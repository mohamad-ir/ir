@extends('emails.layout', ['title' => $campaign->subject, 'preheader' => $campaign->preheader, 'pixel' => $pixel])
@section('body')
    <div class="content">{!! $html !!}</div>
@endsection
@section('footer')
    <p style="margin:0">You are receiving this because you subscribed at {{ url('/') }}. <a href="{{ $unsubscribeUrl }}" style="color:#7a7f87">Unsubscribe</a></p>
@endsection
