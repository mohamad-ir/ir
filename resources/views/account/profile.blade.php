@extends('account.layout')
@section('account')
    <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <form method="post" action="{{ route('account.profile.update') }}" class="card space-y-5 p-6 sm:p-8">
            @csrf @method('put')
            <h2 class="font-heading text-2xl font-semibold text-ink">Profile</h2>
            <div class="grid gap-5 sm:grid-cols-[8rem_1fr]">
                <div><label for="title" class="field-label">Title</label><input id="title" name="title" value="{{ old('title', $user->title) }}" class="input" placeholder="Dr"></div>
                <div><label for="name" class="field-label">Full name</label><input id="name" name="name" value="{{ old('name', $user->name) }}" required class="input">@error('name')<p class="field-error">{{ $message }}</p>@enderror</div>
            </div>
            <div><label for="email" class="field-label">Email</label><input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required class="input">@error('email')<p class="field-error">{{ $message }}</p>@enderror</div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div><label for="career_stage" class="field-label">Career stage</label>
                    <select id="career_stage" name="career_stage" class="input"><option value="">—</option>@foreach (\App\Enums\CareerStage::cases() as $s)<option value="{{ $s->value }}" @selected(old('career_stage', $user->career_stage?->value) === $s->value)>{{ $s->getLabel() }}</option>@endforeach</select></div>
                <div><label for="specialty" class="field-label">Sub-specialty</label><input id="specialty" name="specialty" value="{{ old('specialty', $user->specialty) }}" class="input" placeholder="Medical retina"></div>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div><label for="institution" class="field-label">Institution</label><input id="institution" name="institution" value="{{ old('institution', $user->institution) }}" class="input"></div>
                <div><label for="country" class="field-label">Country</label>
                    <select id="country" name="country" class="input"><option value="">—</option>@foreach (\App\Support\Countries::all() as $code => $n)<option value="{{ $code }}" @selected(old('country', $user->country) === $code)>{{ $n }}</option>@endforeach</select></div>
            </div>
            <input type="hidden" name="newsletter" value="0">
            <label class="flex items-center gap-2.5 text-sm"><input type="checkbox" name="newsletter" value="1" class="checkbox" @checked($subscriber?->status?->value === 'subscribed')> Receive the weekly OCT case email</label>
            <input type="hidden" name="notify_replies" value="0">
            <label class="flex items-center gap-2.5 text-sm"><input type="checkbox" name="notify_replies" value="1" class="checkbox" @checked($user->notify_replies ?? true)> Email me when someone replies to my comments</label>
            <button class="btn btn-primary">Save profile</button>
        </form>

        <div class="space-y-8">
            <form method="post" action="{{ route('account.password.update') }}" class="card space-y-4 p-6">
                @csrf @method('put')
                <h2 class="font-heading text-xl font-semibold text-ink">Change password</h2>
                <div><label for="current_password" class="field-label">Current password</label><input id="current_password" type="password" name="current_password" required class="input" autocomplete="current-password">@error('current_password')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div><label for="password" class="field-label">New password</label><input id="password" type="password" name="password" required class="input" autocomplete="new-password">@error('password')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div><label for="password_confirmation" class="field-label">Confirm</label><input id="password_confirmation" type="password" name="password_confirmation" required class="input"></div>
                <button class="btn btn-secondary">Update password</button>
            </form>
            <div class="card space-y-3 p-6">
                <h2 class="font-heading text-xl font-semibold text-ink">Your data</h2>
                <p class="text-sm text-muted">Download everything we store about you, or delete your account permanently.</p>
                <a href="{{ route('account.export') }}" class="btn btn-secondary btn-sm">Download my data</a>
                <form method="post" action="{{ route('account.destroy') }}" x-data="{ confirm: false }" class="pt-2">
                    @csrf @method('delete')
                    <button type="button" x-show="!confirm" x-on:click="confirm = true" class="text-sm font-medium text-red-700 hover:underline">Delete my account…</button>
                    <div x-show="confirm" x-cloak class="space-y-3">
                        <label for="del-password" class="field-label">Confirm with your password</label>
                        <input id="del-password" type="password" name="password" required class="input">
                        @error('password')<p class="field-error">{{ $message }}</p>@enderror
                        <button class="btn btn-sm bg-red-600 text-white hover:bg-red-700">Permanently delete account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
