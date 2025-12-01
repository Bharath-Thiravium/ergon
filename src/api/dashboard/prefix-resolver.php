<?php
// /ergon/src/api/dashboard/prefix-resolver.php

/**
 * Resolve a user-supplied raw prefix into a canonical company prefix from the list.
 * Start with first 2 letters and expand until unique match found.
 *
 * @param string $rawPrefix
 * @param array $companyPrefixes
 * @return string resolved prefix
 */
function resolveCompanyPrefix(string $rawPrefix, array $companyPrefixes): string
{
    $letters = preg_replace('/[^A-Za-z]/', '', strtoupper($rawPrefix));
    $len = strlen($letters);
    if ($len < 2) {
        return $letters ?: '';
    }

    for ($i = 2; $i <= $len; $i++) {
        $try = substr($letters, 0, $i);
        $matches = array_filter($companyPrefixes, function ($p) use ($try) {
            return stripos($p, $try) === 0;
        });
        if (count($matches) === 1) {
            return array_values($matches)[0];
        }
    }

    return substr($letters, 0, 2);
}
