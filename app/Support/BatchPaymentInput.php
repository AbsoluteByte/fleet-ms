<?php

namespace App\Support;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BatchPaymentInput
{
    public const PAYMENT_METHODS = ['Bank Transfer', 'Cash', 'Cheque', 'Card Payment', 'Direct Debit', 'Discount'];

    /**
     * @return array<int, string, mixed>
     */
    public static function validationRules(
        Request $request,
        int $tenantId,
        string $field = 'payments',
        bool $requirePaymentDate = true
    ): array {
        $paymentDateRule = $requirePaymentDate ? 'required|date' : 'nullable|date';

        return [
            $field => 'required|array|min:1',
            "{$field}.*.payment_method" => ['required', 'string', Rule::in(self::PAYMENT_METHODS)],
            "{$field}.*.bank_account_id" => [
                'nullable',
                Rule::exists('bank_accounts', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            "{$field}.*.payment_date" => $paymentDateRule,
            "{$field}.*.amount" => 'required|numeric|min:0.01',
            "{$field}.*.notes" => 'nullable|string|max:5000',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return list<array{payment_method: string, bank_account_id: ?int, payment_date: ?string, amount: float, notes: ?string}>
     */
    public static function normalizeRows(array $validated, string $field = 'payments', bool $allowEmpty = false): array
    {
        $rows = [];

        foreach (($validated[$field] ?? []) as $index => $paymentRow) {
            if (! is_array($paymentRow) || empty($paymentRow['amount'])) {
                continue;
            }

            $rowMethod = (string) ($paymentRow['payment_method'] ?? '');

            if ($rowMethod === '') {
                throw ValidationException::withMessages([
                    "{$field}.{$index}.payment_method" => 'Payment method is required when an amount is entered.',
                ]);
            }

            if (Payment::requiresBankAccount($rowMethod) && empty($paymentRow['bank_account_id'])) {
                throw ValidationException::withMessages([
                    "{$field}.{$index}.bank_account_id" => 'Bank account is required for bank transfer and card payments.',
                ]);
            }

            $rows[] = [
                'payment_method' => $rowMethod,
                'bank_account_id' => Payment::bankAccountIdForMethod(
                    $rowMethod,
                    $paymentRow['bank_account_id'] ?? null
                ),
                'payment_date' => $paymentRow['payment_date'] ?? null,
                'amount' => round((float) $paymentRow['amount'], 2),
                'notes' => $paymentRow['notes'] ?? null,
            ];
        }

        if ($rows === [] && ! $allowEmpty) {
            throw ValidationException::withMessages([
                "{$field}.0.amount" => 'Add at least one payment amount.',
            ]);
        }

        return $rows;
    }

    /**
     * @return array<int, string, mixed>
     */
    public static function optionalValidationRules(
        Request $request,
        int $tenantId,
        string $field = 'reservation_payments'
    ): array {
        return [
            $field => 'nullable|array',
            "{$field}.*.payment_method" => ['nullable', 'string', Rule::in(self::PAYMENT_METHODS)],
            "{$field}.*.bank_account_id" => [
                'nullable',
                Rule::exists('bank_accounts', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            "{$field}.*.amount" => 'nullable|numeric|min:0.01',
            "{$field}.*.notes" => 'nullable|string|max:5000',
        ];
    }

    /**
     * @param  list<array<string, mixed>>|null  $defaultRows
     * @return list<array<string, mixed>>
     */
    public static function defaultRows(?array $defaultRows = null, ?string $defaultDate = null): array
    {
        if ($defaultRows !== null && $defaultRows !== []) {
            return array_values($defaultRows);
        }

        return [[
            'payment_method' => '',
            'bank_account_id' => '',
            'payment_date' => $defaultDate ?? now()->toDateString(),
            'amount' => '',
            'notes' => '',
        ]];
    }

    /**
     * @param  list<array<string, mixed>>|null  $defaultRows
     * @return list<array<string, mixed>>
     */
    public static function resolveRows(string $fieldName, ?array $defaultRows = null, ?string $defaultDate = null): array
    {
        $rows = old($fieldName, $defaultRows);

        if (! is_array($rows) || $rows === []) {
            return self::defaultRows(null, $defaultDate);
        }

        return array_values($rows);
    }

    public static function assertDepositWithinAgreedAdvance(float $amountPaid, float $agreedAdvance): void
    {
        if (round($amountPaid, 2) > round($agreedAdvance, 2)) {
            throw ValidationException::withMessages([
                'amount_paid' => 'Deposit payments cannot exceed the agreed advance. Rent is collected when creating the agreement.',
            ]);
        }
    }
}
