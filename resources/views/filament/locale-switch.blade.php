{{-- Dashboard language switch in the top bar (English | فارسی). Only the dashboard changes; the public website stays English. --}}
@php($current = \App\Support\AdminLocale::current())
<nav class="oa-locale-switch" aria-label="{{ __('Dashboard language') }}">
    @foreach (['en' => ['EN', 'English'], 'fa' => ['فا', 'فارسی']] as $code => [$short, $long])
        @if ($code === $current)
            <span class="oa-locale-switch-item oa-locale-switch-current" aria-current="true" lang="{{ $code }}" title="{{ $long }}">
                <span class="sm:hidden">{{ $short }}</span><span class="hidden sm:inline">{{ $long }}</span>
            </span>
        @else
            <a href="{{ route('admin.locale', ['locale' => $code]) }}" class="oa-locale-switch-item" lang="{{ $code }}" hreflang="{{ $code }}" title="{{ $long }}">
                <span class="sm:hidden">{{ $short }}</span><span class="hidden sm:inline">{{ $long }}</span>
            </a>
        @endif
    @endforeach
</nav>
