@extends('install.layout', ['title' => 'Administrator', 'active' => 2])
@section('body')
    <h1 class="font-heading text-3xl font-medium text-ink">Your administrator account</h1>
    <p class="mt-2 text-muted">The database is ready. Create the main administrator account — you can add team members later.</p>
    @if ($errors->any())
        <div class="mt-6 rounded-ui bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif
    <form method="post" action="{{ route('install.admin.save') }}" class="mt-6 space-y-5">
        @csrf
        <div><label class="field-label" for="name">Full name</label><input id="name" name="name" class="input" required value="{{ old('name') }}" placeholder="Dr Jane Smith"></div>
        <div><label class="field-label" for="email">Email</label><input id="email" type="email" name="email" class="input" required value="{{ old('email') }}"></div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div><label class="field-label" for="password">Password</label><input id="password" type="password" name="password" class="input" required autocomplete="new-password"></div>
            <div><label class="field-label" for="password_confirmation">Confirm password</label><input id="password_confirmation" type="password" name="password_confirmation" class="input" required></div>
        </div>
        <p class="field-hint">At least 12 characters with upper- and lower-case letters and numbers. You will also set up two-factor authentication at first sign-in.</p>
        <label class="flex items-start gap-3 rounded-ui bg-subtle p-4 text-sm"><input type="checkbox" name="demo" value="1" class="checkbox mt-0.5" checked>
            <span><strong>Install sample content</strong><br><span class="text-muted">10 example OCT cases, articles, videos and exam questions with synthetic images, so you can see how everything works. Remove it in one click later (System › Health &amp; maintenance).</span></span></label>
        <div class="flex justify-end"><button class="btn btn-primary btn-lg">Finish installation</button></div>
    </form>
@endsection
