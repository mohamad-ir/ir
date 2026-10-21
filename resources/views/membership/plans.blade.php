{{-- Plan cards; used by the membership page and the "Membership plans" page-builder block. --}}
@php
    $intervals = $plans->pluck('interval')->unique()->values();
    $hasToggle = $intervals->count() > 1;
    $monthly = $plans->firstWhere('interval', 'month');
    $current = $subscription?->membership_plan_id;
@endphp
<div x-data="{ interval: @js($intervals->contains('year') ? 'year' : ($intervals->first() ?? 'month')) }">
    @if ($hasToggle)
        <div class="mb-10 flex justify-center">
            <div class="inline-flex rounded-full bg-subtle p-1 ring-1 ring-line" role="group" aria-label="Billing period">
                <button type="button" x-on:click="interval = 'month'" :class="interval === 'month' ? 'bg-surface text-ink shadow-sm' : 'text-muted'" class="rounded-full px-5 py-2 text-sm font-semibold transition">Monthly</button>
                <button type="button" x-on:click="interval = 'year'" :class="interval === 'year' ? 'bg-surface text-ink shadow-sm' : 'text-muted'" class="rounded-full px-5 py-2 text-sm font-semibold transition">Yearly</button>
            </div>
        </div>
    @endif
    <div class="mx-auto grid max-w-5xl justify-center gap-6 md:grid-cols-[repeat(auto-fit,minmax(18rem,22rem))]">
        @foreach ($plans as $plan)
            @php $saving = $plan->interval === 'year' && $monthly && $monthly->currency === $plan->currency && $monthly->price * 12 > $plan->price ? (int) round((1 - $plan->price / ($monthly->price * 12)) * 100) : null; @endphp
            <div @if($hasToggle) x-show="interval === @js($plan->interval)" x-cloak @endif
                 @class(['relative flex flex-col rounded-card bg-surface p-7 ring-1', 'ring-2 ring-accent shadow-[var(--shadow-pop)]' => $plan->is_featured, 'ring-line' => ! $plan->is_featured])>
                @if ($plan->is_featured)
                    <span class="absolute -top-3 left-7 rounded-full bg-accent px-3 py-1 text-[0.7rem] font-bold uppercase tracking-wider text-accent-contrast">Most popular</span>
                @endif
                <p class="font-heading text-2xl font-semibold text-ink">{{ $plan->name }}</p>
                @if ($plan->tagline)<p class="mt-1 text-sm text-muted">{{ $plan->tagline }}</p>@endif
                <p class="mt-6 flex items-baseline gap-1.5">
                    <span class="font-heading text-5xl font-semibold tracking-tight text-ink">{{ $plan->priceLabel() }}</span>
                    <span class="text-muted">/ {{ $plan->intervalLabel() }}</span>
                </p>
                <p class="mt-1 min-h-5 text-sm text-muted">
                    @if ($plan->monthlyEquivalent()) {{ $plan->monthlyEquivalent() }} per month @endif
                    @if ($saving) <span class="ml-1 font-semibold text-accent-ink">Save {{ $saving }}%</span> @endif
                </p>
                @if ($plan->trial_days)
                    <p class="mt-4 rounded-ui bg-accent/10 px-3 py-2 text-sm font-medium text-accent-ink">{{ $plan->trial_days }}-day free trial</p>
                @endif
                <ul class="mt-6 space-y-3 text-[0.95rem]" role="list">
                    @foreach (collect($plan->features)->pluck('text')->filter() as $feature)
                        <li class="flex gap-3"><x-heroicon-s-check class="mt-0.5 size-5 shrink-0 text-accent" /> {{ $feature }}</li>
                    @endforeach
                </ul>
                <div class="mt-auto pt-8">
                    @if ($current === $plan->id)
                        <a href="{{ route('account.billing') }}" class="btn btn-secondary w-full">Your current plan — manage</a>
                    @elseif ($subscription)
                        <a href="{{ route('account.billing') }}" class="btn btn-secondary w-full">Change plan in billing</a>
                    @elseif (! $paymentsEnabled)
                        <span class="btn btn-secondary w-full cursor-default opacity-70">Opening soon</span>
                    @else
                        @auth
                            <form method="post" action="{{ route('checkout.membership', $plan->slug) }}" x-data="{ busy: false }" x-on:submit="busy = true">@csrf
                                <button @class(['btn w-full', 'btn-primary' => $plan->is_featured, 'btn-secondary' => ! $plan->is_featured]) x-bind:disabled="busy">
                                    <span x-show="!busy">{{ $plan->trial_days ? 'Start free trial' : 'Become a member' }}</span><span x-show="busy" x-cloak>Redirecting…</span>
                                </button>
                            </form>
                        @else
                            <a href="{{ route('register', ['redirect' => route('membership', absolute: false)]) }}" @class(['btn w-full', 'btn-primary' => $plan->is_featured, 'btn-secondary' => ! $plan->is_featured])>{{ $plan->trial_days ? 'Start free trial' : 'Become a member' }}</a>
                        @endauth
                    @endif
                </div>
            </div>
        @endforeach
    </div>
    @if ($paymentsEnabled && $plans->isNotEmpty())
        <p class="mt-8 text-center text-sm text-muted">Cancel any time · {{ setting('payments.prices_include_tax', true) ? 'Prices include VAT · ' : '' }}Secure payment by Stripe</p>
    @endif
</div>
