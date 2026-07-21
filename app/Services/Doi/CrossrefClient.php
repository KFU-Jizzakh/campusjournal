<?php

namespace App\Services\Doi;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * PURPOSE: HTTP client for submitting Crossref deposit XML
 * via multipart POST to the configured endpoint.
 *
 * SPECIFICATION: SPEC-08/AC-5
 */
class CrossrefClient
{
    public function deposit(string $xml, string $batchId): Response
    {
        $config = config('services.crossref');

        if (! str_starts_with((string) ($config['endpoint'] ?? ''), 'https://')) {
            throw new \RuntimeException('Crossref endpoint must use HTTPS.');
        }

        return Http::asMultipart()
            ->timeout(60)
            ->attach('fname', $xml, $batchId.'.xml', ['Content-Type' => 'application/xml'])
            ->post($config['endpoint'], [
                ['name' => 'operation', 'contents' => 'doMDUpload'],
                ['name' => 'login_id', 'contents' => (string) ($config['username'] ?? '')],
                ['name' => 'login_passwd', 'contents' => (string) ($config['password'] ?? '')],
            ]);
    }
}
