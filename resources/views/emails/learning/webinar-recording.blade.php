@extends('emails.layout', ['title' => $webinar->title, 'preheader' => 'Watch the recording at any time.'])
@section('body')
    @include('emails.learning._heading', ['eyebrow' => 'Recording available', 'heading' => $webinar->title])
    <p>Dear {{ $user->name }},</p>
    <p>Thank you for registering. The recording of the webinar is now available on the event page — you can watch it at any time.</p>
    @include('emails.partials-button', ['url' => $url, 'label' => 'Watch the recording'])
@endsection
@section('footer')
    <p style="margin:0">You received this email because you registered for this webinar.</p>
@endsection
