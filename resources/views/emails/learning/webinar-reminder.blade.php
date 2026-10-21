@extends('emails.layout', ['title' => $webinar->title, 'preheader' => $when === 'hour' ? 'Starting in one hour.' : 'Your webinar is tomorrow.'])
@section('body')
    @include('emails.learning._heading', ['eyebrow' => $when === 'hour' ? 'Starting in one hour' : 'Reminder — tomorrow', 'heading' => $webinar->title])
    <p>Dear {{ $user->name }},</p>
    <p>{{ $when === 'hour' ? 'The webinar starts in about an hour.' : 'This is a reminder that the webinar takes place tomorrow.' }}</p>
    @include('emails.learning._when')
    @if (filled($webinar->join_details) && $when === 'hour')
        <p style="white-space:pre-line;font-size:14px;color:#444">{{ $webinar->join_details }}</p>
    @endif
    @include('emails.partials-button', ['url' => $joinUrl, 'label' => 'Join the webinar'])
@endsection
@section('footer')
    <p style="margin:0">You received this reminder because you registered for this webinar.</p>
@endsection
