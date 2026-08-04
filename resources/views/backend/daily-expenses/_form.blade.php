@php
    $selectedExpenseType = old(
        'daily_expense_type',
        $model->daily_expense_type ?? \App\Models\Expense::DAILY_TYPE_OFFICE
    );
    $selectedCarId = (string) old('car_id', $model->car_id ?? '');
@endphp

<div class="row">
    <div class="col-md-6 mb-2">
        <div class="form-group">
            <label for="daily_expense_type">Type <span class="text-danger">*</span></label>
            <select name="daily_expense_type" id="daily_expense_type"
                    class="form-control @error('daily_expense_type') is-invalid @enderror" required>
                <option value="{{ \App\Models\Expense::DAILY_TYPE_OFFICE }}"
                    {{ $selectedExpenseType === \App\Models\Expense::DAILY_TYPE_OFFICE ? 'selected' : '' }}>
                    Office
                </option>
                <option value="{{ \App\Models\Expense::DAILY_TYPE_VEHICLE }}"
                    {{ $selectedExpenseType === \App\Models\Expense::DAILY_TYPE_VEHICLE ? 'selected' : '' }}>
                    Vehicle
                </option>
            </select>
            @error('daily_expense_type')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6 mb-2 {{ $selectedExpenseType === \App\Models\Expense::DAILY_TYPE_VEHICLE ? '' : 'd-none' }}"
         id="daily-expense-car-field">
        <div class="form-group">
            <label for="car_id">Car <span class="text-danger">*</span></label>
            <select name="car_id" id="car_id"
                    class="form-control select-search @error('car_id') is-invalid @enderror">
                <option value="">Select Car</option>
                @foreach($cars ?? collect() as $car)
                    <option value="{{ $car->id }}" {{ $selectedCarId === (string) $car->id ? 'selected' : '' }}>
                        {{ $car->registration ?: 'No registration' }}
                        @if($car->carModel) — {{ $car->carModel->name }} @endif
                        @if($car->company) ({{ $car->company->name }}) @endif
                    </option>
                @endforeach
            </select>
            @error('car_id')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6 mb-2">
        <div class="form-group">
            <label for="title">Expense Title <span class="text-danger">*</span></label>
            <input type="text" name="title" id="title"
                   class="form-control @error('title') is-invalid @enderror"
                   value="{{ old('title', $model->title ?? '') }}"
                   placeholder="e.g. Office supplies" required>
            @error('title')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6 mb-2">
        <div class="form-group">
            <label for="date">Date <span class="text-danger">*</span></label>
            <input type="date" name="date" id="date"
                   class="form-control @error('date') is-invalid @enderror"
                   value="{{ old('date', optional($model->date)->format('Y-m-d') ?? now()->toDateString()) }}"
                   required>
            @error('date')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-12 mb-2">
        <label class="form-label d-block">Payments <span class="text-danger">*</span></label>
        <p class="text-muted">Add separate rows when the expense was paid using more than one method.</p>
        @include('backend.payments.partials.batch-payment-rows', [
            'fieldName' => 'payments',
            'containerId' => 'daily-expense-payments',
            'bankAccounts' => $bankAccounts ?? collect(),
            'defaultPaymentDate' => old('date', optional($model->date)->format('Y-m-d') ?? now()->toDateString()),
            'showNotes' => false,
            'helpText' => 'Each payment row appears separately on the daily financial sheet.',
        ])
    </div>

    <div class="col-md-6 mb-2">
        <div class="form-group">
            <label for="document">Document <span class="text-muted">(optional)</span></label>
            <input type="file" name="document" id="document"
                   class="form-control @error('document') is-invalid @enderror">
            @error('document')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-12 mb-2">
        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea name="notes" id="notes" rows="3"
                      class="form-control @error('notes') is-invalid @enderror"
                      placeholder="Optional notes">{{ old('notes', $model->notes ?? '') }}</textarea>
            @error('notes')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-12">
        <button type="submit" class="btn btn-primary">
            <i class="fa fa-save"></i> Save Daily Expense
        </button>
    </div>
</div>

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const expenseTypeSelect = document.getElementById('daily_expense_type');
            const carField = document.getElementById('daily-expense-car-field');
            const carSelect = document.getElementById('car_id');
            const expenseDateInput = document.getElementById('date');

            function toggleCarField() {
                if (!expenseTypeSelect || !carField || !carSelect) {
                    return;
                }

                const isVehicle = expenseTypeSelect.value === @json(\App\Models\Expense::DAILY_TYPE_VEHICLE);
                carField.classList.toggle('d-none', !isVehicle);
                carSelect.required = isVehicle;

                if (!isVehicle) {
                    if (window.jQuery && window.jQuery.fn.select2 && window.jQuery(carSelect).hasClass('select2-hidden-accessible')) {
                        window.jQuery(carSelect).val('').trigger('change');
                    } else {
                        carSelect.value = '';
                    }
                }
            }

            function syncPaymentDates() {
                if (!expenseDateInput || !window.BatchPaymentRows) {
                    return;
                }

                window.BatchPaymentRows.rows('daily-expense-payments').forEach(function (row) {
                    const dateInput = row.querySelector('[data-payment-date]');
                    if (dateInput) {
                        dateInput.value = expenseDateInput.value;
                    }
                });
            }

            if (expenseTypeSelect) {
                expenseTypeSelect.addEventListener('change', toggleCarField);
                toggleCarField();
            }

            if (expenseDateInput) {
                expenseDateInput.addEventListener('change', syncPaymentDates);
                syncPaymentDates();
            }
        });
    </script>
@endpush
