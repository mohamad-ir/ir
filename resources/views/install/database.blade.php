@extends('install.layout', ['title' => 'Database', 'active' => 1])
@section('body')
    <h1 class="font-heading text-3xl font-medium text-ink">Website & database</h1>
    <p class="mt-2 text-muted">Create a MySQL database and user in <strong>DirectAdmin › MySQL Management</strong>, then enter the details below.</p>
    @if ($errors->any())
        <div class="mt-6 rounded-ui bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif
    <form method="post" action="{{ route('install.database.save') }}" class="mt-6 space-y-5" onsubmit="const b=this.querySelector('button');b.disabled=true;b.textContent='Installing…'">
        @csrf
        <div class="grid gap-5 sm:grid-cols-2">
            <div><label class="field-label" for="site_name">Website name</label><input id="site_name" name="site_name" class="input" required value="{{ old('site_name', 'OCT Academy') }}"></div>
            <div><label class="field-label" for="app_url">Website address</label><input id="app_url" name="app_url" class="input" required value="{{ old('app_url', $url) }}"></div>
        </div>
        <hr class="border-line">
        <div class="grid gap-5 sm:grid-cols-[1fr_7rem]">
            <div><label class="field-label" for="db_host">Database host</label><input id="db_host" name="db_host" class="input" required value="{{ old('db_host', 'localhost') }}"></div>
            <div><label class="field-label" for="db_port">Port</label><input id="db_port" name="db_port" class="input" required value="{{ old('db_port', 3306) }}"></div>
        </div>
        <div><label class="field-label" for="db_database">Database name</label><input id="db_database" name="db_database" class="input" required value="{{ old('db_database') }}" placeholder="username_octacademy"></div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div><label class="field-label" for="db_username">Database user</label><input id="db_username" name="db_username" class="input" required value="{{ old('db_username') }}" autocomplete="off"></div>
            <div><label class="field-label" for="db_password">Database password</label><input id="db_password" type="password" name="db_password" class="input" autocomplete="new-password"></div>
        </div>
        <div class="flex items-center justify-between pt-2">
            <p class="text-xs text-muted">Creating the tables takes up to a minute.</p>
            <button class="btn btn-primary btn-lg">Install database</button>
        </div>
    </form>
@endsection
