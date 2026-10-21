@extends('emails.layout', ['title' => $webinar->title])
@section('body')
    @include('emails.learning._heading', ['eyebrow' => 'Webinar update', 'heading' => $webinar->title])
    <div class="content">{!! $html !!}</div>
    @unless ($webinar->hasEnded())
        @include('emails.learning._when')
        @include('emails.partials-button', ['url' => $joinUrl, 'label' => 'Join the webinar'])
    @endunless
@endsection
@section('footer')
    <p style="margin:0">You received this email because you registered for this webinar.</p>
@endsection
