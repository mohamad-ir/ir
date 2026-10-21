<?php

namespace App\Livewire;

use App\Models\Article;
use App\Models\Category;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ArticleIndex extends Component
{
    use WithPagination;

    public ?int $categoryId = null;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Article::query()->published()->with(['featuredMedia', 'category', 'author'])
            ->when($this->categoryId, fn ($q) => $q->where('category_id', $this->categoryId))
            ->when($this->search !== '', function ($q) {
                $term = '%'.addcslashes($this->search, '%_\\').'%';
                $q->where(fn ($w) => $w->where('title', 'like', $term)->orWhere('excerpt', 'like', $term));
            })
            ->orderByDesc('published_at');

        $showFeatured = ! $this->categoryId && $this->search === '' && $this->getPage() === 1;
        $featured = $showFeatured ? Article::published()->where('is_featured', true)->with(['featuredMedia', 'category', 'author'])->latest('published_at')->first() : null;

        return view('livewire.article-index', [
            'articles' => $query->when($featured, fn ($q) => $q->whereKeyNot($featured->id))->paginate(9),
            'featured' => $featured,
            'categories' => Category::ofType('article')->withCount(['articles' => fn ($q) => $q->published()])->get()->where('articles_count', '>', 0),
        ]);
    }
}
