<?php

namespace App\Http\Controllers;

use App\Enums\Exam;
use App\Models\ExamTopic;
use App\Models\Question;
use App\Services\Seo\SeoManager;
use App\Support\RichText;

class ExamController extends Controller
{
    public function index(SeoManager $seo)
    {
        $seo->title('FRCS & FRCOphth Exam Preparation')
            ->description('Revision notes, single best answer MCQs, image-based questions, viva practice and exam tips for FRCOphth and FRCS Ophthalmology candidates.')
            ->breadcrumb('Exam preparation', route('exam.index'));

        $topics = ExamTopic::published()->withCount(['questions' => fn ($q) => $q->published()])->orderBy('sort_order')->get();
        $counts = Question::published()->selectRaw('type, count(*) as c')->groupBy('type')->pluck('c', 'type');

        return view('exam.index', [
            'topics' => $topics,
            'counts' => $counts,
            'exams' => collect(Exam::cases())->reject(fn ($e) => $e === Exam::All),
        ]);
    }

    public function topic(string $slug, SeoManager $seo)
    {
        $topic = ExamTopic::published()->where('slug', $slug)->with('seo')->firstOrFail();

        return $this->renderTopic($topic, $seo);
    }

    public function renderTopic(ExamTopic $topic, SeoManager $seo, bool $preview = false)
    {
        $seo->forModel($topic, 'article')
            ->breadcrumb('Exam preparation', route('exam.index'))
            ->breadcrumb($topic->title, $topic->url());
        if ($preview) {
            $seo->noindex();
        }

        return view('exam.topic', [
            'topic' => $topic,
            'content' => RichText::process($topic->body),
            'questions' => $topic->questions()->published()->with('media')->orderBy('id')->get(),
            'trackSubject' => ['type' => 'exam_topic', 'id' => $topic->id],
        ]);
    }

    public function practice(SeoManager $seo)
    {
        $seo->title('Question bank — practice mode')
            ->description('Practise FRCOphth and FRCS style questions with instant feedback and detailed explanations.')
            ->breadcrumb('Exam preparation', route('exam.index'))
            ->breadcrumb('Practice', route('exam.practice'));

        return view('exam.practice');
    }
}
