/**
 * Vehicle swap form validation (mirrors VehicleSwapController::validatedSwapPayload).
 */
(function (global) {
    'use strict';

    var FV = global.FleetiqFormValidation;

    function validateVehicleSwapForm(form, errors) {
        errors = errors || [];
        var cfg = global.fleetiqVehicleSwapValidation || {};

        FV.requiredField(errors, form, 'old_car_id', 'Old car');
        FV.requiredField(errors, form, 'swapped_with_car_id', 'Swapped with');
        FV.requiredField(errors, form, 'customer_name', 'Client\'s name');
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

        var emailEl = FV.findField(form, 'customer_email');
        if (emailEl && !FV.isEmpty(FV.fieldValue(emailEl)) && !FV.isValidEmail(FV.fieldValue(emailEl))) {
            FV.addError(errors, emailEl, 'Email', 'Email must be a valid email address.');
        }

        FV.requiredField(errors, form, 'reason_for_swap', 'Reason for swap');

        var oldCar = FV.findField(form, 'old_car_id');
        var newCar = FV.findField(form, 'swapped_with_car_id');
        if (oldCar && newCar && FV.trim(FV.fieldValue(oldCar)) && FV.trim(FV.fieldValue(oldCar)) === FV.trim(FV.fieldValue(newCar))) {
            FV.addError(errors, newCar, 'Swapped with', 'Swapped with car must be different from old car.');
        }

        var reason = FV.trim(FV.fieldValue(FV.findField(form, 'reason_for_swap')));
        var phvlReason = cfg.reasonPhvl || 'phvl_issues';
        var othersReason = cfg.reasonOthers || 'others';
        var phvlFailed = cfg.phvlFailed || 'failed';
        var phvlDocumentation = cfg.phvlDocumentation || 'documentation';

        if (reason === phvlReason) {
            FV.requiredField(errors, form, 'phvl_issue_type', 'PHVL issue type');
            var phvlType = FV.trim(FV.fieldValue(FV.findField(form, 'phvl_issue_type')));
            if (phvlType === phvlFailed || phvlType === phvlDocumentation) {
                FV.requiredField(errors, form, 'phvl_issue_notes', 'PHVL issue notes');
            }
        }

        if (reason === othersReason) {
            FV.requiredField(errors, form, 'reason_notes', 'Reason notes');
        }

        return errors.length === 0;
    }

    global.validateVehicleSwapForm = validateVehicleSwapForm;
})(window);
