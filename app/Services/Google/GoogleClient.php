<?php

namespace App\Services\Google;

use App\Support\Settings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Minimal Google API client using a service account (OAuth 2.0 JWT bearer flow)
 * for the GA4 Data API and the Search Console API. Responses are cached so the
 * dashboard stays fast and quota usage stays low.
 */
class GoogleClient
{
    public const SCOPES = 'https://www.googleapis.com/auth/analytics.readonly https://www.googleapis.com/auth/webmasters.readonly';

    public static function version(): int
    {
        return (int) Cache::get('google.version', 1);
    }

    public static function clearCache(): void
    {
        Cache::forever('google.version', static::version() + 1);
    }

    public function credentials(): ?array
    {
        $json = Settings::get('analytics.google_credentials');
        $data = is_string($json) ? json_decode($json, true) : (is_array($json) ? $json : null);

        return is_array($data) && isset($data['client_email'], $data['private_key']) ? $data : null;
    }

    public function configured(): bool
    {
        return $this->credentials() !== null;
    }

    public function ga4Configured(): bool
    {
        return $this->configured() && filled(Settings::get('analytics.ga4_property_id'));
    }

    public function gscConfigured(): bool
    {
        return $this->configured() && filled(Settings::get('analytics.gsc_site_url'));
    }

    public function makeJwt(array $credentials, ?int $now = null): string
    {
        $now ??= time();
        $b64 = fn (string $s) => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
        $header = $b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims = $b64(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => self::SCOPES,
            'aud' => $credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $key = openssl_pkey_get_private($credentials['private_key']);
        if (! $key || ! openssl_sign($header.'.'.$claims, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('The service account private key could not be read.');
        }

        return $header.'.'.$claims.'.'.$b64($signature);
    }

    public function accessToken(): string
    {
        $credentials = $this->credentials() ?? throw new RuntimeException('No Google service account is configured (Settings › Analytics & Google).');

        return Cache::remember('google.token.'.md5($credentials['client_email']), 3300, function () use ($credentials) {
            $response = Http::asForm()->timeout(15)->post($credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $this->makeJwt($credentials),
            ]);

            if (! $response->successful() || ! $response->json('access_token')) {
                throw new RuntimeException('Google rejected the service account: '.($response->json('error_description') ?? $response->body()));
            }

            return $response->json('access_token');
        });
    }

    protected function post(string $url, array $body): array
    {
        $response = Http::withToken($this->accessToken())->acceptJson()->timeout(20)->post($url, $body);

        if (! $response->successful()) {
            $message = $response->json('error.message') ?? $response->body();
            throw new RuntimeException("Google API error ({$response->status()}): {$message}");
        }

        return $response->json() ?? [];
    }

    /** GA4 Data API runReport, returned as a list of associative rows. */
    public function ga4Report(array $dimensions, array $metrics, string $startDate = '28daysAgo', string $endDate = 'today', int $limit = 10, ?array $orderBy = null): array
    {
        $property = Settings::get('analytics.ga4_property_id');
        $cacheKey = 'google.ga4.'.static::version().md5(json_encode(func_get_args()).$property);

        return Cache::remember($cacheKey, 1800, function () use ($property, $dimensions, $metrics, $startDate, $endDate, $limit, $orderBy) {
            $body = [
                'dateRanges' => [['startDate' => $startDate, 'endDate' => $endDate]],
                'dimensions' => array_map(fn ($d) => ['name' => $d], $dimensions),
                'metrics' => array_map(fn ($m) => ['name' => $m], $metrics),
                'limit' => $limit,
            ];
            if ($orderBy) {
                $body['orderBys'] = [$orderBy];
            }
            $data = $this->post("https://analyticsdata.googleapis.com/v1beta/properties/{$property}:runReport", $body);

            return collect($data['rows'] ?? [])->map(function ($row) use ($dimensions, $metrics) {
                $out = [];
                foreach ($dimensions as $i => $d) {
                    $out[$d] = $row['dimensionValues'][$i]['value'] ?? null;
                }
                foreach ($metrics as $i => $m) {
                    $out[$m] = (float) ($row['metricValues'][$i]['value'] ?? 0);
                }

                return $out;
            })->all();
        });
    }

    /** Search Console search analytics (queries/pages with clicks, impressions, CTR, position). */
    public function searchAnalytics(array $dimensions, int $days = 28, int $limit = 10): array
    {
        $site = Settings::get('analytics.gsc_site_url');
        $cacheKey = 'google.gsc.'.static::version().md5(json_encode([$dimensions, $days, $limit, $site]));

        return Cache::remember($cacheKey, 3600, function () use ($site, $dimensions, $days, $limit) {
            $data = $this->post('https://www.googleapis.com/webmasters/v3/sites/'.rawurlencode($site).'/searchAnalytics/query', [
                'startDate' => now()->subDays($days + 2)->toDateString(),
                'endDate' => now()->subDays(2)->toDateString(),
                'dimensions' => $dimensions,
                'rowLimit' => $limit,
            ]);

            return collect($data['rows'] ?? [])->map(fn ($r) => [
                'keys' => $r['keys'] ?? [],
                'clicks' => (int) ($r['clicks'] ?? 0),
                'impressions' => (int) ($r['impressions'] ?? 0),
                'ctr' => round(($r['ctr'] ?? 0) * 100, 1),
                'position' => round($r['position'] ?? 0, 1),
            ])->all();
        });
    }

    /** @return array<int, string> human-readable results */
    public function test(): array
    {
        Cache::forget('google.token.'.md5($this->credentials()['client_email'] ?? ''));
        $this->accessToken();
        $lines = ['Service account authenticated.'];

        if (filled(Settings::get('analytics.ga4_property_id'))) {
            $rows = $this->ga4Report([], ['activeUsers'], '7daysAgo', 'today', 1);
            $lines[] = 'GA4: '.(int) ($rows[0]['activeUsers'] ?? 0).' active users in the last 7 days.';
        }
        if (filled(Settings::get('analytics.gsc_site_url'))) {
            $rows = $this->searchAnalytics(['date'], 7, 7);
            $lines[] = 'Search Console: '.collect($rows)->sum('clicks').' clicks in the last 7 days.';
        }

        return $lines;
    }
}
