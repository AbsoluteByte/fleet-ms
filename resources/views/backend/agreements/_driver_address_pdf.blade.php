@php
    $driverAddress = collect([
        $driver->address1 ?? null,
        $driver->address2 ?? null,
        $driver->town ?? null,
        $driver->county ?? null,
        $driver->post_code ?? null,
    ])->filter(fn ($line) => filled($line))->implode(', ');
@endphp
{{ strtoupper($driverAddress) }}
