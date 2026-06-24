/**
 * Reservation form validation (mirrors ReservationController::validatedReservationPayload).
 */
(function (global) {
    'use strict';

    var FV = global.FleetiqFormValidation;

    function validateReservationForm(form, errors) {
        errors = errors || [];

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

        var driverModeEl = form.querySelector('input[name="driver_mode"]:checked');
        var driverMode = driverModeEl ? driverModeEl.value : 'new';

        if (driverMode === 'existing') {
            FV.requiredField(errors, form, 'driver_id', 'Driver');
        } else if (typeof global.validateDriverForm === 'function') {
            global.validateDriverForm(form, errors, { embedded: true });
        }

        return errors.length === 0;
    }

    global.validateReservationForm = validateReservationForm;
})(window);
