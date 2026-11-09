<?php

namespace App\Services\Popups;

/** Ready-made starting points for the popup editor. Every value can be changed afterwards. */
class PopupTemplates
{
    public static function all(): array
    {
        return [
            'newsletter' => [
                'label' => 'Newsletter signup',
                'description' => 'Centre popup with the signup form, after 40% of an article.',
                'values' => [
                    'type' => 'modal', 'position' => 'center', 'size' => 'md', 'color_scheme' => 'default', 'animation' => 'zoom',
                    'eyebrow' => 'Free weekly email', 'title' => 'Get the OCT case of the week',
                    'body' => '<p>One annotated case every week, with key findings and exam pearls. Join thousands of ophthalmologists.</p>',
                    'form' => 'newsletter', 'cta_label' => null, 'cta_url' => null, 'dismiss_label' => 'No thanks',
                    'trigger' => 'scroll', 'trigger_value' => 40, 'audience' => 'guests',
                    'frequency' => 'days', 'frequency_days' => 14, 'after_close_days' => 30, 'hide_after_conversion' => true,
                ],
            ],
            'course_launch' => [
                'label' => 'Course launch',
                'description' => 'Large popup with image and button, shown after a few seconds.',
                'values' => [
                    'type' => 'modal', 'position' => 'center', 'size' => 'lg', 'image_style' => 'side', 'color_scheme' => 'default', 'animation' => 'slide',
                    'eyebrow' => 'New course', 'title' => 'OCT Masterclass: from basics to biomarkers',
                    'body' => '<p>Twelve video lessons, quizzes and a certificate of completion.</p>',
                    'form' => 'none', 'cta_label' => 'See the course', 'cta_url' => '/courses', 'dismiss_label' => 'Maybe later',
                    'trigger' => 'delay', 'trigger_value' => 8, 'audience' => 'everyone',
                    'frequency' => 'days', 'frequency_days' => 7, 'after_close_days' => 14, 'hide_after_conversion' => true,
                ],
            ],
            'webinar' => [
                'label' => 'Webinar reminder',
                'description' => 'Corner slide-in for an upcoming live session.',
                'values' => [
                    'type' => 'slide_in', 'position' => 'bottom_right', 'size' => 'sm', 'color_scheme' => 'brand', 'animation' => 'slide',
                    'eyebrow' => 'Live webinar', 'title' => 'Uveitis grand rounds — this Thursday',
                    'body' => '<p>Join live and ask your questions to the panel.</p>',
                    'form' => 'none', 'cta_label' => 'Register free', 'cta_url' => '/webinars', 'dismiss_label' => null,
                    'trigger' => 'delay', 'trigger_value' => 5, 'audience' => 'everyone',
                    'frequency' => 'session', 'after_close_days' => 2, 'hide_after_conversion' => true,
                ],
            ],
            'membership_offer' => [
                'label' => 'Membership offer',
                'description' => 'Exit-intent offer for signed-in users without a membership.',
                'values' => [
                    'type' => 'modal', 'position' => 'bottom', 'size' => 'md', 'color_scheme' => 'accent', 'animation' => 'zoom',
                    'eyebrow' => 'Limited offer', 'title' => 'Unlock every case and course',
                    'body' => '<p>Full access to the case library, exam questions and all courses. Cancel any time.</p>',
                    'form' => 'none', 'cta_label' => 'View membership', 'cta_url' => '/membership', 'dismiss_label' => 'Not now',
                    'trigger' => 'exit', 'trigger_value' => null, 'audience' => 'non_members',
                    'frequency' => 'days', 'frequency_days' => 7, 'after_close_days' => 7, 'hide_after_conversion' => true,
                ],
            ],
            'announcement' => [
                'label' => 'Announcement bar',
                'description' => 'Slim bar above the header for news.',
                'values' => [
                    'type' => 'bar', 'position' => 'top', 'size' => 'md', 'color_scheme' => 'brand', 'animation' => 'none', 'show_overlay' => false,
                    'eyebrow' => null, 'title' => 'New: Retina course with certificate', 'body' => null,
                    'form' => 'none', 'cta_label' => 'Learn more', 'cta_url' => '/courses', 'dismiss_label' => null,
                    'trigger' => 'load', 'trigger_value' => null, 'audience' => 'everyone',
                    'frequency' => 'page', 'after_close_days' => 3, 'hide_after_conversion' => false,
                ],
            ],
            'notice' => [
                'label' => 'Important notice',
                'description' => 'Bottom bar for maintenance or service messages, shown on every page.',
                'values' => [
                    'type' => 'bar', 'position' => 'bottom', 'size' => 'md', 'color_scheme' => 'highlight', 'animation' => 'slide', 'show_overlay' => false,
                    'eyebrow' => null, 'title' => 'Scheduled maintenance on Sunday 06:00–07:00 (UK time).', 'body' => null,
                    'form' => 'none', 'cta_label' => null, 'cta_url' => null, 'dismiss_label' => null,
                    'trigger' => 'load', 'trigger_value' => null, 'audience' => 'everyone',
                    'frequency' => 'page', 'after_close_days' => 1, 'hide_after_conversion' => false,
                ],
            ],
            'welcome' => [
                'label' => 'Welcome for new visitors',
                'description' => 'Small slide-in on the first visit, pointing to the case library.',
                'values' => [
                    'type' => 'slide_in', 'position' => 'bottom_left', 'size' => 'sm', 'color_scheme' => 'default', 'animation' => 'slide',
                    'eyebrow' => 'Welcome', 'title' => 'New here? Start with the case library',
                    'body' => '<p>Hundreds of real OCT cases with step-by-step interpretation.</p>',
                    'form' => 'none', 'cta_label' => 'Browse cases', 'cta_url' => '/cases', 'dismiss_label' => null,
                    'trigger' => 'idle', 'trigger_value' => 20, 'audience' => 'guests', 'visitors' => 'new',
                    'frequency' => 'once', 'after_close_days' => null, 'hide_after_conversion' => true,
                ],
            ],
        ];
    }

    public static function options(): array
    {
        return array_map(fn ($t) => $t['label'], self::all());
    }

    public static function values(string $key): array
    {
        return (self::all()[$key]['values'] ?? []) + ['template' => $key];
    }
}
