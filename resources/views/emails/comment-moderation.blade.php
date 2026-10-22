@extends('emails.layout', ['title' => 'Comment awaiting approval'])
@section('body')
    @include('emails.learning._heading', ['eyebrow' => 'Comment awaiting approval', 'heading' => $title])
    <p><strong>{{ $comment->authorName() }}</strong>@if ($comment->moderation_note) — <em>{{ $comment->moderation_note }}</em>@endif</p>
    <div style="background:#f7f6f2;border-radius:8px;padding:16px;margin:0 0 8px">{!! $comment->bodyHtml() !!}</div>
    @include('emails.partials-button', ['url' => $url, 'label' => 'Review comments'])
@endsection
@section('footer')
    <p style="margin:0">Turn these emails off in Settings › Comments.</p>
@endsection
