{{-- Announcement bars at the top of the page (Popups & announcements, type “bar”, position “top”). --}}
@foreach (($popupItems ?? collect())->filter(fn ($p) => $p->type === 'bar' && $p->position === 'top') as $popup)
    @include('popups.item', ['popup' => $popup])
@endforeach
