@extends('emails.layout', ['title' => 'Certificate', 'preheader' => 'Congratulations on completing '.$certificate->course_title])
@section('body')
    @include('emails.learning._heading', ['eyebrow' => 'Certificate of completion', 'heading' => 'Congratulations, '.$certificate->recipient_name])
    <p>You have completed <strong>{{ $certificate->course_title }}</strong>. Your certificate is attached to this email as a PDF.</p>
    <p style="font-size:14px;color:#555">Certificate ID <strong>{{ $certificate->code }}</strong>@if($certificate->hoursLabel()) · {{ $certificate->hoursLabel() }} of learning @endif. Anyone can confirm it is genuine at the verification link below — useful for your portfolio or CPD record.</p>
    @include('emails.partials-button', ['url' => $certificate->verifyUrl(), 'label' => 'View and verify certificate'])
@endsection
@section('footer')
    <p style="margin:0">You can download your certificates at any time from My learning.</p>
@endsection
