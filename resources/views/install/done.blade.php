@extends('install.layout', ['title' => 'Done', 'active' => 3])
@section('body')
    <h1 class="font-heading text-3xl font-medium text-ink">Installation complete</h1>
    <p class="mt-2 text-muted">Your website is installed. The installer is now disabled.</p>
    <div class="mt-6 space-y-4 text-sm">
        <div class="rounded-ui bg-subtle p-4">
            <p class="font-semibold text-ink">1. Add the cron job</p>
            <p class="mt-1 text-muted">DirectAdmin › Advanced Features › Cron Jobs → every minute:</p>
            <code class="mt-2 block overflow-x-auto rounded bg-ink p-3 text-xs text-white">{{ $cronLine }}</code>
        </div>
        <div class="rounded-ui bg-subtle p-4">
            <p class="font-semibold text-ink">2. Sign in to the dashboard</p>
            <p class="mt-1 text-muted">Use <strong>{{ $email }}</strong> and the password you chose. Bookmark this address:</p>
            <p class="mt-1 font-mono text-xs">{{ $adminUrl }}</p>
        </div>
        <div class="rounded-ui bg-subtle p-4">
            <p class="font-semibold text-ink">3. Follow the checklist on the dashboard</p>
            <p class="mt-1 text-muted">Email settings, your profile and photo, Google connection and removing sample content.</p>
        </div>
    </div>
    <div class="mt-8 flex justify-end gap-3">
        <a href="{{ url('/') }}" class="btn btn-secondary btn-lg">View website</a>
        <a href="{{ $adminUrl }}" class="btn btn-primary btn-lg">Open dashboard</a>
    </div>
@endsection
