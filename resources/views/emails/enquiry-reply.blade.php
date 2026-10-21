@extends('emails.layout', ['title' => $replySubject])
@section('body')
    <p>Dear {{ $enquiry->name }},</p>
    <div style="white-space:pre-line">{{ $body }}</div>
    <p style="margin-top:24px">Kind regards,<br>{{ setting('doctor.name') }}</p>
    <hr style="border:0;border-top:1px solid #e5e3dc;margin:28px 0">
    <p style="font-size:13px;color:#7a7f87"><strong>Your original message:</strong><br><span style="white-space:pre-line">{{ \Illuminate\Support\Str::limit($enquiry->message, 1500) }}</span></p>
@endsection
@section('footer')
    <p style="margin:0">{{ setting('site.name') }} · {{ url('/') }}</p>
@endsection
