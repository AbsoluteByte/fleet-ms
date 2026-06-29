@props([
    'viewUrl',
    'downloadUrl' => null,
    'downloadFilename' => null,
    'removeUrl' => null,
    'label' => null,
    'style' => 'compact',
    'viewText' => 'View',
    'downloadText' => 'Download',
    'showIcons' => false,
    'linkClass' => '',
])

@php
    $resolvedDownloadUrl = $downloadUrl ?? document_download_url($viewUrl);
    $resolvedFilename = $downloadFilename ?? document_download_filename($viewUrl);
    $useDownloadAttribute = document_download_uses_client_attribute($resolvedDownloadUrl);
    $downloadHref = $useDownloadAttribute
        ? document_view_url($resolvedDownloadUrl)
        : $resolvedDownloadUrl;
@endphp

@if($viewUrl)
    @if($style === 'buttons')
        <span {{ $attributes->class(['document-actions', 'document-actions--buttons', 'd-inline-flex', 'flex-wrap', 'align-items-center']) }}>
            <a href="{{ document_view_url($viewUrl) }}" target="_blank" rel="noopener"
               class="document-view-link btn btn-sm btn-outline-primary mr-1 mb-1 {{ $linkClass }}">
                @if($showIcons)<i class="fa fa-eye"></i> @endif{{ $viewText }}
            </a>
            @if($resolvedDownloadUrl)
                <a href="{{ $downloadHref }}"
                   @if($useDownloadAttribute) download="{{ $resolvedFilename }}" @endif
                   class="document-download-link btn btn-sm btn-outline-secondary mr-1 mb-1 {{ $linkClass }}"
                   rel="noopener">
                    @if($showIcons)<i class="fa fa-download"></i> @endif{{ $downloadText }}
                </a>
            @endif
        </span>
    @elseif($style === 'list-item')
        <span {{ $attributes->class(['document-actions', 'document-actions--list-item']) }}>
            <a href="{{ document_view_url($viewUrl) }}" target="_blank" rel="noopener" class="document-view-link {{ $linkClass }}">{{ $viewText }}</a>
            @if($resolvedDownloadUrl)
                <span class="text-muted mx-1">|</span>
                <a href="{{ $downloadHref }}"
                   @if($useDownloadAttribute) download="{{ $resolvedFilename }}" @endif
                   class="document-download-link {{ $linkClass }}">{{ $downloadText }}</a>
            @endif
        </span>
    @elseif($style === 'text')
        <span {{ $attributes->class(['document-actions', 'document-actions--text']) }}>
            <a href="{{ document_view_url($viewUrl) }}" target="_blank" class="document-view-link text-primary {{ $linkClass }}">
                @if($showIcons)<i class="fa fa-file"></i> @endif{{ $viewText }}
            </a>
            @if($resolvedDownloadUrl)
                <a href="{{ $downloadHref }}"
                   @if($useDownloadAttribute) download="{{ $resolvedFilename }}" @endif
                   class="document-download-link text-primary ml-1 {{ $linkClass }}">
                    @if($showIcons)<i class="fa fa-download"></i> @endif{{ $downloadText }}
                </a>
            @endif
        </span>
    @else
        <small {{ $attributes->class(['text-muted', 'd-inline-flex', 'align-items-center', 'flex-wrap', 'mt-25', 'document-actions', 'document-actions--compact']) }}>
            @if($label)
                <span class="mr-50">{{ $label === 'document' ? 'Current:' : $label.':' }}</span>
            @endif
            <a href="{{ document_view_url($viewUrl) }}" target="_blank" rel="noopener" class="mr-75 document-view-link {{ $linkClass }}">{{ $viewText }}</a>
            @if($resolvedDownloadUrl)
                <a href="{{ $downloadHref }}"
                   @if($useDownloadAttribute) download="{{ $resolvedFilename }}" @endif
                   class="mr-75 document-download-link {{ $linkClass }}">{{ $downloadText }}</a>
            @endif
            @if($removeUrl)
                <button type="button"
                        class="btn btn-link btn-sm text-danger p-0 align-baseline car-doc-remove-btn"
                        data-remove-url="{{ $removeUrl }}"
                        data-doc-label="{{ $label ?? 'document' }}">
                    <i class="fa fa-times-circle mr-25"></i>Remove
                </button>
            @endif
        </small>
    @endif
@endif
