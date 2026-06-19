@props([
    'viewUrl',
    'removeUrl' => null,
    'label' => 'document',
])

@if($viewUrl)
    <small class="text-muted d-inline-flex align-items-center flex-wrap mt-25 car-document-actions">
        <span class="mr-50">Current:</span>
        <a href="{{ document_view_url($viewUrl) }}" target="_blank" rel="noopener" class="mr-75 document-view-link">View</a>
        @if($removeUrl)
            <button type="button"
                    class="btn btn-link btn-sm text-danger p-0 align-baseline car-doc-remove-btn"
                    data-remove-url="{{ $removeUrl }}"
                    data-doc-label="{{ $label }}">
                <i class="fa fa-times-circle mr-25"></i>Remove
            </button>
        @endif
    </small>
@endif
