@props([
    'viewUrl',
    'downloadUrl' => null,
    'removeUrl' => null,
    'label' => 'document',
])

<x-document-actions
    :view-url="$viewUrl"
    :download-url="$downloadUrl"
    :remove-url="$removeUrl"
    :label="$label"
    style="compact"
/>
