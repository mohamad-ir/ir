<?php

namespace App\Support;

use Illuminate\Support\Arr;

class SettingDefaults
{
    public static function all(): array
    {
        return [
            'site' => [
                'name' => 'OCT Academy',
                'tagline' => 'Retina, OCT & uveitis education for ophthalmologists',
                'description' => 'Case-based ophthalmology education: OCT interpretation, retina, uveitis and FRCOphth / FRCS exam preparation for residents, fellows and ophthalmologists.',
                'homepage_id' => null,
                'about_page_id' => null,
                'contact_email' => null,
                'notification_email' => null,
                'timezone' => 'Europe/London',
                'medical_disclaimer' => 'Content on this website is for the education of medical professionals only. It does not constitute medical advice, and no doctor–patient relationship is created by its use. Images are de-identified and published for teaching purposes.',
                'registration_enabled' => true,
                'cookie_banner' => true,
            ],
            'doctor' => [
                'name' => 'Dr Alex Morgan',
                'credentials' => 'MD, FRCOphth',
                'role' => 'Consultant Ophthalmologist · Medical Retina & Uveitis',
                'short_bio' => 'Consultant ophthalmologist specialising in medical retina, OCT interpretation and uveitis, with a long-standing commitment to teaching residents and fellows.',
                'photo_id' => null,
                'same_as' => [],
            ],
            'branding' => [
                'logo_id' => null,
                'logo_dark_id' => null,
                'favicon_id' => null,
                'logo_text' => null,
                'og_image_id' => null,
            ],
            'theme' => [
                'primary' => '#12324f',
                'accent' => '#0f8b8d',
                'highlight' => '#d9822b',
                'background' => '#fbfaf7',
                'surface' => '#ffffff',
                'text' => '#1c2733',
                'heading_font' => 'newsreader',
                'body_font' => 'inter',
                'base_size' => 17,
                'radius' => 'md',
                'density' => 'comfortable',
                'button_style' => 'rounded',
                'header_style' => 'light',
                'header_sticky' => true,
                'footer_style' => 'dark',
                'container' => 'standard',
                'custom_css' => null,
            ],
            'header' => [
                'show_search' => true,
                'show_account' => true,
                'announcement' => null,
                'announcement_url' => null,
                'announcement_enabled' => false,
            ],
            'footer' => [
                'about' => 'An independent educational resource for ophthalmologists in training and practice, focused on OCT, medical retina and uveitis.',
                'show_newsletter' => true,
                'copyright' => '© {year} {site}. All rights reserved.',
            ],
            'social' => [
                'linkedin' => null, 'x' => null, 'youtube' => null, 'instagram' => null,
                'researchgate' => null, 'orcid' => null, 'google_scholar' => null, 'facebook' => null,
            ],
            'seo' => [
                'title_template' => '%title% | %site%',
                'home_title' => null,
                'default_noindex' => false,
                'robots_extra' => null,
                'google_verification' => null,
                'bing_verification' => null,
                'twitter_handle' => null,
                'image_alt_fallback' => 'title',
                'schema_person' => true,
            ],
            'analytics' => [
                'enabled' => true,
                'retention_days' => 730,
                'exclude_staff' => true,
                'ga4_measurement_id' => null,
                'ga4_property_id' => null,
                'gsc_site_url' => null,
                'google_credentials' => null,
            ],
            'newsletter' => [
                'enabled' => true,
                'double_opt_in' => true,
                'from_name' => null,
                'from_email' => null,
                'send_rate_per_minute' => 20,
                'signup_heading' => 'Receive a new OCT case every week',
                'signup_text' => 'One carefully selected teaching case in your inbox each week — with the answer one click away. No spam; unsubscribe any time.',
                'track_opens' => true,
            ],
            'mail' => [
                'mailer' => null, 'host' => null, 'port' => 465, 'username' => null, 'password' => null,
                'encryption' => 'ssl', 'from_address' => null, 'from_name' => null,
            ],
            'security' => [
                'require_staff_mfa' => true,
                'turnstile_site_key' => null,
                'turnstile_secret' => null,
            ],
            'backup' => [
                'schedule' => 'weekly',
                'keep' => 6,
                'include_media' => true,
            ],
            'system' => [
                'cron_token' => null,
                'last_cron_at' => null,
            ],
        ];
    }

    public static function flat(): array
    {
        $flat = [];
        foreach (static::all() as $group => $values) {
            foreach ($values as $k => $v) {
                $flat["$group.$k"] = $v;
            }
        }

        return $flat;
    }

    /** Settings stored encrypted at rest. */
    public static function encrypted(): array
    {
        return ['mail.password', 'analytics.google_credentials', 'security.turnstile_secret'];
    }

    public static function default(string $key): mixed
    {
        return Arr::get(static::all(), $key);
    }
}
