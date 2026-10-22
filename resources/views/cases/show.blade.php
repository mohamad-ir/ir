@extends('layouts.app')
@php
    use App\Support\RichText;
    $images = $case->imageItems();
    $comparison = $case->comparisonItems();
    $viewerImages = $images->map(fn ($i) => [
        'src' => $i['media']->url('lg'), 'full' => $i['media']->url(),
        'caption' => $i['caption'] ?? $i['media']->caption, 'label' => $i['label'] ?? null, 'alt' => $i['media']->altText(),
    ])->values();
    $refs = $case->references ?? [];
    $sections = collect([
        'history' => 'Clinical history',
        'imaging' => $images->isNotEmpty() ? 'Imaging' : null,
        'challenge' => 'Your diagnosis',
        'answer' => 'Diagnosis',
        'findings' => filled($case->findings) ? 'Findings' : null,
        'differentials' => ! empty($case->differentials) ? 'Differential diagnosis' : null,
        'discussion' => filled($case->discussion) ? 'Discussion' : null,
        'management' => filled($case->management) ? 'Management' : null,
        'pearls' => ! empty($case->teaching_points) ? 'Teaching points' : null,
        'references' => collect($refs)->filter(fn ($r) => filled($r['citation'] ?? null))->isNotEmpty() ? 'References' : null,
    ])->filter();
    $answerSections = ['answer', 'findings', 'differentials', 'discussion', 'management', 'pearls', 'references'];
    $options = collect($case->quiz_options ?? [])->filter(fn ($o) => filled($o['text'] ?? null))->values();
@endphp

@push('head')<noscript><style>[x-cloak]{display:block!important}</style></noscript>@endpush

@section('content')
<div x-data="caseQuiz({{ $case->id }}, 'case-{{ $case->id }}-v{{ $case->updated_at?->timestamp }}')">
    {{-- Header --}}
    <header class="relative overflow-hidden border-b border-line bg-surface">
        <div class="bg-retina-lines absolute inset-0 opacity-60" aria-hidden="true"></div>
        <div class="container-site relative pt-8 pb-10 sm:pt-10 sm:pb-12">
            @if ($isPreview)
                <div class="mb-6 rounded-ui bg-highlight/15 px-4 py-2.5 text-sm font-medium text-ink ring-1 ring-highlight/30">Preview — status: {{ $case->displayStatus() }}. This page is not visible to the public{{ $case->isPublished() ? '' : ' yet' }}.</div>
            @endif
            <x-breadcrumbs :items="array_filter(['OCT cases' => route('cases.index'), ($case->topics->first()?->name ?? '') => $case->topics->first()?->url(), $case->title => null])" />
            <div class="mt-6 flex flex-wrap items-center gap-2">
                @if ($badge = $case->weeklyBadge())
                    <span class="inline-flex items-center gap-1 rounded-full bg-highlight px-2.5 py-0.5 text-xs font-bold uppercase tracking-wider text-highlight-contrast"><x-heroicon-m-sparkles class="size-3.5" /> {{ $badge }}</span>
                @endif
                @foreach ($case->topics as $topic)
                    <a href="{{ $topic->urlPath() }}" class="chip chip-accent hover:bg-accent/20">{{ $topic->name }}</a>
                @endforeach
                @foreach ($case->modalities as $m)
                    <span class="chip">{{ $m->label() }}</span>
                @endforeach
            </div>
            <h1 class="mt-4 max-w-4xl font-heading text-4xl font-medium leading-[1.1] text-ink sm:text-5xl">{{ $case->title }}</h1>
            @if ($case->summary)
                <p class="lead mt-4 max-w-3xl">{{ $case->summary }}</p>
            @endif
            <div class="mt-6 flex flex-wrap items-center gap-x-6 gap-y-3 text-sm text-muted">
                <x-difficulty-badge :level="$case->difficulty" />
                @if ($case->published_at)<span class="inline-flex items-center gap-1.5"><x-heroicon-o-calendar class="size-4" />{{ $case->published_at->format('j F Y') }}</span>@endif
                @if ($case->author)<span class="inline-flex items-center gap-1.5"><x-heroicon-o-user class="size-4" />{{ $case->author->displayName() }}</span>@endif
                @if ($case->guesses_count >= 5)
                    <span class="inline-flex items-center gap-1.5"><x-heroicon-o-chart-bar class="size-4" />{{ round($case->correct_guesses_count / max(1, $case->guesses_count) * 100) }}% of {{ number_format($case->guesses_count) }} readers answered correctly</span>
                @endif
            </div>
        </div>
    </header>

    <div class="container-site grid gap-12 py-10 lg:grid-cols-[minmax(0,1fr)_17rem] lg:py-14">
        <article class="min-w-0">
            {{-- History --}}
            <section id="history" data-toc-section class="scroll-mt-28">
                <h2 class="eyebrow">Clinical history</h2>
                <div class="card mt-4 p-6 sm:p-8">
                    @if ($case->patient_age || $case->patient_sex || $case->eye)
                        <dl class="mb-5 flex flex-wrap gap-2">
                            @if ($case->patient_age)<div class="chip chip-outline !py-1 !text-sm"><dt class="sr-only">Age</dt><dd>{{ $case->patient_age }}</dd></div>@endif
                            @if ($case->patient_sex)<div class="chip chip-outline !py-1 !text-sm"><dt class="sr-only">Sex</dt><dd>{{ $case->patient_sex }}</dd></div>@endif
                            @if ($case->eye)<div class="chip chip-outline !py-1 !text-sm"><dt class="sr-only">Eye</dt><dd>{{ ['OD' => 'Right eye (OD)', 'OS' => 'Left eye (OS)', 'OU' => 'Both eyes (OU)'][$case->eye] ?? $case->eye }}</dd></div>@endif
                        </dl>
                    @endif
                    @if ($case->presenting_complaint)
                        <p class="font-heading text-xl leading-snug text-ink">{{ $case->presenting_complaint }}</p>
                    @endif
                    @if (filled($case->history))
                        <div class="prose prose-lg prose-clinical mt-4">{!! RichText::render($case->history, $refs) !!}</div>
                    @endif
                </div>
            </section>

            {{-- Imaging --}}
            @if ($images->isNotEmpty())
                <section id="imaging" data-toc-section class="mt-12 scroll-mt-28" x-data="caseViewer(@js($viewerImages))" x-on:keydown.window="key($event)">
                    <div class="flex items-end justify-between gap-4">
                        <h2 class="eyebrow">Imaging</h2>
                        <p class="hidden text-xs text-muted sm:block">Click an image to zoom, adjust contrast or invert</p>
                    </div>
                    <div class="mt-4 space-y-4" x-data="{ active: 0 }">
                        <figure class="viewer-frame group">
                            @foreach ($images as $i => $item)
                                <button type="button" x-show="active === {{ $i }}" @if($i > 0) x-cloak @endif x-on:click="show({{ $i }})" class="relative block w-full cursor-zoom-in" aria-label="Open image {{ $i + 1 }} in viewer">
                                    <x-media-img :media="$item['media']" variant="lg" sizes="(min-width: 1024px) 860px, 100vw" class="mx-auto max-h-[36rem] w-full object-contain" :eager="$i === 0" />
                                    <span class="absolute right-3 top-3 inline-flex items-center gap-1.5 rounded-full bg-black/60 px-3 py-1.5 text-xs font-medium text-white opacity-0 backdrop-blur transition group-hover:opacity-100"><x-heroicon-m-arrows-pointing-out class="size-3.5" /> Expand</span>
                                    @if (! empty($item['label']))
                                        <span class="absolute left-3 top-3 rounded bg-black/60 px-2 py-1 text-xs font-semibold tracking-wide text-white backdrop-blur">{{ $item['label'] }}</span>
                                    @endif
                                </button>
                            @endforeach
                            @foreach ($images as $i => $item)
                                @if ($item['caption'] ?? $item['media']->caption)
                                    <figcaption x-show="active === {{ $i }}" @if($i > 0) x-cloak @endif class="border-t border-white/10 px-5 py-3.5 text-sm leading-relaxed text-white/75">
                                        <span class="font-semibold text-white">Figure {{ $i + 1 }}.</span> {{ $item['caption'] ?? $item['media']->caption }}
                                        @if ($item['media']->credit)<span class="text-white/45"> — {{ $item['media']->credit }}</span>@endif
                                    </figcaption>
                                @endif
                            @endforeach
                        </figure>
                        @if ($images->count() > 1)
                            <div class="flex gap-3 overflow-x-auto pb-1 scrollbar-none" role="tablist" aria-label="Case images">
                                @foreach ($images as $i => $item)
                                    <button type="button" x-on:click="active = {{ $i }}" role="tab" :aria-selected="active === {{ $i }}"
                                            class="viewer-frame relative aspect-[4/3] w-28 shrink-0 ring-2 transition sm:w-32" :class="active === {{ $i }} ? 'ring-accent' : 'ring-transparent opacity-60 hover:opacity-100'">
                                        <img src="{{ $item['media']->thumbUrl() }}" alt="" class="size-full object-cover" loading="lazy">
                                        <span class="absolute bottom-1 left-1 rounded bg-black/70 px-1.5 text-[0.65rem] font-semibold text-white">{{ $item['label'] ?? 'Fig. '.($i + 1) }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @include('cases.partials.lightbox')
                </section>
            @endif

            @if ($comparison)
                <section class="mt-10">
                    <h2 class="eyebrow">Comparison</h2>
                    <div class="mt-4" x-data="compareSlider()">
                        <div x-ref="frame" class="viewer-frame relative aspect-[16/9] select-none overflow-hidden" x-on:mousedown="dragging = true; set($event)" x-on:mousemove.window="dragging && set($event)" x-on:mouseup.window="dragging = false" x-on:touchstart.passive="set($event)" x-on:touchmove.passive="set($event)">
                            <x-media-img :media="$comparison['after']" variant="lg" sizes="(min-width:1024px) 860px, 100vw" class="absolute inset-0 size-full object-contain" />
                            <div class="absolute inset-0 overflow-hidden" :style="`clip-path: inset(0 ${100 - pos}% 0 0)`">
                                <x-media-img :media="$comparison['before']" variant="lg" sizes="(min-width:1024px) 860px, 100vw" class="absolute inset-0 size-full object-contain" />
                            </div>
                            <div class="absolute inset-y-0 w-0.5 bg-white/90 shadow-[0_0_0_1px_rgba(0,0,0,.2)]" :style="`left: ${pos}%`">
                                <button type="button" class="absolute top-1/2 left-1/2 grid size-10 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full bg-white text-ink shadow-lg" x-on:keydown="keys($event)" aria-label="Drag to compare" role="slider" :aria-valuenow="Math.round(pos)" aria-valuemin="0" aria-valuemax="100">
                                    <x-heroicon-m-arrows-right-left class="size-5" />
                                </button>
                            </div>
                            <span class="absolute left-3 top-3 rounded bg-black/60 px-2 py-1 text-xs font-semibold text-white">{{ $comparison['before_label'] }}</span>
                            <span class="absolute right-3 top-3 rounded bg-black/60 px-2 py-1 text-xs font-semibold text-white">{{ $comparison['after_label'] }}</span>
                        </div>
                        @if ($comparison['caption'])<p class="mt-3 text-sm text-muted">{{ $comparison['caption'] }}</p>@endif
                    </div>
                </section>
            @endif

            @if (! empty($case->videos))
                <section class="mt-10" aria-label="Case videos">
                    @foreach ($case->videos as $video)
                        {!! \App\Support\VideoEmbed::html($video) !!}
                    @endforeach
                </section>
            @endif

            @if (! $canView)
                <div class="mt-12 overflow-hidden rounded-card bg-ink p-8 text-ink-contrast sm:p-12">
                    <x-locked-content :content="$case" thing="case discussion" />
                </div>
            @else
                {{-- Diagnostic challenge --}}
                <section id="challenge" data-toc-section class="mt-12 scroll-mt-28">
                    <div class="relative overflow-hidden rounded-card bg-ink p-6 text-ink-contrast sm:p-8">
                        <svg class="pointer-events-none absolute -right-10 -top-10 size-56 text-white/[.04]" viewBox="0 0 100 100" aria-hidden="true"><circle cx="50" cy="50" r="48" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="50" cy="50" r="30" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="50" cy="50" r="12" fill="currentColor"/></svg>
                        <p class="eyebrow !text-white/60">Your diagnosis</p>
                        <h2 class="mt-2 font-heading text-2xl font-semibold text-white sm:text-3xl">{{ $case->quiz_prompt ?: 'What is the most likely diagnosis?' }}</h2>

                        @if ($options->count() >= 2)
                            <fieldset class="mt-6 grid gap-2.5 sm:grid-cols-2" :disabled="submitted">
                                <legend class="sr-only">Choose one diagnosis</legend>
                                @foreach ($options as $i => $option)
                                    <label class="relative flex cursor-pointer items-center gap-3 rounded-ui bg-white/[.06] px-4 py-3.5 ring-1 ring-white/10 transition hover:bg-white/10"
                                           :class="{
                                               '!ring-2 !ring-[color-mix(in_oklab,var(--color-accent)_60%,white)] !bg-white/[.12]': selected === {{ $i }} && !submitted,
                                               '!bg-emerald-500/20 !ring-2 !ring-emerald-400': submitted && {{ ! empty($option['is_correct']) ? 'true' : 'false' }},
                                               '!bg-red-500/20 !ring-2 !ring-red-400': submitted && selected === {{ $i }} && {{ empty($option['is_correct']) ? 'true' : 'false' }},
                                               'opacity-60': submitted && selected !== {{ $i }} && {{ empty($option['is_correct']) ? 'true' : 'false' }}
                                           }">
                                        <input type="radio" name="case-guess" value="{{ $i }}" x-model.number="selected" class="sr-only">
                                        <span class="grid size-7 shrink-0 place-items-center rounded-full bg-white/10 text-sm font-bold">{{ chr(65 + $i) }}</span>
                                        <span class="font-medium">{{ $option['text'] }}</span>
                                        <template x-if="submitted && {{ ! empty($option['is_correct']) ? 'true' : 'false' }}"><x-heroicon-m-check-circle class="ml-auto size-6 text-emerald-300" /></template>
                                    </label>
                                @endforeach
                            </fieldset>
                            <div class="mt-6 flex flex-wrap items-center gap-3" x-show="!submitted">
                                <button type="button" class="btn btn-light" :disabled="selected === null" x-on:click="submit([{{ $options->map(fn ($o) => ! empty($o['is_correct']) ? 'true' : 'false')->implode(',') }}][selected])">Submit my answer</button>
                                <button type="button" class="btn text-white/75 hover:text-white" x-on:click="reveal()" x-show="!revealed">Skip and reveal</button>
                            </div>
                            <div x-cloak x-show="submitted" class="mt-6 flex items-center gap-3 rounded-ui px-4 py-3" :class="correct ? 'bg-emerald-500/15 text-emerald-100' : 'bg-red-500/15 text-red-100'" role="status">
                                <span class="text-lg font-semibold" x-text="correct ? 'Correct — well done.' : 'Not this time.'"></span>
                                <span class="text-sm opacity-80">Read the explanation below.</span>
                            </div>
                        @else
                            <p class="mt-4 max-w-2xl text-white/70">Study the history and imaging. Describe the key findings and commit to a diagnosis and differential before revealing the answer.</p>
                            <button type="button" x-show="!revealed" class="btn btn-light mt-6" x-on:click="reveal()"><x-heroicon-o-eye /> Reveal the answer</button>
                        @endif
                        <button type="button" x-cloak x-show="revealed" x-on:click="restart()" class="mt-5 inline-flex items-center gap-1.5 text-sm text-white/60 hover:text-white"><x-heroicon-m-arrow-path class="size-4" /> Try again</button>
                    </div>
                </section>

                {{-- Answer (revealed) --}}
                <div x-cloak x-show="revealed" x-transition:enter="transition duration-500 ease-out" x-transition:enter-start="opacity-0 translate-y-3">
                    <section id="answer" data-toc-section class="mt-12 scroll-mt-28">
                        <h2 class="eyebrow">Diagnosis</h2>
                        <div class="mt-4 rounded-card border-l-4 border-accent bg-surface p-6 shadow-[var(--shadow-card)] ring-1 ring-line sm:p-8">
                            <p class="font-heading text-3xl font-semibold leading-tight text-ink">{{ $case->diagnosis }}</p>
                            @if (filled($case->diagnosis_explanation))
                                <div class="prose prose-lg prose-clinical mt-4">{!! RichText::render($case->diagnosis_explanation, $refs) !!}</div>
                            @endif
                        </div>
                    </section>

                    @if (filled($case->findings))
                        <section id="findings" data-toc-section class="mt-12 scroll-mt-28">
                            <h2 class="font-heading text-2xl font-semibold text-ink">Findings</h2>
                            <div class="prose prose-lg prose-clinical mt-4" data-toc-root>{!! RichText::render($case->findings, $refs) !!}</div>
                        </section>
                    @endif

                    @if (! empty($case->differentials))
                        <section id="differentials" data-toc-section class="mt-12 scroll-mt-28">
                            <h2 class="font-heading text-2xl font-semibold text-ink">Differential diagnosis</h2>
                            <div class="mt-5 overflow-hidden rounded-card ring-1 ring-line">
                                <table class="w-full text-left text-[0.95rem]">
                                    <thead class="bg-subtle text-xs uppercase tracking-wider text-muted">
                                        <tr><th scope="col" class="px-5 py-3 font-semibold">Diagnosis</th><th scope="col" class="px-5 py-3 font-semibold">Distinguishing features</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-line bg-surface">
                                        @foreach ($case->differentials as $d)
                                            <tr class="align-top">
                                                <th scope="row" class="w-1/3 px-5 py-4 font-semibold text-ink">{{ $d['name'] ?? '' }}</th>
                                                <td class="px-5 py-4 leading-relaxed text-fg/85">{{ $d['features'] ?? '' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    @endif

                    @if (filled($case->discussion))
                        <section id="discussion" data-toc-section class="mt-12 scroll-mt-28">
                            <h2 class="font-heading text-2xl font-semibold text-ink">Discussion</h2>
                            <div class="prose prose-lg prose-clinical mt-4" data-toc-root>{!! RichText::render($case->discussion, $refs) !!}</div>
                        </section>
                    @endif

                    @if (filled($case->management))
                        <section id="management" data-toc-section class="mt-12 scroll-mt-28">
                            <h2 class="font-heading text-2xl font-semibold text-ink">Management &amp; outcome</h2>
                            <div class="prose prose-lg prose-clinical mt-4">{!! RichText::render($case->management, $refs) !!}</div>
                        </section>
                    @endif

                    @if (! empty($case->teaching_points))
                        <section id="pearls" data-toc-section class="mt-12 scroll-mt-28">
                            <h2 class="font-heading text-2xl font-semibold text-ink">Teaching points &amp; clinical pearls</h2>
                            <ol class="mt-5 grid gap-3 sm:grid-cols-2" role="list">
                                @foreach ($case->teaching_points as $i => $point)
                                    <li class="flex gap-4 rounded-card bg-accent/[.06] p-5 ring-1 ring-accent/15">
                                        <span class="grid size-8 shrink-0 place-items-center rounded-full bg-accent font-heading text-sm font-bold text-accent-contrast">{{ $i + 1 }}</span>
                                        <p class="leading-relaxed text-fg">{{ $point['point'] ?? '' }}</p>
                                    </li>
                                @endforeach
                            </ol>
                        </section>
                    @endif

                    <x-references :references="$refs" />
                </div>
            @endif

            <div class="mt-14 flex flex-col gap-6 border-t border-line pt-8 sm:flex-row sm:items-center sm:justify-between">
                <x-share-buttons :url="$case->url()" :title="$case->title" />
                @include('partials.bookmark', ['type' => 'case', 'model' => $case])
            </div>

            @if ($case->tags->isNotEmpty())
                <div class="mt-6 flex flex-wrap gap-2">
                    @foreach ($case->tags as $tag)<a href="{{ route('search', ['q' => $tag->name]) }}" class="chip chip-outline hover:text-ink">#{{ $tag->name }}</a>@endforeach
                </div>
            @endif

            @if ($canView && $case->commentsVisible() && ($case->commentsOpen() || $case->comments_count))
                <div class="mt-12" x-data="{ show: window.location.hash.startsWith('#comment') }">
                    <button type="button" x-show="! show" x-on:click="show = true" class="flex w-full items-center justify-between gap-4 rounded-card bg-subtle p-5 text-left ring-1 ring-line transition hover:ring-accent/40">
                        <span>
                            <span class="flex items-center gap-2 font-heading text-lg font-semibold text-ink"><x-heroicon-o-chat-bubble-left-right class="size-5 text-accent-ink" /> Discussion{{ $case->comments_count ? ' ('.$case->comments_count.')' : '' }}</span>
                            <span class="mt-0.5 block text-sm text-muted">Comments may reveal the diagnosis — read them after working through the case.</span>
                        </span>
                        <span class="btn btn-secondary btn-sm shrink-0">Show discussion</span>
                    </button>
                    <div x-show="show" x-cloak>
                        <livewire:comments :item="$case" />
                    </div>
                </div>
            @endif

            <nav class="mt-10 grid gap-4 sm:grid-cols-2" aria-label="More cases">
                @if ($previous)
                    <a href="{{ $previous->urlPath() }}" class="card-interactive p-5"><span class="text-xs font-semibold uppercase tracking-wider text-muted">← Previous case</span><span class="mt-1 block font-heading text-lg font-semibold text-ink">{{ $previous->title }}</span></a>
                @else <span></span> @endif
                @if ($next)
                    <a href="{{ $next->urlPath() }}" class="card-interactive p-5 text-right"><span class="text-xs font-semibold uppercase tracking-wider text-muted">Next case →</span><span class="mt-1 block font-heading text-lg font-semibold text-ink">{{ $next->title }}</span></a>
                @endif
            </nav>
        </article>

        {{-- Sidebar --}}
        <aside class="hidden lg:block">
            <div class="sticky top-24 space-y-8">
                <nav x-data="tocSpy()" aria-label="Case sections">
                    <p class="text-xs font-semibold uppercase tracking-[.14em] text-muted">In this case</p>
                    <ol class="mt-3 space-y-0.5 border-l border-line" role="list">
                        @foreach ($sections as $id => $label)
                            <li @if (in_array($id, $answerSections)) x-show="revealed" x-cloak @endif>
                                <a href="#{{ $id }}" class="-ml-px block border-l-2 py-1.5 pl-4 text-sm transition" :class="active === '{{ $id }}' ? 'border-accent font-semibold text-ink' : 'border-transparent text-muted hover:text-ink'">{{ $label }}</a>
                            </li>
                        @endforeach
                        <li x-show="!revealed" class="py-1.5 pl-4 text-sm text-muted/70"><x-heroicon-m-lock-closed class="mr-1 inline size-3.5" />Answer hidden until revealed</li>
                    </ol>
                </nav>

                @if ($case->topics->isNotEmpty())
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[.14em] text-muted">Explore topic</p>
                        <div class="mt-3 space-y-2">
                            @foreach ($case->topics as $topic)
                                <a href="{{ $topic->urlPath() }}" class="flex items-center justify-between rounded-ui bg-surface px-3.5 py-2.5 text-sm font-medium ring-1 ring-line transition hover:ring-line-strong">{{ $topic->name }} <x-heroicon-m-arrow-right class="size-4 text-muted" /></a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="rounded-card bg-accent/[.07] p-5 ring-1 ring-accent/15">
                    <p class="font-heading text-lg font-semibold text-ink">A new case every week</p>
                    <p class="mt-1 text-sm text-muted">Get the Case of the Week by email.</p>
                    <livewire:newsletter-form variant="footer" :compact="true" source="case-sidebar" />
                </div>
            </div>
        </aside>
    </div>

    @if ($related->isNotEmpty())
        <section class="border-t border-line bg-subtle">
            <div class="container-site section">
                <x-section-heading eyebrow="Keep learning" heading="Related cases" action-label="All cases" :action-url="route('cases.index')" />
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($related as $item)<x-case-card :case="$item" />@endforeach
                </div>
            </div>
        </section>
    @endif
</div>
@endsection
