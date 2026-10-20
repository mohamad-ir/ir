@extends('emails.layout', ['title' => 'New enquiry'])
@section('body')
    <p style="font-size:12px;letter-spacing:.12em;text-transform:uppercase;font-weight:bold;color:{{ setting('theme.accent') }};margin:0 0 6px">{{ $enquiry->type->getLabel() }}</p>
    <h1 style="font-family:Georgia,serif;font-size:22px;color:{{ setting('theme.primary') }};margin:0 0 18px">{{ $enquiry->subject }}</h1>
    <p><strong>{{ $enquiry->name }}</strong> &lt;{{ $enquiry->email }}&gt;@if($enquiry->organisation)<br>{{ $enquiry->organisation }}@endif</p>
    <div style="white-space:pre-line;background:#f7f6f2;border-radius:8px;padding:16px">{{ $enquiry->message }}</div>
    @include('emails.partials-button', ['url' => $url, 'label' => 'Open in dashboard'])
@endsection
@section('footer')
    <p style="margin:0">Reply directly to this email to answer {{ $enquiry->name }}.</p>
@endsection
