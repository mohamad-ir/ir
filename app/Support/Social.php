<?php

namespace App\Support;

class Social
{
    public const NETWORKS = [
        'linkedin' => 'LinkedIn', 'x' => 'X (Twitter)', 'youtube' => 'YouTube', 'instagram' => 'Instagram',
        'researchgate' => 'ResearchGate', 'orcid' => 'ORCID', 'google_scholar' => 'Google Scholar', 'facebook' => 'Facebook',
    ];

    public static function label(string $network): string
    {
        return self::NETWORKS[$network] ?? ucfirst($network);
    }

    /** Share links for a page. */
    public static function shareLinks(string $url, string $title): array
    {
        $u = rawurlencode($url);
        $t = rawurlencode($title);

        return [
            'linkedin' => ['label' => 'LinkedIn', 'url' => "https://www.linkedin.com/sharing/share-offsite/?url={$u}"],
            'x' => ['label' => 'X', 'url' => "https://twitter.com/intent/tweet?url={$u}&text={$t}"],
            'whatsapp' => ['label' => 'WhatsApp', 'url' => "https://wa.me/?text={$t}%20{$u}"],
            'facebook' => ['label' => 'Facebook', 'url' => "https://www.facebook.com/sharer/sharer.php?u={$u}"],
            'email' => ['label' => 'Email', 'url' => "mailto:?subject={$t}&body={$u}"],
        ];
    }
}
