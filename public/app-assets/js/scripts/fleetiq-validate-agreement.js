/**
 * Agreement form validation (mirrors AgreementController::validateAgreementRequest).
 */
(function (global) {
    'use strict';

    var FV = global.FleetiqFormValidation;

    function isReplacementVehicle(form) {
        var cfg = global.fleetiqAgreementValidation || {};
        var statusEl = FV.findField(form, 'status_id');
        if (!statusEl || cfg.replacementVehicleStatusId == null) {
            return false;
        }
        return String(FV.fieldValue(statusEl)) === String(cfg.replacementVehicleStatusId);
    }

    function usingOwnInsurance(form) {
        var clientRadio = form.querySelector('#using_own_insurance_client');
        return clientRadio && clientRadio.checked;
    }

    function validateAgreementForm(form, errors) {
        errors = errors || [];

        FV.requiredField(errors, form, 'company_id', 'Company');
        FV.requiredField(errors, form, 'driver_id', 'Driver');
        FV.requiredField(errors, form, 'car_id', 'Vehicle');
        FV.requiredField(errors, form, 'status_id', 'Status');
        FV.requiredField(errors, form, 'start_date', 'Start date');
        FV.requiredField(errors, form, 'end_date', 'End date');

        FV.dateOnOrAfter(
            errors,
            FV.findField(form, 'start_date'),
            FV.findField(form, 'end_date'),
            'Start date',
            'End date',
            'End date must be on or after start date.'
        );

        if (isReplacementVehicle(form)) {
            FV.requiredField(errors, form, 'parent_agreement_id', 'Original agreement');
        } else {
            FV.requiredField(errors, form, 'agreed_rent', 'Agreed rent');
            FV.requiredField(errors, form, 'rent_interval', 'Rent interval');
            FV.requiredField(errors, form, 'deposit_amount', 'Deposit amount');
            FV.requiredField(errors, form, 'collection_type', 'Collection type');

            var rentEl = FV.findField(form, 'agreed_rent');
            var depositEl = FV.findField(form, 'deposit_amount');
            if (rentEl && !FV.isEmpty(FV.fieldValue(rentEl))) {
                var rent = FV.parseNumber(FV.fieldValue(rentEl));
                if (rent === null || rent < 0) {
                    FV.addError(errors, rentEl, 'Agreed rent', 'Agreed rent must be 0 or greater.');
                }
            }
            if (depositEl && !FV.isEmpty(FV.fieldValue(depositEl))) {
                var deposit = FV.parseNumber(FV.fieldValue(depositEl));
                if (deposit === null || deposit < 0) {
                    FV.addError(errors, depositEl, 'Deposit amount', 'Deposit amount must be 0 or greater.');
                }
            }

            var addPayment = form.querySelector('#add_payment');
            if (addPayment && addPayment.checked) {
                var paymentRows = form.querySelectorAll('[data-payment-row]');
                if (!paymentRows.length) {
                    paymentRows = form.querySelectorAll('#agreement-payment-rows .agreement-payment-row');
                }
                if (!paymentRows.length) {
                    paymentRows = form.querySelectorAll('#agreement-payment-rows > div');
                }

                paymentRows.forEach(function (row, idx) {
                    var method = row.querySelector('[name*="[payment_method]"]');
                    var date = row.querySelector('[name*="[payment_date]"]');
                    var amount = row.querySelector('[name*="[amount]"]');
                    var rowNum = idx + 1;
                    FV.requiredVisible(errors, method, 'Payment ' + rowNum + ' method');
                    FV.requiredVisible(errors, date, 'Payment ' + rowNum + ' date');
                    FV.requiredVisible(errors, amount, 'Payment ' + rowNum + ' amount');
                    if (amount && !FV.isEmpty(FV.fieldValue(amount))) {
                        var amt = FV.parseNumber(FV.fieldValue(amount));
                        if (amt === null || amt < 0.01) {
                            FV.addError(errors, amount, 'Payment amount', 'Payment ' + rowNum + ' amount must be at least 0.01.');
                        }
                    }
                });
            }
        }

        if (usingOwnInsurance(form)) {
            FV.requiredField(errors, form, 'own_insurance_provider_name', 'Insurance provider name');
            FV.requiredField(errors, form, 'own_insurance_start_date', 'Insurance start date');
            FV.requiredField(errors, form, 'own_insurance_end_date', 'Insurance end date');
            FV.requiredField(errors, form, 'own_insurance_type', 'Insurance type');
            FV.requiredField(errors, form, 'own_insurance_policy_number', 'Policy number');

            FV.dateOnOrAfter(
                errors,
                FV.findField(form, 'own_insurance_start_date'),
                FV.findField(form, 'own_insurance_end_date'),
                'Insurance start date',
                'Insurance end date',
                'Insurance end date must be after insurance start date.'
            );

            var proofInput = FV.findField(form, 'own_insurance_proof_document');
            if (proofInput) {
                FV.validateFileInput(errors, proofInput, 'Proof of insurance document', { maxBytes: FV.FILE_MAX_2MB });
            }
        }

        var mileageOut = FV.findField(form, 'mileage_out');
        var mileageIn = FV.findField(form, 'mileage_in');
        if (mileageOut && mileageIn && !FV.isEmpty(FV.fieldValue(mileageOut)) && !FV.isEmpty(FV.fieldValue(mileageIn))) {
            var outVal = parseInt(FV.fieldValue(mileageOut), 10);
            var inVal = parseInt(FV.fieldValue(mileageIn), 10);
            if (!isNaN(outVal) && !isNaN(inVal) && inVal < outVal) {
                FV.addError(errors, mileageIn, 'Mileage in', 'Mileage in must be greater than or equal to mileage out.');
            }
        }

        return errors.length === 0;
    }

    global.validateAgreementForm = validateAgreementForm;
})(window);
