@extends('emails.layout', ['title' => $webinar->title, 'preheader' => 'Your place is confirmed. Add the webinar to your calendar.'])
@section('body')
    @include('emails.learning._heading', ['eyebrow' => 'Live webinar', 'heading' => $webinar->title])
    <p>Dear {{ $user->name }},</p>
    <p>Your place is confirmed. A calendar invitation is attached to this email.</p>
    @include('emails.learning._when')
    <p>The join button opens {{ \App\Models\Webinar::JOIN_OPENS_MINUTES }} minutes before the start. We will also remind you the day before and one hour before.</p>
    @include('emails.partials-button', ['url' => $joinUrl, 'label' => 'Join the webinar'])
    <p style="font-size:14px;color:#555">Please do not share this link — it is personal to your registration. Event page: <a href="{{ $pageUrl }}">{{ $pageUrl }}</a></p>
@endsection
@section('footer')
    <p style="margin:0">You received this email because you registered for a webinar on {{ setting('site.name') }}.</p>
@endsection
