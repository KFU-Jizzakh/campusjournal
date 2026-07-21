@extends('oai.envelope')
@section('content')
    <error code="{{ $errorCode }}">{{ $errorMessage }}</error>
@endsection
