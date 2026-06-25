@php
    $bankAccounts = $bankAccounts ?? collect();
    $selectedValue = $selected ?? null;
    $fieldName = $name ?? 'bank_account_id';
    $fieldId = $id ?? 'bank_account_id';
    $wrapperClass = $wrapperClass ?? 'bank-account-field d-none';
    $errorKey = $errorKey ?? 'bank_account_id';
@endphp

<div class="{{ $wrapperClass }}" data-bank-account-field>
    <label for="{{ $fieldId }}" class="form-label">Bank Account <span class="text-danger">*</span></label>
    @if($bankAccounts->isEmpty())
        <div class="alert alert-warning mb-0 py-2">
            No bank accounts configured.
            <a href="{{ route('bank-accounts.index') }}">Add bank accounts</a>
        </div>
    @else
        <select name="{{ $fieldName }}" id="{{ $fieldId }}"
                class="form-control @error($errorKey) is-invalid @enderror"
                data-bank-account-select>
            <option value="">Select Bank Account</option>
            @foreach($bankAccounts as $account)
                <option value="{{ $account->id }}"
                    {{ (string) ($selectedValue ?? '') === (string) $account->id ? 'selected' : '' }}>
                    {{ $account->bank_name }}
                </option>
            @endforeach
        </select>
        @error($errorKey)
        <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    @endif
</div>
