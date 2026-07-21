<?php

namespace App\Http\Controllers;

use App\Enums\ArticleStatus;
use App\Exceptions\Oai\BadArgumentException;
use App\Exceptions\Oai\BadVerbException;
use App\Exceptions\Oai\CannotDisseminateFormatException;
use App\Exceptions\Oai\IdDoesNotExistException;
use App\Exceptions\Oai\NoRecordsMatchException;
use App\Exceptions\Oai\OaiException;
use App\Models\Article;
use App\Models\Setting;
use App\Services\Oai\OaiIdentifier;
use App\Services\Oai\OaiRecordQuery;
use App\Services\Oai\OaiSetResolver;
use App\Services\Oai\ResumptionToken;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;

/**
 * PURPOSE: OAI-PMH 2.0 protocol endpoint supporting 6 verbs
 * with Dublin Core, DOAJ, Crossref, and NLM metadata formats.
 *
 * SPECIFICATION: SPEC-09/AC-1, SPEC-09/AC-2, SPEC-09/AC-3, SPEC-09/AC-4, SPEC-09/AC-5, SPEC-09/AC-6, SPEC-09/AC-7, SPEC-09/AC-8, SPEC-09/AC-9
 */
class OaiPmhController extends Controller
{
    private const FORMATS = [
        'oai_dc' => [
            'prefix' => 'oai_dc',
            'schema' => 'http://www.openarchives.org/OAI/2.0/oai_dc.xsd',
            'namespace' => 'http://www.openarchives.org/OAI/2.0/oai_dc/',
        ],
        'oai_doaj' => [
            'prefix' => 'oai_doaj',
            'schema' => 'https://doaj.org/static/doaj/doajArticles.xsd',
            'namespace' => 'http://www.doaj.org/schemas/',
        ],
        'crossref' => [
            'prefix' => 'crossref',
            'schema' => 'https://data.crossref.org/schemas/crossref5.3.1.xsd',
            'namespace' => 'http://www.crossref.org/schema/5.3.1',
        ],
        'nlm' => [
            'prefix' => 'nlm',
            'schema' => 'https://jats.nlm.nih.gov/publishing/1.3/xsd/JATS-journalpublishing1.xsd',
            'namespace' => 'https://jats.nlm.nih.gov/publishing/1.3/',
        ],
    ];

    private const VERB_ARGS = [
        'Identify' => [],
        'ListMetadataFormats' => ['identifier'],
        'ListSets' => ['resumptionToken'],
        'ListIdentifiers' => ['from', 'until', 'set', 'metadataPrefix', 'resumptionToken'],
        'ListRecords' => ['from', 'until', 'set', 'metadataPrefix', 'resumptionToken'],
        'GetRecord' => ['identifier', 'metadataPrefix'],
    ];

    public function handle(Request $request): Response
    {
        $params = $request->isMethod('post') ? $request->post() : $request->query();
        $params = is_array($params) ? $params : [];

        try {
            $verb = (string) ($params['verb'] ?? '');

            if (! array_key_exists($verb, self::VERB_ARGS)) {
                throw new BadVerbException('Illegal or missing verb.');
            }

            $this->validateArgs($verb, $params);

            $body = match ($verb) {
                'Identify' => $this->identify($request, $params),
                'ListMetadataFormats' => $this->listMetadataFormats($request, $params),
                'ListSets' => $this->listSets($request, $params),
                'ListIdentifiers' => $this->listIdentifiers($request, $params),
                'ListRecords' => $this->listRecords($request, $params),
                'GetRecord' => $this->getRecord($request, $params),
            };
        } catch (OaiException $e) {
            $body = $this->renderError($request, $params, $e);
        }

        return response($body, 200, ['Content-Type' => 'text/xml; charset=UTF-8']);
    }

    private function validateArgs(string $verb, array $params): void
    {
        $allowed = array_merge(['verb'], self::VERB_ARGS[$verb]);

        foreach (array_keys($params) as $key) {
            if (! in_array($key, $allowed, true)) {
                throw new BadArgumentException("Unknown argument: {$key}.");
            }
        }

        if (in_array($verb, ['ListRecords', 'ListIdentifiers'], true)) {
            $hasToken = ! empty($params['resumptionToken']);
            $hasPrefix = ! empty($params['metadataPrefix']);

            if ($hasToken && (isset($params['from']) || isset($params['until']) || isset($params['set']) || isset($params['metadataPrefix']))) {
                throw new BadArgumentException('resumptionToken is exclusive.');
            }

            if (! $hasToken && ! $hasPrefix) {
                throw new BadArgumentException('metadataPrefix is required.');
            }
        }

        if ($verb === 'GetRecord') {
            if (empty($params['identifier']) || empty($params['metadataPrefix'])) {
                throw new BadArgumentException('identifier and metadataPrefix are required.');
            }
        }
    }

    private function baseEnvelope(Request $request, array $params): array
    {
        return [
            'responseDate' => now()->utc()->format('Y-m-d\TH:i:s\Z'),
            'baseUrl' => route('oai'),
            'requestAttributes' => $params,
        ];
    }

    private function identify(Request $request, array $params): string
    {
        $earliest = Article::published()->min('published_at');
        $earliestDatestamp = $earliest
            ? Carbon::parse($earliest)->utc()->format('Y-m-d\TH:i:s\Z')
            : '1970-01-01T00:00:00Z';

        return View::make('oai.identify', [
            ...$this->baseEnvelope($request, $params),
            'repositoryName' => config('oai.repository_name'),
            'repositoryId' => OaiIdentifier::repositoryId(),
            'adminEmail' => config('oai.admin_email') ?: 'admin@example.com',
            'earliestDatestamp' => $earliestDatestamp,
        ])->render();
    }

    private function listMetadataFormats(Request $request, array $params): string
    {
        if (! empty($params['identifier'])) {
            $parsed = OaiIdentifier::parse($params['identifier']);
            if (! $parsed || ! Article::withTrashed()->whereKey($parsed['id'])->exists()) {
                throw new IdDoesNotExistException('Unknown identifier.');
            }
        }

        return View::make('oai.list-metadata-formats', [
            ...$this->baseEnvelope($request, $params),
            'formats' => array_values(self::FORMATS),
        ])->render();
    }

    private function listSets(Request $request, array $params): string
    {
        return View::make('oai.list-sets', [
            ...$this->baseEnvelope($request, $params),
            'sets' => OaiSetResolver::all(),
        ])->render();
    }

    private function listIdentifiers(Request $request, array $params): string
    {
        [$records, $nextToken, $metadataPrefix] = $this->paginate($params);

        return View::make('oai.list-identifiers', [
            ...$this->baseEnvelope($request, $params),
            'records' => $records,
            'resumptionToken' => $nextToken,
            'metadataPrefix' => $metadataPrefix,
        ])->render();
    }

    private function listRecords(Request $request, array $params): string
    {
        [$records, $nextToken, $metadataPrefix] = $this->paginate($params);

        return View::make('oai.list-records', [
            ...$this->baseEnvelope($request, $params),
            'records' => $records,
            'resumptionToken' => $nextToken,
            'metadataPrefix' => $metadataPrefix,
            'electronicIssn' => Setting::get('journal_issn_electronic'),
        ])->render();
    }

    private function paginate(array $params): array
    {
        if (! empty($params['resumptionToken'])) {
            $payload = ResumptionToken::decode((string) $params['resumptionToken']);
            $criteria = [
                'metadataPrefix' => $payload['metadataPrefix'] ?? null,
                'set' => $payload['set'] ?? null,
                'from' => $payload['from'] ?? null,
                'until' => $payload['until'] ?? null,
                'offset' => (int) ($payload['offset'] ?? 0),
            ];
        } else {
            $criteria = [
                'metadataPrefix' => $params['metadataPrefix'] ?? null,
                'set' => $params['set'] ?? null,
                'from' => $params['from'] ?? null,
                'until' => $params['until'] ?? null,
                'offset' => 0,
            ];
        }

        $prefix = $criteria['metadataPrefix'];
        if (! $prefix || ! isset(self::FORMATS[$prefix])) {
            throw new CannotDisseminateFormatException('Unsupported metadataPrefix.');
        }

        if (! empty($criteria['set']) && ! OaiSetResolver::exists($criteria['set'])) {
            throw new NoRecordsMatchException('Unknown set.');
        }

        [$articles, $hasMore] = OaiRecordQuery::run($criteria);

        if ($articles->isEmpty()) {
            throw new NoRecordsMatchException('No records match.');
        }

        $records = $articles->map(fn (Article $a) => $this->buildRecord($a))->all();

        $nextToken = null;
        if ($hasMore) {
            $nextToken = ResumptionToken::encode([
                'metadataPrefix' => $criteria['metadataPrefix'],
                'set' => $criteria['set'],
                'from' => $criteria['from'],
                'until' => $criteria['until'],
                'offset' => $criteria['offset'] + $articles->count(),
            ]);
        }

        return [$records, $nextToken, $prefix];
    }

    private function getRecord(Request $request, array $params): string
    {
        $prefix = (string) $params['metadataPrefix'];
        if (! isset(self::FORMATS[$prefix])) {
            throw new CannotDisseminateFormatException('Unsupported metadataPrefix.');
        }

        $parsed = OaiIdentifier::parse((string) $params['identifier']);
        if (! $parsed) {
            throw new IdDoesNotExistException('Unknown identifier.');
        }

        $article = Article::withTrashed()->with(['authors', 'issue', 'category'])->find($parsed['id']);

        if (! $article || (! $article->trashed() && $article->status !== ArticleStatus::Published)) {
            throw new IdDoesNotExistException('Unknown identifier.');
        }

        return View::make('oai.get-record', [
            ...$this->baseEnvelope($request, $params),
            'record' => $this->buildRecord($article),
            'metadataPrefix' => $prefix,
            'electronicIssn' => Setting::get('journal_issn_electronic'),
        ])->render();
    }

    private function buildRecord(Article $article): array
    {
        $article->loadMissing(['authors', 'issue', 'category']);
        $timestamp = $article->trashed() ? $article->deleted_at : $article->updated_at;

        return [
            'article' => $article,
            'identifier' => OaiIdentifier::forArticle($article),
            'datestamp' => Carbon::parse($timestamp)->utc()->format('Y-m-d\TH:i:s\Z'),
            'setSpecs' => OaiSetResolver::setsForArticle($article),
        ];
    }

    private function renderError(Request $request, array $params, OaiException $e): string
    {
        // Per spec, when verb/argument errors occur, <request> has no attributes.
        $safeAttrs = in_array($e->errorCode(), ['badVerb', 'badArgument'], true) ? [] : $params;

        return View::make('oai.error', [
            'responseDate' => now()->utc()->format('Y-m-d\TH:i:s\Z'),
            'baseUrl' => route('oai'),
            'requestAttributes' => $safeAttrs,
            'errorCode' => $e->errorCode(),
            'errorMessage' => $e->getMessage(),
        ])->render();
    }
}
