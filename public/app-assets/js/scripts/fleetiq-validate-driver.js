/**
 * Driver form validation (mirrors DriverPersistenceService::validationRules).
 */
(function (global) {
    'use strict';

    var FV = global.FleetiqFormValidation;

    var DOCUMENT_FIELDS = [
        { name: 'driver_license_document', label: 'Driver License Document' },
        { name: 'driver_phd_license_document', label: 'PHD License Document' },
        { name: 'phd_card_document', label: 'PHD Card Document' },
        { name: 'dvla_license_summary', label: 'DVLA License Summary' },
        { name: 'misc_document', label: 'Miscellaneous Document' },
        { name: 'proof_of_address_document', label: 'Proof of Address' }
    ];

    function validateDriverForm(form, errors, options) {
        errors = errors || [];
        options = options || {};

        var requiredFields = [
            ['first_name', 'First Name'],
            ['last_name', 'Last Name'],
            ['dob', 'Date of Birth'],
            ['email', 'Email'],
            ['phone_number', 'Phone Number'],
            ['address1', 'Address Line 1'],
            ['post_code', 'Post Code'],
            ['town', 'Town'],
            ['country_id', 'Country'],
            ['driver_license_number', 'Driver License Number'],
            ['driver_license_expiry_date', 'Driver License Expiry Date'],
            ['next_of_kin', 'Next of Kin'],
            ['next_of_kin_phone', 'Next of Kin Phone']
        ];

        requiredFields.forEach(function (pair) {
            FV.requiredField(errors, form, pair[0], pair[1]);
        });

        var emailEl = FV.findField(form, 'email');
        if (emailEl && !FV.isEmpty(FV.fieldValue(emailEl)) && !FV.isValidEmail(FV.fieldValue(emailEl))) {
            FV.addError(errors, emailEl, 'Email', 'Email must be a valid email address.');
        }

        var dobEl = FV.findField(form, 'dob');
        if (dobEl && !FV.isEmpty(FV.fieldValue(dobEl)) && !FV.isValidDate(FV.fieldValue(dobEl))) {
            FV.addError(errors, dobEl, 'Date of Birth', 'Date of Birth must be a valid date.');
        }

        var licenseExpiry = FV.findField(form, 'driver_license_expiry_date');
        if (licenseExpiry && !FV.isEmpty(FV.fieldValue(licenseExpiry)) && !FV.isValidDate(FV.fieldValue(licenseExpiry))) {
            FV.addError(errors, licenseExpiry, 'Driver License Expiry Date', 'Driver License Expiry Date must be a valid date.');
        }

        var phdExpiry = FV.findField(form, 'phd_license_expiry_date');
        if (phdExpiry && !FV.isEmpty(FV.fieldValue(phdExpiry)) && !FV.isValidDate(FV.fieldValue(phdExpiry))) {
            FV.addError(errors, phdExpiry, 'PHD License Expiry Date', 'PHD License Expiry Date must be a valid date.');
        }

        DOCUMENT_FIELDS.forEach(function (doc) {
            var input = FV.findField(form, doc.name);
            if (input && !input.disabled) {
                FV.validateFileInput(errors, input, doc.label, { maxBytes: FV.FILE_MAX_2MB });
            }
        });

        return errors.length === 0;
    }

    global.validateDriverForm = validateDriverForm;
})(window);
