<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * PURPOSE: Parses and validates the Crossmark configuration values
 * (policy DOI and domains) so that malformed operator input degrades
 * safely to "crossmark disabled" instead of producing schema-invalid
 * Crossref deposits. Configuration evaluation stays side-effect free
 * (no facades): a broken policy DOI is surfaced later, at app boot.
 */
final class CrossrefConfig
{
    /**
     * PURPOSE: Returns the configured Crossmark policy DOI, falling
     * back to the deprecated CROSSMARK_POLICY_URL variable and
     * validating the result against the Crossref doi_t pattern.
     *
     * SPECIFICATION: SPEC-16/BR-7, SPEC-16/AC-10
     */
    public static function policyDoi(?string $doi, ?string $legacyUrl = null): ?string
    {
        $value = $doi ?: $legacyUrl;

        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        if (preg_match('/^10\.[0-9]{4,9}\/.{1,200}$/Du', $value) === 1) {
            return $value;
        }

        return null;
    }

    /**
     * PURPOSE: Whether a Crossmark policy value was supplied but is
     * not a valid Crossref DOI — the misconfiguration operators must
     * be warned about. Returns false when nothing was configured or
     * the value is valid.
     *
     * SPECIFICATION: SPEC-16/BR-7
     */
    public static function misconfigured(?string $doi, ?string $legacyUrl = null): bool
    {
        $value = $doi ?: $legacyUrl;

        return $value !== null && trim($value) !== '' && self::policyDoi($doi, $legacyUrl) === null;
    }

    /**
     * PURPOSE: Names the configuration variable whose value was
     * supplied but is not a valid Crossref DOI — the primary
     * CROSSMARK_POLICY_DOI when set, the deprecated
     * CROSSMARK_POLICY_URL when it is the only supplied value.
     * Returns null when nothing is misconfigured.
     *
     * SPECIFICATION: SPEC-16/BR-7
     */
    public static function invalidVariable(?string $doi, ?string $legacyUrl = null): ?string
    {
        if ($doi !== null && trim($doi) !== '' && self::policyDoi($doi) === null) {
            return 'CROSSMARK_POLICY_DOI';
        }

        if ($doi === null && $legacyUrl !== null && trim($legacyUrl) !== '' && self::policyDoi(null, $legacyUrl) === null) {
            return 'CROSSMARK_POLICY_URL';
        }

        return null;
    }

    /**
     * PURPOSE: Logs a warning once per day when the Crossmark policy
     * DOI is misconfigured, naming the offending variable. Safe to
     * call from AppServiceProvider::boot (facades and cache are fully
     * available there, unlike during config evaluation).
     *
     * SPECIFICATION: SPEC-16/BR-7
     */
    public static function warnIfMisconfigured(): void
    {
        if (! config('services.crossref.crossmark.policy_doi_misconfigured')) {
            return;
        }

        try {
            if (! Cache::add('crossref_crossmark_misconfigured_warned', true, 86_400)) {
                return;
            }
        } catch (\Throwable) {
            // cache store unavailable: warn without throttling rather than crash boot
        }

        $variable = config('services.crossref.crossmark.policy_doi_invalid_variable')
            ?? 'CROSSMARK_POLICY_DOI';

        Log::warning(
            "{$variable} is not a valid Crossref DOI; "
            .'the Crossmark widget and retraction/correction re-deposits are disabled. '
            .'Check the value (a DOI like 10.xxxx/policy, not a URL) or unset the variable.',
        );
    }

    /**
     * PURPOSE: Splits and validates the Crossmark domains list,
     * keeping only entries that satisfy the schema's cm_domain
     * pattern (min 4 chars, dotted hostname).
     *
     * SPECIFICATION: SPEC-16/BR-7
     */
    public static function domains(?string $raw): array
    {
        $raw = trim($raw ?? '');

        return array_values(array_filter(
            array_map('trim', explode(',', $raw)),
            fn (string $domain) => strlen($domain) >= 4
                && strlen($domain) <= 1024
                && preg_match('/^[A-Za-z0-9_]+([-.][A-Za-z0-9_]+)*\.[A-Za-z0-9_]+([-.][A-Za-z0-9_]+)*$/', $domain) === 1
        ));
    }
}
