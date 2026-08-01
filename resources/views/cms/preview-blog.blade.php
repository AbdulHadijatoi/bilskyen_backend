@extends('cms.preview-frame')

@section('content')
@include('cms.partials.blog-body', ['previewMode' => true])
@endsection
