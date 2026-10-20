@extends('emails.layout', ['title' => 'Confirm your subscription', 'preheader' => 'One click to start receiving the weekly OCT case.'])
@section('body')
    <h1 style="font-family:Georgia,serif;font-size:26px;line-height:1.25;color:{{ setting('theme.primary') }};margin:0 0 16px">Confirm your subscription</h1>
    <p>Thank you for signing up to {{ setting('site.name') }}. Please confirm your email address so we can send you new teaching cases and resources.</p>
    @include('emails.partials-button', ['url' => $confirmUrl, 'label' => 'Confirm my subscription'])
    <p style="color:#6b7280;font-size:14px">If you did not request this, you can safely ignore this email — you will not be subscribed.</p>
@endsection
@section('footer')
    <p style="margin:0">You received this email because this address was entered at {{ url('/') }}.</p>
@endsection
