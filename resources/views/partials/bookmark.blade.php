@auth
    @php($saved = auth()->user()->hasBookmarked($model))
    <div x-data="{ saved: {{ $saved ? 'true' : 'false' }}, busy: false }">
        <button type="button" class="btn btn-secondary btn-sm" :aria-pressed="saved" :disabled="busy"
                x-on:click="busy = true; fetch(@js(route('account.bookmark', [$type, $model->id])), { method: 'POST', headers: { 'X-CSRF-TOKEN': @js(csrf_token()), 'Accept': 'application/json' } }).then(r => r.json()).then(d => saved = d.saved).finally(() => busy = false)">
            <x-heroicon-o-bookmark class="size-4" x-show="!saved" />
            <x-heroicon-s-bookmark class="size-4 text-accent" x-show="saved" x-cloak />
            <span x-text="saved ? 'Saved to My learning' : 'Save for later'">Save for later</span>
        </button>
    </div>
@else
    <a href="{{ route('login') }}" class="btn btn-secondary btn-sm"><x-heroicon-o-bookmark class="size-4" /> Sign in to save</a>
@endauth
