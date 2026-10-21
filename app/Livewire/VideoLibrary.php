<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Topic;
use App\Models\Video;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class VideoLibrary extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $category = '';

    #[Url(except: '')]
    public string $topic = '';

    #[Url(except: 'latest')]
    public string $sort = 'latest';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Video::query()->published()->with(['thumbnailMedia', 'category', 'topics']);

        if ($this->search !== '') {
            $term = '%'.addcslashes($this->search, '%_\\').'%';
            $query->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('summary', 'like', $term)->orWhere('transcript', 'like', $term));
        }
        if ($this->category !== '') {
            $query->whereHas('category', fn ($q) => $q->where('slug', $this->category));
        }
        if ($this->topic !== '' && ($topic = Topic::where('slug', $this->topic)->first())) {
            $query->inTopic($topic);
        }
        $this->sort === 'popular' ? $query->orderByDesc('plays_count')->orderByDesc('views_count') : $query->orderByDesc('published_at');

        $featured = $this->search === '' && $this->category === '' && $this->topic === '' && $this->getPage() === 1
            ? Video::published()->where('is_featured', true)->with(['thumbnailMedia', 'category'])->latest('published_at')->first()
            : null;

        return view('livewire.video-library', [
            'videos' => $query->when($featured, fn ($q) => $q->whereKeyNot($featured->id))->paginate(12),
            'featured' => $featured,
            'categories' => Category::ofType('video')->withCount(['videos' => fn ($q) => $q->published()])->get()->where('videos_count', '>', 0),
            'topics' => Topic::whereHas('videos', fn ($q) => $q->published())->orderBy('name')->get(),
        ]);
    }
}
