@props(['course', 'enrolled' => false, 'isMember' => false])
<article {{ $attributes->merge(['class' => 'group card-interactive relative flex flex-col overflow-hidden']) }}>
    <div class="relative aspect-[16/9] overflow-hidden bg-ink">
        @if ($course->featuredMedia)
            <x-media-img :media="$course->featuredMedia" variant="md" sizes="(min-width: 1024px) 400px, 100vw" class="size-full object-cover opacity-90 transition duration-700 group-hover:scale-[1.035] group-hover:opacity-100" />
        @else
            <div class="bg-retina-lines absolute inset-0 opacity-30"></div>
            <div class="grid size-full place-items-center text-white/40"><x-heroicon-o-academic-cap class="size-12" /></div>
        @endif
        <div class="absolute left-3 top-3 flex flex-wrap gap-1.5">
            @if ($enrolled)
                <span class="rounded-full bg-accent px-2.5 py-0.5 text-[0.7rem] font-bold uppercase tracking-wider text-accent-contrast">Enrolled</span>
            @elseif ($course->isFree())
                <span class="rounded-full bg-white px-2.5 py-0.5 text-[0.7rem] font-bold uppercase tracking-wider text-ink">Free</span>
            @elseif ($course->included_in_membership)
                <span class="rounded-full bg-highlight px-2.5 py-0.5 text-[0.7rem] font-bold uppercase tracking-wider text-highlight-contrast">Included in membership</span>
            @endif
        </div>
    </div>
    <div class="flex flex-1 flex-col p-5">
        <div class="flex items-center justify-between gap-3 text-sm text-muted">
            <span>{{ $course->lessons->count() }} {{ \Illuminate\Support\Str::plural('lesson', $course->lessons->count()) }}@if($course->durationLabel()) · {{ $course->durationLabel() }}@endif</span>
            @if ($course->level)<x-difficulty-badge :level="$course->level" />@endif
        </div>
        <h3 class="mt-3 font-heading text-[1.35rem] font-semibold leading-snug text-ink">
            <a href="{{ $course->urlPath() }}" class="after:absolute after:inset-0 focus:outline-none">{{ $course->title }}</a>
        </h3>
        @if ($course->subtitle || $course->summary)
            <p class="mt-2 line-clamp-3 text-[0.9375rem] leading-relaxed text-muted">{{ $course->subtitle ?: $course->summary }}</p>
        @endif
        <div class="mt-auto flex items-center justify-between pt-5">
            <span class="font-heading text-xl font-semibold text-ink">
                @if ($enrolled || ($isMember && $course->included_in_membership))
                    <span class="text-base font-sans font-medium text-accent-ink">You have access</span>
                @else
                    {{ $course->priceLabel() }}
                @endif
            </span>
            <span class="link-arrow">{{ $enrolled ? 'Continue' : 'View course' }} <x-heroicon-m-arrow-right /></span>
        </div>
    </div>
</article>
