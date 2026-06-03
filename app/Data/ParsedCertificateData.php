<?php

namespace App\Data;

use Carbon\Carbon;

class ParsedCertificateData
{
    public function __construct(
        public string $registration,
        public Carbon $startDate,
        public Carbon $expiryDate,
        public string $source = 'filename',
    ) {}
}
