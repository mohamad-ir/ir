@extends('auth.layout')
@section('form')
    <h1 class="font-heading text-4xl font-medium text-ink">Create your free account</h1>
    <p class="mt-2 text-muted">Save cases, track your exam practice and access members-only material.</p>
    <form method="post" action="{{ route('register') }}" class="mt-8 space-y-5">
        @csrf
        <div class="hidden" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
        <div>
            <label for="name" class="field-label">Full name</label>
            <input id="name" name="name" value="{{ old('name') }}" required autocomplete="name" class="input">
            @error('name')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="email" class="field-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" class="input">
            @error('email')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="career_stage" class="field-label">I am a…</label>
                <select id="career_stage" name="career_stage" required class="input">
                    <option value="">Select</option>
                    @foreach (\App\Enums\CareerStage::cases() as $stage)<option value="{{ $stage->value }}" @selected(old('career_stage') === $stage->value)>{{ $stage->getLabel() }}</option>@endforeach
                </select>
                @error('career_stage')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="country" class="field-label">Country</label>
                <select id="country" name="country" class="input">
                    <option value="">Select</option>
                    @foreach (\App\Support\Countries::all() as $code => $name)<option value="{{ $code }}" @selected(old('country') === $code)>{{ $name }}</option>@endforeach
                </select>
            </div>
        </div>
        <div>
            <label for="institution" class="field-label">Hospital / institution <span class="font-normal text-muted">(optional)</span></label>
            <input id="institution" name="institution" value="{{ old('institution') }}" class="input" autocomplete="organization">
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="password" class="field-label">Password</label>
                <input id="password" type="password" name="password" required autocomplete="new-password" class="input">
                @error('password')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="field-label">Confirm password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="input">
            </div>
        </div>
        <p class="field-hint -mt-2">At least 10 characters with letters and numbers.</p>
        <label class="flex items-start gap-2.5 text-sm"><input type="checkbox" name="newsletter" value="1" class="checkbox mt-0.5" @checked(old('newsletter', true))> Send me the weekly OCT case by email</label>
        <label class="flex items-start gap-2.5 text-sm"><input type="checkbox" name="terms" value="1" class="checkbox mt-0.5" required> <span>I am a healthcare professional or student and accept the <a href="{{ url('/terms') }}" class="link" target="_blank">terms</a> and <a href="{{ url('/privacy') }}" class="link" target="_blank">privacy policy</a>.</span></label>
        @error('terms')<p class="field-error -mt-3">{{ $message }}</p>@enderror
        <button class="btn btn-primary btn-lg w-full">Create account</button>
    </form>
    <p class="mt-8 text-center text-sm text-muted">Already registered? <a href="{{ route('login') }}" class="link">Sign in</a></p>
@endsection
