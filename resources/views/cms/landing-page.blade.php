@extends('layouts.app')

@section('title', ($seo['meta_title'] ?? $page->title) . ' | Bilskyen')

@section('content')
@include('cms.partials.landing-body')
@endsection
