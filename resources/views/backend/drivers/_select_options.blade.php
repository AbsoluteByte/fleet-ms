@foreach($drivers as $driver)
    <option value="{{ $driver->id }}"
        {{ (string) ($selectedId ?? '') === (string) $driver->id ? 'selected' : '' }}>
        {{ $driver->selectOptionLabel() }}
    </option>
@endforeach
