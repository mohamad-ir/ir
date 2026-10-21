@if (($data['courses'] ?? collect())->isNotEmpty())
    <x-section-heading :eyebrow="$data['eyebrow'] ?? null" :heading="$data['heading'] ?? null" :intro="$data['intro'] ?? null" action-label="All courses" :action-url="route('courses.index')" />
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($data['courses'] as $course)
            <x-course-card :course="$course" :is-member="$data['is_member'] ?? false" />
        @endforeach
    </div>
@elseif ($context['preview'] ?? false)
    <p class="rounded-ui bg-subtle p-6 text-center text-sm text-muted">Courses section — publish a course to show it here.</p>
@endif
