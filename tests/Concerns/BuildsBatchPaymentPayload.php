<?php

namespace Tests\Concerns;

trait BuildsBatchPaymentPayload
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function batchPaymentRow(array $overrides = []): array
    {
        return array_merge([
            'payment_method' => 'Cash',
            'bank_account_id' => null,
            'payment_date' => now()->toDateString(),
            'amount' => 100,
            'notes' => null,
        ], $overrides);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    protected function batchPaymentsField(array $rows, string $field = 'payments'): array
    {
        return [
            $field => array_map(fn (array $row) => $this->batchPaymentRow($row), $rows),
        ];
    }
}
