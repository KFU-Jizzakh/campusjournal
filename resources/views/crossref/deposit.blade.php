{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<doi_batch version="5.3.1"
           xmlns="http://www.crossref.org/schema/5.3.1"
           xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
           xsi:schemaLocation="http://www.crossref.org/schema/5.3.1 https://www.crossref.org/schemas/crossref5.3.1.xsd">
    <head>
        <doi_batch_id>{{ $batchId }}</doi_batch_id>
        <timestamp>{{ $timestamp }}</timestamp>
        <depositor>
            <depositor_name>{{ $depositorName }}</depositor_name>
            <email_address>{{ $depositorEmail }}</email_address>
        </depositor>
        <registrant>{{ $registrant }}</registrant>
        <crossmark>
            <crossmark_version>1</crossmark_version>
            <crossmark_policy>{{ $crossmarkPolicyUrl }}</crossmark_policy>
            @foreach ($crossmarkDomains as $domain)
                <crossmark_domain>
                    <domain>{{ $domain }}</domain>
                </crossmark_domain>
            @endforeach
            @if ($updateType)
                <doi_updates>
                    @if ($updateType === 'retraction')
                        <update type="retraction"/>
                    @elseif ($updateType === 'correction')
                        <update type="correction"/>
                    @endif
                </doi_updates>
            @endif
        </crossmark>
    </head>
    <body>
        <journal>
            <journal_metadata language="ru">
                <full_title>{{ config('app.name') }}</full_title>
            </journal_metadata>
            @if ($issue)
                <journal_issue>
                    <publication_date media_type="online">
                        @if ($issue->published_at)
                            <month>{{ $issue->published_at->format('m') }}</month>
                            <day>{{ $issue->published_at->format('d') }}</day>
                        @endif
                        <year>{{ $issue->year }}</year>
                    </publication_date>
                    <journal_volume>
                        <volume>{{ $issue->volume }}</volume>
                    </journal_volume>
                    <issue>{{ $issue->number }}</issue>
                    @if ($issue->doi)
                        <doi_data>
                            <doi>{{ $issue->doi }}</doi>
                            <resource>{{ url('/issues/'.$issue->id) }}</resource>
                        </doi_data>
                    @endif
                </journal_issue>
            @endif
            @include('crossref._journal_article', [
                'article' => $article,
                'authors' => $authors,
                'authorNameParts' => $authorNameParts,
                'resourceUrl' => $resourceUrl,
                'doi' => $doi,
            ])
        </journal>
    </body>
</doi_batch>
