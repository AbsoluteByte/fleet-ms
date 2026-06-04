@php
    $driverAddress = collect([
        $driver->address1 ?? null,
        $driver->address2 ?? null,
        $driver->post_code ?? null,
        $driver->town ?? null,
    ])->filter(fn ($line) => filled($line))->implode(', ');
@endphp
{{ $driverAddress }}
