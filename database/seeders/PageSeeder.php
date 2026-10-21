<?php

namespace Database\Seeders;

use App\Enums\ContentStatus;
use App\Models\Page;
use App\Support\Settings;
use Illuminate\Database\Seeder;

/** Starter pages built with the page builder. Every block can be edited, reordered or removed in Design › Pages. */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        $home = $this->page('home', 'Home', false, [
            ['hero', [
                'layout' => 'centered', 'eyebrow' => 'Ophthalmology education',
                'heading' => 'Master *OCT* interpretation, one real case at a time',
                'subheading' => 'Case-based teaching in medical retina, OCT and uveitis for residents, fellows and ophthalmologists — with an FRCOphth and FRCS exam hub built in.',
                'show_search' => true,
                'buttons' => [['label' => 'Explore the case library', 'url' => '/cases', 'style' => 'primary'], ['label' => 'This week’s case', 'url' => '/case-of-the-week', 'style' => 'secondary']],
                'highlights' => ['De-identified teaching cases', 'Free to read', 'FRCOphth & FRCS focused'],
                '_tone' => 'default', '_spacing' => 'loose',
            ]],
            ['stats', ['items' => [
                ['live' => 'cases', 'label' => 'OCT teaching cases'], ['live' => 'videos', 'label' => 'Educational videos'],
                ['live' => 'articles', 'label' => 'Clinical articles'], ['live' => 'questions', 'label' => 'Exam questions'],
            ], '_tone' => 'default', '_spacing' => 'tight']],
            ['case_of_week', ['eyebrow' => 'Case of the Week', 'intro' => 'Study the images, commit to a diagnosis, then reveal the answer and teaching points.', 'button_label' => 'Attempt this case', '_tone' => 'ink']],
            ['content_feed', ['eyebrow' => 'Case library', 'heading' => 'Latest OCT cases', 'intro' => 'New cases are added every week — each with findings, differentials, discussion and pearls.', 'source' => 'cases', 'mode' => 'latest', 'limit' => 6, 'layout' => 'cards', 'view_all_label' => 'Browse all cases', 'view_all_url' => '/cases']],
            ['topics_grid', ['eyebrow' => 'Browse by disease', 'heading' => 'Find cases by topic', 'show_counts' => true, '_tone' => 'muted']],
            ['content_feed', ['eyebrow' => 'Video library', 'heading' => 'Watch & learn', 'source' => 'videos', 'mode' => 'latest', 'limit' => 3, 'layout' => 'cards', 'view_all_label' => 'All videos', 'view_all_url' => '/videos']],
            ['profile', ['eyebrow' => 'Your teacher', 'show_social' => true, 'buttons' => [['label' => 'Full biography', 'url' => '/about', 'style' => 'secondary'], ['label' => 'Invite to speak', 'url' => '/contact', 'style' => 'ghost']], '_tone' => 'surface']],
            ['content_feed', ['eyebrow' => 'Articles', 'heading' => 'Systematic approaches & reviews', 'source' => 'articles', 'mode' => 'featured', 'limit' => 4, 'layout' => 'feature', 'view_all_label' => 'All articles', 'view_all_url' => '/articles']],
            ['exam_hub', ['eyebrow' => 'Exam preparation', 'heading' => 'FRCS & FRCOphth preparation', 'intro' => 'Revision notes, single best answer questions, image-based questions and viva practice.', 'limit' => 6, '_tone' => 'accent']],
            ['courses', ['eyebrow' => 'Courses', 'heading' => 'Structured online courses', 'intro' => 'Self-paced programmes with video lessons, real cases and clear take-home messages.', 'mode' => 'featured', 'limit' => 3]],
            ['webinars', ['eyebrow' => 'Live teaching', 'heading' => 'Upcoming webinars', 'limit' => 2, '_tone' => 'muted']],
            ['newsletter', ['interests' => ['weekly_case'], 'style' => 'split', '_tone' => 'ink']],
        ]);

        $this->page('about', 'About', false, [
            ['profile', ['eyebrow' => 'About', 'bio' => '<p>Consultant ophthalmologist specialising in medical retina and uveitis, with a particular interest in multimodal imaging and OCT interpretation. Teaching has always been central to clinical practice — from the bedside and the OCT reading room to national courses and examinations.</p><p>This website brings that teaching online: real, de-identified cases presented the way they are met in clinic, with the reasoning made explicit.</p><p><em>Sample biography — replace this text in Design › Pages › About.</em></p>', 'show_social' => true, 'buttons' => [['label' => 'Collaboration & speaking', 'url' => '/contact', 'style' => 'primary']]]],
            ['credentials', ['heading' => 'Education & training', 'types' => ['education', 'fellowship'], 'layout' => 'timeline', '_tone' => 'surface']],
            ['credentials', ['heading' => 'Clinical & surgical experience', 'types' => ['appointment', 'surgical'], 'layout' => 'cards']],
            ['publications', ['heading' => 'Publications', 'intro' => 'Peer-reviewed articles, books and chapters.', 'types' => ['publication', 'book'], 'searchable' => true, '_tone' => 'surface']],
            ['credentials', ['heading' => 'Lectures & teaching', 'types' => ['lecture', 'teaching'], 'layout' => 'list']],
            ['credentials', ['heading' => 'Awards & memberships', 'types' => ['award', 'membership'], 'layout' => 'cards', '_tone' => 'muted']],
            ['cta', ['heading' => 'Invite me to speak or collaborate', 'text' => 'Grand rounds, national meetings, teaching programmes and research collaborations.', 'buttons' => [['label' => 'Get in touch', 'url' => '/contact', 'style' => 'primary']], '_tone' => 'ink']],
        ]);

        $this->page('contact', 'Contact', false, [
            ['contact_form', ['heading' => 'Collaboration & professional enquiries', 'text' => 'For scientific collaboration, invitations to speak or teach, research partnerships and media enquiries. Please include dates, audience and format for speaking invitations.', 'default_type' => 'collaboration', '_spacing' => 'loose']],
        ]);

        $legal = '<p><em>Template text — please review it with your data-protection adviser and adapt it to your practice before launch.</em></p>';

        $this->page('privacy', 'Privacy policy', true, [['rich_text', ['content' => $legal.'
<h2>Who we are</h2><p>This website is an educational resource for healthcare professionals. The data controller is the website owner named on the About page. Contact us using the contact form for any privacy request.</p>
<h2>What we collect</h2><ul><li><strong>Account data</strong> — name, email address, career stage, institution and country when you create an account.</li><li><strong>Newsletter data</strong> — your email address, the topics you chose and the date you confirmed your subscription.</li><li><strong>Enquiries</strong> — the details you send through the contact form.</li><li><strong>Purchases</strong> — when you buy a course, ticket or membership: your name, email, billing country, VAT number (if given), what you bought and the amount. Card details are handled by our payment processor Stripe and never reach this website. Order records are kept for the period required by tax law.</li><li><strong>Usage statistics</strong> — anonymous, cookieless page-view statistics. We store an irreversible daily-rotating hash instead of your IP address.</li></ul>
<h2>Cookies</h2><p>We use only essential cookies (session and security). Google Analytics is loaded only if you accept it in the cookie banner.</p>
<h2>How long we keep data</h2><p>Account data is kept until you delete your account. Statistics are kept for up to two years. You can unsubscribe from emails at any time with one click.</p>
<h2>Your rights</h2><p>You can download or delete your data at any time from <strong>My learning › Settings</strong>, or contact us to exercise your rights of access, correction, objection and erasure under UK GDPR / EU GDPR.</p>
<h2>Patient information</h2><p>All clinical images and case descriptions are de-identified before publication. Please never send identifiable patient information through this website.</p>']]]);

        $this->page('terms', 'Terms of use', true, [['rich_text', ['content' => $legal.'
<h2>Educational purpose</h2><p>All material is provided for the education of healthcare professionals and does not replace clinical judgement, local guidelines or specialist advice.</p>
<h2>Accounts</h2><p>You are responsible for keeping your login details confidential. We may suspend accounts that misuse the service.</p>
<h2>Intellectual property</h2><p>Text, images and videos are protected by copyright. You may share links and use material for personal study and non-commercial teaching with attribution. Please ask before republishing.</p>
<h2>No medical advice</h2><p>We cannot provide opinions on individual patients through this website.</p>']]]);

        $this->page('disclaimer', 'Medical disclaimer', true, [['rich_text', ['content' => '<p>'.e(Settings::get('site.medical_disclaimer')).'</p><p>Case images are de-identified and published with appropriate consent or under institutional approval for teaching. Treatment information reflects published evidence at the time of writing and may change; always consult current guidelines and product information.</p>']]]);

        $this->page('terms-of-sale', 'Terms of sale', true, [['rich_text', ['content' => \App\Support\LegalTemplates::termsOfSale()]]]);
        $this->page('refund-policy', 'Refund policy', true, [['rich_text', ['content' => \App\Support\LegalTemplates::refundPolicy()]]]);

        if (! Settings::get('site.homepage_id')) {
            Settings::set('site.homepage_id', $home->id);
        }
        if (! Settings::get('site.about_page_id')) {
            Settings::set('site.about_page_id', Page::where('slug', 'about')->value('id'));
        }
    }

    protected function page(string $slug, string $title, bool $showTitle, array $blocks): Page
    {
        return Page::withoutRevisions(fn () => Page::firstOrCreate(['slug' => $slug], [
            'title' => $title,
            'show_title' => $showTitle,
            'blocks' => collect($blocks)->map(fn ($b) => ['type' => $b[0], 'data' => $b[1]])->all(),
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ]));
    }
}
