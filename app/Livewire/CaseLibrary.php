<?php

namespace App\Livewire;

use App\Enums\Difficulty;
use App\Models\ClinicalCase;
use App\Models\Modality;
use App\Models\Topic;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CaseLibrary extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $topic = '';

    #[Url(except: '')]
    public string $modality = '';

    #[Url(except: '')]
    public string $difficulty = '';

    #[Url(except: false)]
    public bool $cotw = false;

    #[Url(except: 'latest')]
    public string $sort = 'latest';

    #[Url(except: 'grid')]
    public string $view = 'grid';

    public function updated($property): void
    {
        if ($property !== 'view') {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'topic', 'modality', 'difficulty', 'cotw', 'sort']);
        $this->resetPage();
    }

    public function render()
    {
        $query = ClinicalCase::query()->published()->with(['featuredMedia', 'topics', 'modalities']);

        if ($this->search !== '') {
            $term = '%'.addcslashes($this->search, '%_\\').'%';
            $query->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('diagnosis', 'like', $term)->orWhere('summary', 'like', $term)
                ->orWhereHas('topics', fn ($t) => $t->where('name', 'like', $term)->orWhere('abbreviation', 'like', $term)));
        }
        if ($this->topic !== '' && ($topic = Topic::where('slug', $this->topic)->first())) {
            $query->inTopic($topic);
        }
        if ($this->modality !== '') {
            $query->whereHas('modalities', fn ($q) => $q->where('slug', $this->modality));
        }
        if ($this->difficulty !== '' && Difficulty::tryFrom($this->difficulty)) {
            $query->where('difficulty', $this->difficulty);
        }
        if ($this->cotw) {
            $query->where('is_case_of_week', true);
        }

        match ($this->sort) {
            'popular' => $query->orderByDesc('views_count'),
            'oldest' => $query->orderBy('published_at'),
            'title' => $query->orderBy('title'),
            default => $query->orderByDesc('published_at'),
        };

        $topics = Topic::query()->whereNull('parent_id')->with(['children' => fn ($q) => $q->withCount(['cases' => fn ($c) => $c->published()])])
            ->withCount(['cases' => fn ($c) => $c->published()])->orderBy('sort_order')->orderBy('name')->get();

        return view('livewire.case-library', [
            'cases' => $query->paginate(12),
            'topics' => $topics,
            'modalities' => Modality::orderBy('sort_order')->get(),
            'difficulties' => Difficulty::cases(),
            'activeFilters' => collect([$this->search, $this->topic, $this->modality, $this->difficulty, $this->cotw ? '1' : ''])->filter()->count(),
        ]);
    }
}
