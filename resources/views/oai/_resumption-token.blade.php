@if ($resumptionToken !== null)
    <resumptionToken>{{ $resumptionToken }}</resumptionToken>
@elseif ($emitEmptyToken ?? false)
    <resumptionToken/>
@endif
