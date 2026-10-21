@extends('emails.layout', ['title' => $course->title, 'preheader' => 'Your course is ready — start learning now.'])
@section('body')
    @include('emails.learning._heading', ['eyebrow' => 'Course enrolment', 'heading' => $course->title])
    <p>Dear {{ $user->name }},</p>
    <p>You now have access to <strong>{{ $course->title }}</strong>. You can work through the lessons at your own pace, and your progress is saved automatically.</p>
    @if ($order && $order->isPaid())
        <p style="color:#555;font-size:14px">Order {{ $order->number }} · {{ $order->totalLabel() }}. Your payment receipt is sent separately by our payment provider and is always available under <a href="{{ route('account.billing') }}">Account › Billing</a>.</p>
    @endif
    @include('emails.partials-button', ['url' => $url, 'label' => 'Start the course'])
@endsection
@section('footer')
    <p style="margin:0">You received this email because you enrolled in a course on {{ setting('site.name') }}.</p>
@endsection
