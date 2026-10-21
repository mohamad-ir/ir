{{-- EU consumer law: explicit agreement to the terms and to immediate access to digital content. --}}
<label class="flex items-start gap-2.5 text-sm leading-snug">
    <input type="checkbox" name="terms" value="1" required class="checkbox mt-0.5" @checked(old('terms'))>
    <span>I agree to the <a href="{{ url(setting('payments.terms_url', '/terms-of-sale')) }}" class="link" target="_blank">terms of sale</a> and <a href="{{ url(setting('payments.refund_url', '/refund-policy')) }}" class="link" target="_blank">refund policy</a>.</span>
</label>
@if (setting('payments.withdrawal_waiver', true))
    <label class="flex items-start gap-2.5 text-sm leading-snug">
        <input type="checkbox" name="waiver" value="1" required class="checkbox mt-0.5" @checked(old('waiver'))>
        <span>I want access to start immediately and understand that I lose my 14-day right of withdrawal once access begins.</span>
    </label>
@endif
@if ($errors->has('terms') || $errors->has('waiver'))
    <p class="field-error">{{ $errors->first('terms') ?: $errors->first('waiver') }}</p>
@endif
