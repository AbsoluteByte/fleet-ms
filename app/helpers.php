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

if (! function_exists('document_download_filename')) {
    function document_download_filename(?string $url, ?string $fallback = 'document'): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return $fallback;
        }

        $basename = basename($path);

        return $basename !== '' && $basename !== '/' ? $basename : $fallback;
    }
}

if (! function_exists('document_download_url')) {
    /**
     * Resolve a download URL from a view URL (asset path or inline-view route).
     */
    function document_download_url(?string $viewUrl): ?string
    {
        if ($viewUrl === null || $viewUrl === '') {
            return null;
        }

        if (preg_match('#/cars/\d+/view/v5(?:/(\d+))?#', $viewUrl, $matches)) {
            $index = $matches[1] ?? '';

            return preg_replace(
                '#/view/v5(?:/\d+)?#',
                '/download/v5'.($index !== '' ? '/'.$index : ''),
                $viewUrl
            );
        }

        if (preg_match('#/(mots|phvs)/\d+/download#', $viewUrl)
            || str_contains($viewUrl, 'view-signed')
            || str_contains($viewUrl, 'road-tax/preview')) {
            if (str_contains($viewUrl, 'download=1')) {
                return $viewUrl;
            }

            $separator = str_contains($viewUrl, '?') ? '&' : '?';

            return $viewUrl.$separator.'download=1';
        }

        return $viewUrl;
    }
}

if (! function_exists('document_download_uses_client_attribute')) {
    function document_download_uses_client_attribute(?string $downloadUrl): bool
    {
        if ($downloadUrl === null || $downloadUrl === '') {
            return false;
        }

        return ! str_contains($downloadUrl, 'download=1')
            && ! preg_match('#/download/v5#', $downloadUrl);
    }
}
