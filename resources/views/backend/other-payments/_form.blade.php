@php
    $selectedPaymentType = old(
        'other_payment_type',
        $model->other_payment_type ?? \App\Models\OtherPayment::TYPE_OFFICE
    );
    $selectedCarId = (string) old('car_id', $model->car_id ?? '');
@endphp

<div class="row">
    <div class="col-md-6 mb-2">
        <div class="form-group">
            <label for="other_payment_type">Type <span class="text-danger">*</span></label>
            <select name="other_payment_type" id="other_payment_type"
                    class="form-control @error('other_payment_type') is-invalid @enderror" required>
                <option value="{{ \App\Models\OtherPayment::TYPE_OFFICE }}"
                    {{ $selectedPaymentType === \App\Models\OtherPayment::TYPE_OFFICE ? 'selected' : '' }}>
                    Office
                </option>
                <option value="{{ \App\Models\OtherPayment::TYPE_VEHICLE }}"
                    {{ $selectedPaymentType === \App\Models\OtherPayment::TYPE_VEHICLE ? 'selected' : '' }}>
                    Vehicle
                </option>
            </select>
            @error('other_payment_type')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6 mb-2 {{ $selectedPaymentType === \App\Models\OtherPayment::TYPE_VEHICLE ? '' : 'd-none' }}"
         id="other-payment-car-field">
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
            <label for="title">Payment Title <span class="text-danger">*</span></label>
            <input type="text" name="title" id="title"
                   class="form-control @error('title') is-invalid @enderror"
                   value="{{ old('title', $model->title ?? '') }}"
                   placeholder="e.g. Car sale — ABC123" required>
            @error('title')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-12 mb-2">
        <label class="form-label d-block">Payments <span class="text-danger">*</span></label>
        <p class="text-muted">Add separate rows when the buyer paid using more than one method.</p>
        @include('backend.payments.partials.batch-payment-rows', [
            'fieldName' => 'payments',
            'containerId' => 'other-payments-batch',
            'bankAccounts' => $bankAccounts ?? collect(),
            'defaultPaymentDate' => old('payment_date', optional($model->payment_date)->format('Y-m-d') ?? now()->toDateString()),
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
            <i class="fa fa-save"></i> Save Other Payment
        </button>
    </div>
</div>

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const paymentTypeSelect = document.getElementById('other_payment_type');
            const carField = document.getElementById('other-payment-car-field');
            const carSelect = document.getElementById('car_id');

            function toggleCarField() {
                if (!paymentTypeSelect || !carField || !carSelect) {
                    return;
                }

                const isVehicle = paymentTypeSelect.value === @json(\App\Models\OtherPayment::TYPE_VEHICLE);
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

            if (paymentTypeSelect) {
                paymentTypeSelect.addEventListener('change', toggleCarField);
                toggleCarField();
            }
        });
    </script>
@endpush
