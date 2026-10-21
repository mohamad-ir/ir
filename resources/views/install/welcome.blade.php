@extends('install.layout', ['title' => 'Requirements', 'active' => 0])
@section('body')
    <h1 class="font-heading text-3xl font-medium text-ink">Welcome</h1>
    <p class="mt-2 text-muted">This assistant installs the website in three short steps. First, we check that your hosting meets the requirements.</p>
    <ul class="mt-6 divide-y divide-line rounded-ui ring-1 ring-line">
        @foreach ($checks as $c)
            <li class="flex items-start gap-3 px-4 py-2.5 text-sm">
                <span class="mt-0.5 font-bold {{ $c['ok'] ? 'text-emerald-600' : 'text-red-600' }}">{{ $c['ok'] ? '✓' : '✗' }}</span>
                <div class="flex-1"><div class="flex justify-between gap-4"><span>{{ $c['label'] }}</span><span class="text-muted">{{ $c['value'] }}</span></div>
                    @if (! $c['ok'] && ! empty($c['help']))<p class="mt-0.5 text-xs text-red-700">{{ $c['help'] }}</p>@endif</div>
            </li>
        @endforeach
        <li class="flex items-start gap-3 px-4 py-2.5 text-sm"><span class="mt-0.5 font-bold {{ $envWritable ? 'text-emerald-600' : 'text-red-600' }}">{{ $envWritable ? '✓' : '✗' }}</span><span>Configuration file (.env) writable</span></li>
    </ul>
    <div class="mt-8 flex justify-end">
        @if ($blocking || ! $envWritable)
            <p class="text-sm text-red-700">Please fix the items marked ✗ in DirectAdmin, then reload this page.</p>
        @else
            <a href="{{ route('install.database') }}" class="btn btn-primary btn-lg">Continue</a>
        @endif
    </div>
@endsection
