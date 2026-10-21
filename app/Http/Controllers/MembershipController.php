<?php

namespace App\Http\Controllers;

use App\Enums\AccessLevel;
use App\Models\Article;
use App\Models\ClinicalCase;
use App\Models\Course;
use App\Models\MembershipPlan;
use App\Models\Question;
use App\Models\Video;
use App\Models\Webinar;
use App\Services\Payments\StripeClient;
use App\Services\Seo\SeoManager;
use App\Support\Settings;
use Illuminate\Http\Request;

class MembershipController extends Controller
{
    public function show(Request $request, SeoManager $seo)
    {
        $seo->title(Settings::get('membership.heading') ?: 'Membership')
            ->description(Settings::get('membership.intro'))
            ->breadcrumb('Membership', route('membership'));

        $members = AccessLevel::Members->value;

        return view('membership.show', [
            'plans' => MembershipPlan::active()->get(),
            'subscription' => $request->user()?->activeSubscription(),
            'paymentsEnabled' => StripeClient::configured(),
            'included' => array_filter([
                'courses' => Course::published()->where('included_in_membership', true)->count(),
                'webinars' => Webinar::published()->where('free_for_members', true)->where('starts_at', '>', now())->count(),
                'cases' => ClinicalCase::published()->where('access_level', $members)->count(),
                'videos' => Video::published()->where('access_level', $members)->count(),
                'articles' => Article::published()->where('access_level', $members)->count(),
                'questions' => Question::published()->where('access_level', $members)->count(),
            ]),
            'courses' => Course::published()->where('included_in_membership', true)->with('featuredMedia')->latest('published_at')->limit(3)->get(),
            'faq' => collect(Settings::get('membership.faq') ?? [])->filter(fn ($f) => filled($f['question'] ?? null)),
        ]);
    }
}
