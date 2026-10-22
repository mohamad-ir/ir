<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** An image slider shown at a fixed place on the site (or anywhere through the page builder). */
class Banner extends Model
{
    use LogsActivity;

    public const PLACEMENTS = [
        'home' => 'Homepage — top',
        'cases' => 'OCT case library',
        'videos' => 'Video library',
        'articles' => 'Articles',
        'courses' => 'Courses page',
        'course' => 'Every course page',
        'webinars' => 'Webinars page',
        'galleries' => 'Galleries page',
        'membership' => 'Membership page',
    ];

    public const HEIGHTS = ['compact' => 'Compact', 'standard' => 'Standard', 'tall' => 'Tall'];

    protected $fillable = ['name', 'placement', 'height', 'transition', 'autoplay', 'interval', 'show_arrows', 'show_dots', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['autoplay' => 'boolean', 'show_arrows' => 'boolean', 'show_dots' => 'boolean', 'is_active' => 'boolean', 'interval' => 'integer'];
    }

    public function slides(): HasMany
    {
        return $this->hasMany(BannerSlide::class)->orderBy('sort_order')->orderBy('id');
    }

    /** Slides that are switched on and inside their schedule right now. */
    public function liveSlides()
    {
        $slides = $this->relationLoaded('slides') ? $this->slides : $this->slides()->with(['media', 'mobileMedia'])->get();

        return $slides->filter(fn (BannerSlide $s) => $s->isLive())->values();
    }

    /** The slider for a placement, or null when none is active or none of its slides is live. */
    public static function forPlacement(string $placement): ?self
    {
        $banner = static::where('placement', $placement)->where('is_active', true)->orderBy('sort_order')->orderBy('id')
            ->with(['slides.media', 'slides.mobileMedia'])->first();

        return $banner && $banner->liveSlides()->isNotEmpty() ? $banner : null;
    }
}
