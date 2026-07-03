/**
 * Reservation form validation (mirrors ReservationController::validatedReservationPayload).
 */
(function (global) {
    'use strict';

    var FV = global.FleetiqFormValidation;

    function getDriverSection(form) {
        return form ? form.querySelector('#reservation-driver-section') : null;
    }

    function isCompleteLinkedDriverMode(form) {
        var section = getDriverSection(form);

        return !!(section && section.getAttribute('data-complete-linked-driver') === '1');
    }

    function isDriverProfileComplete(form) {
        var section = getDriverSection(form);

        return !section || section.getAttribute('data-driver-complete') === '1';
    }

    function validateReservationForm(form, errors, options) {
        errors = errors || [];
        options = options || {};

        FV.requiredField(errors, form, 'reservation_date', 'Reservation date');
        FV.requiredField(errors, form, 'pick_up_date', 'Pick up date');

        ['agreed_rent', 'agreed_advance', 'amount_paid'].forEach(function (id) {
            var el = FV.findField(form, id);
            var labels = { agreed_rent: 'Agreed rent', agreed_advance: 'Agreed advance', amount_paid: 'Amount paid' };
            if (!el || el.disabled) {
                return;
            }
            var val = FV.trim(FV.fieldValue(el));
            if (val === '') {
                FV.addError(errors, el, labels[id], labels[id] + ' is required.');
            } else if (FV.parseNumber(val) === null || FV.parseNumber(val) < 0) {
                FV.addError(errors, el, labels[id], labels[id] + ' must be a valid number (0 or greater).');
            }
        });

        var amountPaidEl = FV.findField(form, 'amount_paid');
        var amountPaid = amountPaidEl ? FV.parseNumber(FV.fieldValue(amountPaidEl)) : null;
        if (amountPaid !== null && amountPaid > 0) {
            FV.requiredField(errors, form, 'payment_method', 'Payment method');

            var paymentMethodEl = FV.findField(form, 'payment_method');
            var paymentMethod = paymentMethodEl ? FV.trim(FV.fieldValue(paymentMethodEl)) : '';

            if (paymentMethod === 'Bank Transfer') {
                var bankAccountSelect = form.querySelector('[data-bank-account-select]');
                var bankAccountsConfigured = bankAccountSelect && !bankAccountSelect.disabled;

                if (bankAccountsConfigured) {
                    FV.requiredField(errors, form, 'bank_account_id', 'Bank account');
                }
            }
        }

        if (isCompleteLinkedDriverMode(form) && typeof global.validateDriverForm === 'function') {
            global.validateDriverForm(form, errors, { embedded: true });
        } else {
            var driverModeEl = form.querySelector('input[name="driver_mode"]:checked')
                || form.querySelector('input[name="driver_mode"][type="hidden"]');
            var driverMode = driverModeEl ? driverModeEl.value : 'new';

            if (driverMode === 'existing') {
                FV.requiredField(errors, form, 'driver_id', 'Driver');
            } else if (typeof global.validateDriverForm === 'function') {
                var driverOptions = { embedded: true };
                if (options.minimalNewDriver) {
                    driverOptions.minimal = true;
                }
                global.validateDriverForm(form, errors, driverOptions);
            }
        }

        return errors.length === 0;
    }

    function validateReservationForAgreement(form, errors) {
        errors = errors || [];

        if (!isDriverProfileComplete(form)) {
            if (isCompleteLinkedDriverMode(form) && typeof global.validateDriverForm === 'function') {
                global.validateDriverForm(form, errors, { embedded: true });
                if (errors.length > 0) {
                    return false;
                }
            }

            errors.push({
                field: null,
                label: 'Driver',
                message: 'Complete and save the driver profile on this page before creating an agreement.'
            });

            return false;
        }

        if (!validateReservationForm(form, errors)) {
            return false;
        }

        var driverModeEl = form.querySelector('input[name="driver_mode"]:checked')
            || form.querySelector('input[name="driver_mode"][type="hidden"]');
        var driverMode = driverModeEl ? driverModeEl.value : 'existing';

        if (driverMode === 'new') {
            errors.push({
                field: null,
                label: 'Driver',
                message: 'Please save the reservation first when adding a new driver, then create the agreement.'
            });
            return false;
        }

        FV.requiredField(errors, form, 'driver_id', 'Driver');
        FV.requiredField(errors, form, 'car_id', 'Car');

        return errors.length === 0;
    }

    global.validateReservationForm = validateReservationForm;
    global.validateReservationForAgreement = validateReservationForAgreement;
})(window);
