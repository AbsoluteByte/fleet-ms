/**
 * Car form validation (mirrors CarController store/update + validateMotPhvDocuments).
 */
(function (global) {
    'use strict';

    var FV = global.FleetiqFormValidation;

    var MOT_DETAIL_KEYS = ['test_date', 'expiry_date', 'amount', 'term'];

    function insuranceStatusName(form) {
        var sel = FV.findField(form, 'insurance_status_id');
        if (!sel || sel.selectedIndex < 0) {
            return '';
        }
        var opt = sel.options[sel.selectedIndex];
        return opt ? FV.trim(opt.text).toLowerCase() : '';
    }

    function motRowHasProvidedDetails(form, index, row) {
        if (FV.rowHasAnyValue(row, MOT_DETAIL_KEYS)) {
            return true;
        }
        var fileInput = form.querySelector('[name="mots[' + index + '][document]"]');
        return fileInput && fileInput.files && fileInput.files.length > 0;
    }

    function phvRowHasProvidedDetails(form, index, row) {
        var keys = ['counsel_id', 'amount', 'start_date', 'expiry_date', 'notify_before_expiry'];
        if (FV.rowHasAnyValue(row, keys)) {
            return true;
        }
        var fileInput = form.querySelector('[name="phvs[' + index + '][document]"]');
        return fileInput && fileInput.files && fileInput.files.length > 0;
    }

    function hasExistingDocumentLink(rowContainer) {
        if (!rowContainer) {
            return false;
        }
        return !!rowContainer.querySelector('.car-doc-remove-btn, a.document-view-link, [data-has-document="1"]');
    }

    function validateCarForm(form, errors) {
        errors = errors || [];

        FV.requiredField(errors, form, 'company_id', 'Company');
        FV.requiredField(errors, form, 'car_model_id', 'Car model');
        FV.requiredField(errors, form, 'color', 'Color');
        FV.requiredField(errors, form, 'vin', 'VIN');
        FV.requiredField(errors, form, 'manufacture_year', 'Manufacture year');
        FV.requiredField(errors, form, 'purchase_date', 'Purchase date');
        FV.requiredField(errors, form, 'purchase_price', 'Purchase price');
        FV.requiredField(errors, form, 'purchase_type', 'Purchase type');

        var yearEl = FV.findField(form, 'manufacture_year');
        if (yearEl && !FV.isEmpty(FV.fieldValue(yearEl))) {
            var year = parseInt(FV.fieldValue(yearEl), 10);
            var maxYear = new Date().getFullYear();
            if (isNaN(year) || year < 1900 || year > maxYear) {
                FV.addError(errors, yearEl, 'Manufacture year', 'Manufacture year must be between 1900 and ' + maxYear + '.');
            }
        }

        var priceEl = FV.findField(form, 'purchase_price');
        if (priceEl && !FV.isEmpty(FV.fieldValue(priceEl))) {
            var price = FV.parseNumber(FV.fieldValue(priceEl));
            if (price === null || price < 0) {
                FV.addError(errors, priceEl, 'Purchase price', 'Purchase price must be 0 or greater.');
            }
        }

        var v5Inputs = form.querySelectorAll('input[type="file"][name="v5_document[]"], input[type="file"][name="v5_document"]');
        v5Inputs.forEach(function (v5Input) {
            FV.validateFileInput(errors, v5Input, 'V5 document', { maxBytes: FV.FILE_MAX_10MB });
        });
        form.querySelectorAll('[name^="old_log_book"]').forEach(function (input) {
            if (input.type === 'file') {
                FV.validateFileInput(errors, input, 'Old log book', { maxBytes: FV.FILE_MAX_10MB });
            }
        });

        var reserveCar = form.querySelector('#reserve_car');
        if (reserveCar && reserveCar.checked) {
            FV.requiredField(errors, form, 'reservation_customer_name', 'Reservation customer name');
            var resEmail = FV.findField(form, 'reservation_customer_email');
            if (resEmail && !FV.isEmpty(FV.fieldValue(resEmail)) && !FV.isValidEmail(FV.fieldValue(resEmail))) {
                FV.addError(errors, resEmail, 'Reservation customer email', 'Reservation customer email must be valid.');
            }
        }

        var motRows = FV.getIndexedRows(form, 'mots');
        Object.keys(motRows).forEach(function (index) {
            if (form.querySelector('#mots-preserved input[name="mots[' + index + '][id]"]')) {
                return;
            }
            var row = motRows[index];
            if (!motRowHasProvidedDetails(form, index, row)) {
                return;
            }
            var rowEl = form.querySelector('.mot-item[data-index="' + index + '"]')
                || form.querySelector('[name="mots[' + index + '][expiry_date]"]');
            rowEl = rowEl && rowEl.closest ? (rowEl.closest('.mot-item') || rowEl) : rowEl;

            var fileInput = form.querySelector('[name="mots[' + index + '][document]"]');
            var hasDoc = (fileInput && fileInput.files && fileInput.files.length) || hasExistingDocumentLink(rowEl);
            if (!hasDoc) {
                FV.addError(errors, fileInput, 'MOT document', 'MOT document is required when MOT details are provided.');
            }
            if (FV.isEmpty(row.test_date)) {
                var testDateEl = form.querySelector('[name="mots[' + index + '][test_date]"]');
                FV.addError(errors, testDateEl, 'MOT test date', 'Test date is required when MOT details are provided.');
            }
            if (fileInput) {
                FV.validateFileInput(errors, fileInput, 'MOT document', { maxBytes: FV.FILE_MAX_10MB });
            }
        });

        var phvRows = FV.getIndexedRows(form, 'phvs');
        Object.keys(phvRows).forEach(function (index) {
            if (form.querySelector('#phv-preserved input[name="phvs[' + index + '][id]"]')) {
                return;
            }
            var row = phvRows[index];
            if (!phvRowHasProvidedDetails(form, index, row)) {
                return;
            }
            var rowEl = form.querySelector('.phv-item[data-index="' + index + '"]')
                || form.querySelector('[name="phvs[' + index + '][counsel_id]"]');
            rowEl = rowEl && rowEl.closest ? (rowEl.closest('.phv-item') || rowEl) : rowEl;

            var fileInput = form.querySelector('[name="phvs[' + index + '][document]"]');
            var hasDoc = (fileInput && fileInput.files && fileInput.files.length) || hasExistingDocumentLink(rowEl);
            if (!hasDoc) {
                FV.addError(errors, fileInput, 'PHV document', 'PHV document is required when PHV details are provided.');
            }
            if (fileInput) {
                FV.validateFileInput(errors, fileInput, 'PHV document', { maxBytes: FV.FILE_MAX_10MB });
            }
        });

        var hasInsurance = form.querySelector('#has_insurance');
        if (hasInsurance && hasInsurance.checked) {
            FV.requiredField(errors, form, 'insurance_provider_id', 'Insurance provider');
            FV.requiredField(errors, form, 'insurance_status_id', 'Insurance status');

            var statusName = insuranceStatusName(form);
            if (statusName === 'applied') {
                FV.requiredField(errors, form, 'insurance_applied_date', 'Insurance date applied');
            } else if (statusName === 'cancelled' || statusName === 'canceled') {
                FV.requiredField(errors, form, 'insurance_canceled_date', 'Insurance canceled date');
                var insDoc = FV.findField(form, 'insurance_document');
                var hasInsDoc = (insDoc && insDoc.files && insDoc.files.length)
                    || (insDoc && insDoc.closest('.form-group') && insDoc.closest('.form-group').querySelector('.car-doc-remove-btn'));
                if (!hasInsDoc) {
                    FV.addError(errors, insDoc, 'Insurance document', 'Insurance document is required for cancelled insurance status.');
                }
            } else if (statusName === 'active') {
                FV.requiredField(errors, form, 'insurance_start_date', 'Insurance start date');
                FV.requiredField(errors, form, 'insurance_expiry_date', 'Insurance expiry date');
                FV.requiredField(errors, form, 'insurance_notify_before_expiry', 'Notify before expiry');
            }

            var insDocInput = FV.findField(form, 'insurance_document');
            if (insDocInput) {
                FV.validateFileInput(errors, insDocInput, 'Insurance document', { maxBytes: FV.FILE_MAX_10MB });
            }
        }

        return errors.length === 0;
    }

    global.validateCarForm = validateCarForm;
})(window);
