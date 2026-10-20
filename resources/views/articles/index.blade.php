@extends('layouts.app')
@section('content')
    <x-page-header eyebrow="Articles" :title="$category ? $category->name : 'Clinical articles & guides'"
                   :intro="$category?->description ?: 'Systematic approaches, OCT signs and practical reviews for residents, fellows and consultants.'"
                   :breadcrumbs="$category ? ['Articles' => route('articles.index'), $category->name => null] : ['Articles' => null]" />
    <div class="container-site py-12 lg:py-16">
        <livewire:article-index :category-id="$category?->id" />
    </div>
@endsection
