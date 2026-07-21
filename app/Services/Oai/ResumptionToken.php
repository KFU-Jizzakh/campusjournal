<?php

namespace App\Services\Oai;

use App\Exceptions\Oai\BadResumptionTokenException;
use Illuminate\Support\Carbon;

/**
 * PURPOSE: Stateless resumption token implementation using
 * base64url-encoded JSON with HMAC-SHA256 signature.
 *
 * SPECIFICATION: SPEC-09/AC-8, SPEC-09/BR-3
 */
class ResumptionToken
{
    public static function encode(array $payload): string
    {
        $ttl = (int) config('oai.token_ttl_hours', 24);
        $payload['expiresAt'] = Carbon::now()->addHours($ttl)->timestamp;

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $body = self::b64(((string) $json));
        $sig = self::b64(hash_hmac('sha256', $body, self::key(), true));

        return $body.'.'.$sig;
    }

    public static function decode(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            throw new BadResumptionTokenException('Malformed token.');
        }

        [$body, $sig] = $parts;
        $expected = self::b64(hash_hmac('sha256', $body, self::key(), true));

        if (! hash_equals($expected, $sig)) {
            throw new BadResumptionTokenException('Invalid signature.');
        }

        $json = base64_decode(strtr($body, '-_', '+/'), true);
        if ($json === false) {
            throw new BadResumptionTokenException('Malformed token.');
        }

        $payload = json_decode($json, true);
        if (! is_array($payload)) {
            throw new BadResumptionTokenException('Malformed token.');
        }

        if (isset($payload['expiresAt']) && $payload['expiresAt'] < Carbon::now()->timestamp) {
            throw new BadResumptionTokenException('Token expired.');
        }

        return $payload;
    }

    private static function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function key(): string
    {
        $key = (string) config('app.key');
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7)) ?: $key;
        }

        return $key;
    }
}
