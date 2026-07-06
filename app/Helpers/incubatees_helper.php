<?php

if (! function_exists('incubatee_anchor_id')) {
    /**
     * Build the anchor ID used to deep-link incubatee cards.
     */
    function incubatee_anchor_id(array $inc): string
    {
        $slug = trim((string) ($inc['slug'] ?? ''));
        if ($slug !== '') {
            return 'incubatee-' . $slug;
        }

        $companyName = html_entity_decode((string) ($inc['companyName'] ?? ''), ENT_QUOTES, 'UTF-8');
        $fallback = trim((string) preg_replace('/[^a-z0-9]+/i', '-', strtolower($companyName)), '-');

        return 'incubatee-' . ($fallback !== '' ? $fallback : 'item');
    }
}
