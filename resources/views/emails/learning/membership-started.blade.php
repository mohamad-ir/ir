@extends('emails.layout', ['title' => 'Welcome', 'preheader' => 'Your membership is active.'])
@section('body')
    @include('emails.learning._heading', ['eyebrow' => 'Membership', 'heading' => 'Welcome, '.$user->name])
    <p>Thank you for becoming a member. Your membership{{ $subscription->plan ? ' ('.$subscription->plan->name.')' : '' }} is now active and every members-only case, video, question, course and webinar is unlocked.</p>
    @if ($subscription->trial_ends_at && $subscription->trial_ends_at->isFuture())
        <p>Your free trial runs until <strong>{{ $subscription->trial_ends_at->format('j F Y') }}</strong>. You can cancel before then and you will not be charged.</p>
    @endif
    @include('emails.partials-button', ['url' => $url, 'label' => 'Go to my learning'])
    <p style="font-size:14px;color:#555">Invoices, payment details and cancellation are managed under <a href="{{ $billingUrl }}">Account › Billing</a>.</p>
@endsection
@section('footer')
    <p style="margin:0">You received this email because you started a membership on {{ setting('site.name') }}.</p>
@endsection
