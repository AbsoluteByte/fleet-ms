@props([
    'deleteUrl',
    'label' => 'record',
])

<button type="button"
        class="btn btn-danger btn-sm car-record-delete-btn"
        data-delete-url="{{ $deleteUrl }}"
        data-record-label="{{ $label }}"
        title="Delete {{ $label }}">
    <i class="fa fa-trash"></i>
</button>
