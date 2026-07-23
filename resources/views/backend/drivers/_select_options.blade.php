@foreach($drivers as $driver)
    <option value="{{ $driver->id }}"
        {{ (string) ($selectedId ?? '') === (string) $driver->id ? 'selected' : '' }}>
        {{ $driver->selectOptionLabel() }}@if(($showInactiveLabel ?? false) && ! $driver->is_active) (Inactive)@endif
    </option>
@endforeach
