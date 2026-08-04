@php
    $reservationModel = $reservation ?? null;
    $defaultRows = null;

    if ($reservationModel && $reservationModel->relationLoaded('reservationPayments') && $reservationModel->reservationPayments->isNotEmpty()) {
        $defaultRows = $reservationModel->reservationPayments->map(fn ($payment) => [
            'payment_method' => $payment->payment_method,
            'bank_account_id' => $payment->bank_account_id,
            'amount' => $payment->amount,
            'notes' => '',
        ])->all();
    } elseif ($reservationModel && (float) ($reservationModel->amount_paid ?? 0) > 0) {
        $defaultRows = [[
            'payment_method' => $reservationModel->payment_method,
            'bank_account_id' => $reservationModel->bank_account_id,
            'amount' => $reservationModel->amount_paid,
            'notes' => '',
        ]];
    }
@endphp

<div class="col-12 form-group">
    <label class="d-block">Deposit payments</label>
    <p class="text-muted mb-2">Add separate rows when the customer paid using more than one method. Leave empty for no deposit.</p>
    <input type="hidden" name="amount_paid" id="amount_paid" value="{{ old('amount_paid', $reservationModel->amount_paid ?? 0) }}">
    @error('amount_paid')
    <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    @include('backend.payments.partials.batch-payment-rows', [
        'fieldName' => 'reservation_payments',
        'containerId' => 'reservation-payments',
        'bankAccounts' => $bankAccounts ?? collect(),
        'defaultRows' => $defaultRows,
        'showPaymentDate' => false,
        'showNotes' => false,
        'onAmountChangeCallback' => 'refreshReservationAmountPaidTotal',
    ])
</div>

@push('js')
    <script>
        window.refreshReservationAmountPaidTotal = function () {
            var total = 0;

            if (window.BatchPaymentRows) {
                window.BatchPaymentRows.rows('reservation-payments').forEach(function (row) {
                    var input = row.querySelector('[data-payment-amount]');
                    total += Number(input?.value || 0);
                });
            }

            var hiddenInput = document.getElementById('amount_paid');
            if (hiddenInput) {
                hiddenInput.value = total > 0 ? total.toFixed(2) : '0';
            }

            if (typeof window.refreshReservationBalanceDisplay === 'function') {
                window.refreshReservationBalanceDisplay();
            }
        };

        document.addEventListener('DOMContentLoaded', function () {
            window.refreshReservationAmountPaidTotal();
        });
    </script>
@endpush
