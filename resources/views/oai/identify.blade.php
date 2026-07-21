@extends('oai.envelope')
@section('content')
    <Identify>
        <repositoryName>{{ $repositoryName }}</repositoryName>
        <baseURL>{{ $baseUrl }}</baseURL>
        <protocolVersion>2.0</protocolVersion>
        <adminEmail>{{ $adminEmail }}</adminEmail>
        <earliestDatestamp>{{ $earliestDatestamp }}</earliestDatestamp>
        <deletedRecord>persistent</deletedRecord>
        <granularity>YYYY-MM-DDThh:mm:ssZ</granularity>
        <description>
            <oai-identifier xmlns="http://www.openarchives.org/OAI/2.0/oai-identifier"
                            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                            xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/oai-identifier http://www.openarchives.org/OAI/2.0/oai-identifier.xsd">
                <scheme>oai</scheme>
                <repositoryIdentifier>{{ $repositoryId }}</repositoryIdentifier>
                <delimiter>:</delimiter>
                <sampleIdentifier>oai:{{ $repositoryId }}:article:1</sampleIdentifier>
            </oai-identifier>
        </description>
    </Identify>
@endsection
