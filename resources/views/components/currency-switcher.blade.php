{{-- Lets visitors choose the currency prices are shown and charged in. Hidden when only one currency is offered. --}}
@props(['dark' => false])
@php $currencies = \App\Support\Currency::enabled(); $current = \App\Support\Currency::current(); @endphp
@if (count($currencies) > 1)
    <form method="post" action="{{ route('currency') }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 text-sm']) }} x-data>
        @csrf
        <label for="currency-{{ $id = \Illuminate\Support\Str::random(6) }}" @class(['text-white/60' => $dark, 'text-muted' => ! $dark])>Currency</label>
        <select id="currency-{{ $id }}" name="currency" x-on:change="$el.form.submit()"
                @class(['rounded-ui border-0 py-1 pl-2 pr-7 text-sm font-medium ring-1 focus:ring-2 focus:ring-accent', 'bg-white/10 text-white ring-white/20' => $dark, 'bg-surface text-ink ring-line' => ! $dark])>
            @foreach ($currencies as $code)
                <option value="{{ $code }}" @selected($code === $current) @if($dark) class="text-ink" @endif>{{ $code }} · {{ trim(\Illuminate\Support\Str::after(\App\Support\Money::CURRENCIES[$code] ?? $code, '(') , ')') }}</option>
            @endforeach
        </select>
        <noscript><button class="btn btn-sm btn-secondary">Change</button></noscript>
    </form>
@endif
