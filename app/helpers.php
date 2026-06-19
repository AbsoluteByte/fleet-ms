<?php

if (! function_exists('document_view_url')) {
    /**
     * Append a cache-busting timestamp query param for uploaded document view links.
     */
    function document_view_url(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.'time='.time();
    }
}
