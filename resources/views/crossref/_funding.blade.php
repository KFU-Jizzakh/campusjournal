<fr:program name="fundref" xmlns:fr="http://www.crossref.org/fundref.xsd">
    @foreach ($funding as $funder)
        <fr:assertion name="funder_name">{{ $funder['funder_name'] }}</fr:assertion>
        @if (! empty($funder['funder_identifier']))
            <fr:assertion name="funder_identifier">{{ $funder['funder_identifier'] }}</fr:assertion>
        @endif
        @if (! empty($funder['award_number']))
            <fr:assertion name="award_number">{{ $funder['award_number'] }}</fr:assertion>
        @endif
    @endforeach
</fr:program>
