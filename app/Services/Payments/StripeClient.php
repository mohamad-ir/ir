<?php

namespace App\Services\Payments;

use App\Support\Settings;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Minimal Stripe REST client (no SDK needed on shared hosting).
 * Only the endpoints the website uses: customers, products, prices,
 * Checkout, the Customer Portal, subscriptions and refunds.
 */
class StripeClient
{
    public const API = 'https://api.stripe.com/v1/';

    public const API_VERSION = '2025-03-31.basil';

    public function __construct(protected ?string $secretKey = null)
    {
        $this->secretKey ??= Settings::get('payments.stripe_secret_key');
    }

    public static function configured(): bool
    {
        return (bool) Settings::get('payments.enabled') && filled(Settings::get('payments.stripe_secret_key'));
    }

    public function isTestMode(): bool
    {
        return str_starts_with((string) $this->secretKey, 'sk_test_') || str_starts_with((string) $this->secretKey, 'rk_test_');
    }

    public function get(string $path, array $query = []): array
    {
        return $this->send('get', $path, $query);
    }

    public function post(string $path, array $params = [], ?string $idempotencyKey = null): array
    {
        return $this->send('post', $path, $params, $idempotencyKey ?? (string) Str::uuid());
    }

    public function delete(string $path): array
    {
        return $this->send('delete', $path);
    }

    protected function send(string $method, string $path, array $params = [], ?string $idempotencyKey = null): array
    {
        if (blank($this->secretKey)) {
            throw new StripeException('Stripe is not configured. Add your secret key under Sales › Payment settings.');
        }

        $request = Http::withToken($this->secretKey)
            ->withHeaders(array_filter(['Stripe-Version' => self::API_VERSION, 'Idempotency-Key' => $idempotencyKey]))
            ->acceptJson()
            ->timeout(30)
            ->connectTimeout(10)
            ->retry(2, 500, fn ($e) => $e instanceof \Illuminate\Http\Client\ConnectionException, throw: false);

        $url = self::API.ltrim($path, '/');
        $encoded = self::encode($params);

        try {
            /** @var Response $response */
            $response = match ($method) {
                'get' => $request->get($url.($encoded ? '?'.$encoded : '')),
                'delete' => $request->delete($url),
                default => $request->withBody($encoded, 'application/x-www-form-urlencoded')->post($url),
            };
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new StripeException('Could not reach Stripe: '.$e->getMessage(), 0, 'connection_error');
        }

        if ($response->failed()) {
            $error = $response->json('error') ?? [];
            throw new StripeException($error['message'] ?? 'Stripe request failed ('.$response->status().').', $response->status(), $error['code'] ?? null, $error['type'] ?? null);
        }

        return $response->json() ?? [];
    }

    /** Stripe's form encoding: nested arrays as a[b][0]=c and booleans as "true"/"false". */
    public static function encode(array $params): string
    {
        $normalise = function ($value) use (&$normalise) {
            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }
            if (is_array($value)) {
                return array_map($normalise, array_filter($value, fn ($v) => $v !== null));
            }

            return $value;
        };

        return http_build_query($normalise(array_filter($params, fn ($v) => $v !== null)), '', '&', PHP_QUERY_RFC1738);
    }

    // Webhooks ---------------------------------------------------------------

    /**
     * Verifies the Stripe-Signature header (HMAC-SHA256 of "timestamp.payload").
     *
     * @see https://docs.stripe.com/webhooks#verify-manually
     */
    public static function verifySignature(string $payload, ?string $header, ?string $secret, int $tolerance = 300): bool
    {
        if (blank($header) || blank($secret)) {
            return false;
        }

        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $header) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);
            if ($key === 't') {
                $timestamp = (int) $value;
            } elseif ($key === 'v1' && $value) {
                $signatures[] = $value;
            }
        }

        if (! $timestamp || ! $signatures || abs(time() - $timestamp) > $tolerance) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);
        foreach ($signatures as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }

    public static function sign(string $payload, string $secret, ?int $timestamp = null): string
    {
        $timestamp ??= time();

        return 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$payload, $secret);
    }
}
