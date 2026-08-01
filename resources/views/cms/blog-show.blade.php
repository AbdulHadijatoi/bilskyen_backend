@extends('layouts.app')

@section('title', ($seo['meta_title'] ?? $post->title) . ' | Bilskyen')

@section('content')
@include('cms.partials.blog-body', ['previewMode' => false])
@endsection
