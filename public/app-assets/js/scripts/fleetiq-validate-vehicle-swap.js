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

        var agreedRent = FV.findField(form, 'agreed_rent');
        if (agreedRent && !agreedRent.disabled) {
            var rentVal = FV.trim(FV.fieldValue(agreedRent));
            if (rentVal === '') {
                FV.addError(errors, agreedRent, 'New agreed rent', 'New agreed rent is required.');
            } else if (FV.parseNumber(rentVal) === null || FV.parseNumber(rentVal) < 0) {
                FV.addError(errors, agreedRent, 'New agreed rent', 'New agreed rent must be a valid number (0 or greater).');
            }
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
