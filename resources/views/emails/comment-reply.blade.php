@extends('emails.layout', ['title' => 'New reply'])
@section('body')
    @include('emails.learning._heading', ['eyebrow' => 'New reply', 'heading' => $title])
    <p><strong>{{ $comment->authorName() }}</strong> replied to your comment:</p>
    <div style="background:#f7f6f2;border-radius:8px;padding:16px;margin:0 0 8px">{!! $comment->bodyHtml() !!}</div>
    @include('emails.partials-button', ['url' => $url, 'label' => 'View the discussion'])
@endsection
@section('footer')
    <p style="margin:0">You received this email because you commented on {{ setting('site.name') }}.@if ($unsubscribeUrl) <a href="{{ $unsubscribeUrl }}" style="color:inherit">Stop emails about replies</a>.@endif</p>
@endsection
