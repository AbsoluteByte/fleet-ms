/**
 * FleetIQ shared client-side form validation.
 * Red borders on invalid fields; error messages in a top summary alert.
 */
(function (global) {
    'use strict';

    var SUMMARY_CLASS = 'js-form-validation-summary';
    var INVALID_CLASS = 'is-invalid';

    function trim(value) {
        return String(value == null ? '' : value).trim();
    }

    function isEmpty(value) {
        return trim(value) === '';
    }

    function fieldValue(el) {
        if (!el) {
            return '';
        }
        if (el.type === 'checkbox') {
            return el.checked ? '1' : '';
        }
        if (el.type === 'radio') {
            var checked = el.form ? el.form.querySelector('input[name="' + el.name + '"]:checked') : null;
            return checked ? checked.value : '';
        }
        if (el.type === 'file') {
            return el.files && el.files.length ? '1' : '';
        }
        return el.value;
    }

    function findField(form, nameOrId) {
        if (!form || !nameOrId) {
            return null;
        }
        return form.querySelector('#' + CSS.escape(nameOrId))
            || form.querySelector('[name="' + nameOrId.replace(/"/g, '\\"') + '"]')
            || form.querySelector('[name="' + nameOrId + '"]');
    }

    function findFields(form, name) {
        if (!form || !name) {
            return [];
        }
        return Array.prototype.slice.call(form.querySelectorAll('[name="' + name + '"]'));
    }

    function markInvalid(el) {
        if (!el) {
            return;
        }
        el.classList.add(INVALID_CLASS);
        if (el.tagName === 'SELECT' && global.jQuery && global.jQuery.fn.select2) {
            var $el = global.jQuery(el);
            if ($el.hasClass('select2-hidden-accessible')) {
                $el.next('.select2-container').addClass(INVALID_CLASS);
            }
        }
    }

    function clearInvalid(form) {
        if (!form) {
            return;
        }
        form.querySelectorAll('.' + INVALID_CLASS).forEach(function (el) {
            el.classList.remove(INVALID_CLASS);
        });
        var summary = form.querySelector('.' + SUMMARY_CLASS);
        if (summary) {
            summary.remove();
        }
    }

    function ensureSummary(form) {
        var existing = form.querySelector('.' + SUMMARY_CLASS);
        if (existing) {
            return existing;
        }
        var alert = document.createElement('div');
        alert.className = 'alert alert-danger mb-2 ' + SUMMARY_CLASS;
        alert.setAttribute('role', 'alert');
        alert.innerHTML = '<strong>Please fix the following:</strong><ul class="mb-0 mt-1"></ul>';
        form.insertBefore(alert, form.firstChild);
        return alert;
    }

    function showSummary(form, errors) {
        var alert = ensureSummary(form);
        var list = alert.querySelector('ul');
        list.innerHTML = '';
        errors.forEach(function (err) {
            var li = document.createElement('li');
            li.textContent = err.message;
            list.appendChild(li);
            if (err.element) {
                markInvalid(err.element);
            } else if (err.name) {
                var el = findField(form, err.name);
                if (el) {
                    markInvalid(el);
                }
            }
        });
        alert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function addError(errors, element, label, message) {
        errors.push({
            element: element || null,
            name: element ? element.name || element.id : null,
            label: label || '',
            message: message
        });
    }

    function requiredField(errors, form, nameOrId, label, message) {
        var el = findField(form, nameOrId);
        if (!el || el.disabled) {
            return;
        }
        if (isEmpty(fieldValue(el))) {
            addError(errors, el, label, message || (label + ' is required.'));
        }
    }

    function requiredVisible(errors, el, label, message) {
        if (!el || el.disabled || el.offsetParent === null) {
            return;
        }
        if (isEmpty(fieldValue(el))) {
            addError(errors, el, label, message || (label + ' is required.'));
        }
    }

    function isValidDate(value) {
        if (isEmpty(value)) {
            return false;
        }
        var d = new Date(value + 'T00:00:00');
        return !isNaN(d.getTime());
    }

    function isValidEmail(value) {
        if (isEmpty(value)) {
            return false;
        }
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
    }

    function parseNumber(value) {
        var n = parseFloat(String(value).replace(',', '.'));
        return isNaN(n) ? null : n;
    }

    function dateOnOrAfter(errors, startEl, endEl, startLabel, endLabel, message) {
        if (!startEl || !endEl || startEl.disabled || endEl.disabled) {
            return;
        }
        var start = trim(fieldValue(startEl));
        var end = trim(fieldValue(endEl));
        if (!start || !end) {
            return;
        }
        if (new Date(end + 'T00:00:00') < new Date(start + 'T00:00:00')) {
            addError(errors, endEl, endLabel, message || (endLabel + ' must be on or after ' + startLabel + '.'));
        }
    }

    var FILE_MIMES_DEFAULT = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];

    function validateFileInput(errors, input, label, options) {
        if (!input || input.disabled || !input.files || !input.files.length) {
            return;
        }
        var opts = options || {};
        var maxBytes = opts.maxBytes || 10485760;
        var mimes = opts.mimes || FILE_MIMES_DEFAULT;
        Array.prototype.forEach.call(input.files, function (file) {
            if (file.size > maxBytes) {
                var mb = Math.round(maxBytes / 1024 / 1024);
                addError(errors, input, label, label + ': file "' + file.name + '" exceeds ' + mb + 'MB.');
            }
            if (mimes.indexOf(file.type) === -1) {
                addError(errors, input, label, label + ': file "' + file.name + '" must be PDF, JPG, JPEG, or PNG.');
            }
        });
    }

    function rowHasAnyValue(row, keys) {
        return keys.some(function (key) {
            var val = row[key];
            return val !== null && val !== undefined && trim(val) !== '';
        });
    }

    function getIndexedRows(form, prefix) {
        var rows = {};
        var re = new RegExp('^' + prefix.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\[(\\d+)\\]\\[([^\\]]+)\\]$');
        form.querySelectorAll('[name^="' + prefix + '["]').forEach(function (el) {
            if (el.disabled || el.type === 'file') {
                return;
            }
            var match = el.name.match(re);
            if (!match) {
                return;
            }
            var index = match[1];
            var key = match[2];
            if (!rows[index]) {
                rows[index] = { _index: index };
            }
            rows[index][key] = fieldValue(el);
        });
        form.querySelectorAll('[name^="' + prefix + '["][type="file"]').forEach(function (el) {
            if (el.disabled) {
                return;
            }
            var match = el.name.match(re);
            if (!match) {
                return;
            }
            var index = match[1];
            if (!rows[index]) {
                rows[index] = { _index: index };
            }
            if (el.files && el.files.length) {
                rows[index]._hasFile = true;
            }
        });
        return rows;
    }

    function attach(form, validateFn) {
        if (!form) {
            return;
        }
        form.setAttribute('novalidate', 'novalidate');
        form.addEventListener('submit', function (e) {
            clearInvalid(form);
            var errors = [];
            try {
                validateFn(form, errors);
            } catch (err) {
                console.error(err);
                addError(errors, null, 'Form', 'Validation failed. Please check your entries.');
            }
            if (errors.length) {
                e.preventDefault();
                e.stopPropagation();
                showSummary(form, errors);
                return false;
            }
            return true;
        });
    }

    global.FleetiqFormValidation = {
        attach: attach,
        clear: clearInvalid,
        fail: showSummary,
        addError: addError,
        requiredField: requiredField,
        requiredVisible: requiredVisible,
        isEmpty: isEmpty,
        trim: trim,
        fieldValue: fieldValue,
        findField: findField,
        findFields: findFields,
        isValidDate: isValidDate,
        isValidEmail: isValidEmail,
        parseNumber: parseNumber,
        dateOnOrAfter: dateOnOrAfter,
        validateFileInput: validateFileInput,
        rowHasAnyValue: rowHasAnyValue,
        getIndexedRows: getIndexedRows,
        FILE_MIMES_DEFAULT: FILE_MIMES_DEFAULT,
        FILE_MAX_2MB: 2097152,
        FILE_MAX_10MB: 10485760
    };
})(window);
