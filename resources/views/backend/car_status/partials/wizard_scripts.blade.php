@php use App\Models\VehicleSwap; @endphp
<script>
    $(document).ready(function () {
        const SWAP_REASON_PHVL = @json(VehicleSwap::REASON_PHVL_ISSUES);
        const SWAP_REASON_OTHERS = @json(VehicleSwap::REASON_OTHERS);
        const PHVL_FAILED = @json(VehicleSwap::PHVL_FAILED);
        const PHVL_DOCUMENTATION = @json(VehicleSwap::PHVL_DOCUMENTATION);

        const $form = $('#fleet-status-form');
        const oldTarget = String($form.data('old-target') || '').trim();

        let carFleetFlags = {};
        const fleetFlagsDataEl = document.getElementById('fleet-car-fleet-flags-data');
        if (fleetFlagsDataEl) {
            try {
                carFleetFlags = JSON.parse(fleetFlagsDataEl.textContent || '{}');
            } catch (e) {
                carFleetFlags = {};
            }
        }

        /**
         * Hide the target status that matches the selected car's current fleet_status (no-op change).
         */
        function refreshTargetStatusOptionsForCar() {
            const $carSel = $('#fleet_wizard_car_id');
            const $opt = $carSel.find('option:selected');
            let current = '';
            if ($opt.length && $opt.val()) {
                current = String($opt.attr('data-fleet-status') || '').trim();
            }
            const $ts = $('#fleet_target_status');
            $ts.find('option').each(function () {
                const v = $(this).val();
                if (!v) {
                    $(this).prop('disabled', false);
                    return;
                }
                const disable = current !== '' && v === current;
                $(this).prop('disabled', disable);
            });
            if (current !== '' && String($ts.val() || '') === current) {
                $ts.val('');
            }
        }

        refreshTargetStatusOptionsForCar();
        $('#fleet_wizard_car_id').on('change', refreshTargetStatusOptionsForCar);

        function parseMoney(sel) {
            const v = parseFloat(String($(sel).val()).replace(',', '.'));
            return isNaN(v) ? 0 : v;
        }

        function refreshBalance(prefix) {
            const rent = parseMoney('#' + prefix + '_agreed_rent');
            const advance = parseMoney('#' + prefix + '_agreed_advance');
            const paid = parseMoney('#' + prefix + '_amount_paid');
            const ids = ['#' + prefix + '_agreed_rent', '#' + prefix + '_agreed_advance', '#' + prefix + '_amount_paid'];
            const hasAny = ids.some(function (id) {
                return String($(id).val()).trim() !== '';
            });
            const out = '#' + prefix + '_balance_payable_display';
            if (!hasAny) {
                $(out).val('');
                return;
            }
            const bal = Math.max(0, Math.round((rent + advance - paid) * 100) / 100);
            $(out).val(bal.toFixed(2));
        }

        $('#fleet_rsv_agreed_rent, #fleet_rsv_agreed_advance, #fleet_rsv_amount_paid').on('input change', function () {
            refreshBalance('fleet_rsv');
        });
        $('#fleet_swap_agreed_rent, #fleet_swap_agreed_advance, #fleet_swap_amount_paid').on('input change', function () {
            refreshBalance('fleet_swap');
        });

        function toggleSwapReasonSections() {
            const reason = String($('#fleet_swap_reason_for_swap').val() || '');
            const phvlWrap = $('#fleet_swap_phvl_issue_type_wrap');
            const phvlNotesWrap = $('#fleet_swap_phvl_issue_notes_wrap');
            const othersWrap = $('#fleet_swap_reason_notes_wrap');

            phvlWrap.toggleClass('d-none', reason !== SWAP_REASON_PHVL);
            othersWrap.toggleClass('d-none', reason !== SWAP_REASON_OTHERS);

            if (reason !== SWAP_REASON_PHVL) {
                phvlNotesWrap.addClass('d-none');
                return;
            }
            toggleFleetSwapPhvlNotes();
        }

        function toggleFleetSwapPhvlNotes() {
            const reason = String($('#fleet_swap_reason_for_swap').val() || '');
            const phvlNotesWrap = $('#fleet_swap_phvl_issue_notes_wrap');
            if (reason !== SWAP_REASON_PHVL) {
                phvlNotesWrap.addClass('d-none');
                return;
            }
            const t = String($('#fleet_swap_phvl_issue_type').val() || '');
            const needsNotes = t === PHVL_FAILED || t === PHVL_DOCUMENTATION;
            phvlNotesWrap.toggleClass('d-none', !needsNotes);
        }

        $('#fleet_swap_reason_for_swap').on('change', toggleSwapReasonSections);
        $('#fleet_swap_phvl_issue_type').on('change', toggleFleetSwapPhvlNotes);

        function toggleDamagedFault() {
            const v = String($('#fleet_damaged_fault_type').val() || '');
            $('.fleet-damaged-fault-only').toggleClass('d-none', v !== 'fault');
            $('.fleet-damaged-nonfault-only').toggleClass('d-none', v !== 'non_fault');
        }

        $('#fleet_damaged_fault_type').on('change', toggleDamagedFault);

        $('#fleet_payload_mechanical').on('change', function () {
            $('#fleet_payload_mechanical_notes_wrap').toggleClass('d-none', !this.checked);
        });

        function toggleWrittenFault() {
            const v = String($('#fleet_written_fault_type').val() || '');
            $('.fleet-written-fault-only').toggleClass('d-none', v !== 'fault');
            $('.fleet-written-nonfault-only').toggleClass('d-none', v !== 'non_fault');
        }

        $('#fleet_written_fault_type').on('change', toggleWrittenFault);

        function showPanel(status) {
            $('.fleet-status-panel').each(function () {
                const match = $(this).data('status') === status;
                $(this).toggleClass('d-none', !match);
            });
        }

        function fleetSelectedCarRegistration() {
            const opt = $('#fleet_wizard_car_id option:selected');
            if (!opt.val()) {
                return '';
            }
            const text = opt.text().trim();
            const dash = text.indexOf('—');
            if (dash === -1) {
                return text;
            }
            return text.slice(0, dash).trim();
        }

        function updateStep2Summary() {
            const reg = fleetSelectedCarRegistration();
            const statusVal = $('#fleet_target_status').val();
            let statusLabel = $('#fleet_target_status option:selected').text().trim();
            if (statusLabel === '— Select status —') {
                statusLabel = '';
            }
            const $h = $('#fleet_step2_summary');
            if (reg && statusVal && statusLabel) {
                $h.text(reg + ' status is updating to ' + statusLabel);
            } else {
                $h.text('');
            }
        }

        function fleetFlagsForCar(carId) {
            if (!carId) {
                return null;
            }
            return carFleetFlags[carId] || carFleetFlags[String(carId)] || null;
        }

        function updateAvailableForRentWarning() {
            const $box = $('#fleet_available_rent_warning');
            if (!$box.length) {
                return;
            }
            const status = $('#fleet_target_status').val();
            if (status !== 'available_for_rent') {
                $box.addClass('d-none').empty();
                return;
            }
            const carId = $('#fleet_wizard_car_id').val();
            const flags = fleetFlagsForCar(carId);
            const lines = [];
            if (flags && flags.active_reservation) {
                lines.push('This car has an <strong>active reservation</strong>. Submitting will cancel that reservation and mark the car available for rent.');
            }
            if (flags && flags.active_swap) {
                lines.push('This car is in an <strong>active vehicle swap</strong>. Submitting will remove the swap and update fleet status for the vehicles involved.');
            }
            if (lines.length === 0) {
                $box.addClass('d-none').empty();
                return;
            }
            $box.removeClass('d-none').html(
                '<strong>Warning:</strong><ul class="mb-0 mt-2">' +
                lines.map(function (t) {
                    return '<li>' + t + '</li>';
                }).join('') +
                '</ul>'
            );
        }

        $('#fleet_wizard_car_id, #fleet_target_status').on('change', function () {
            if (!$('#fleet_step2').hasClass('d-none')) {
                updateStep2Summary();
                updateAvailableForRentWarning();
            }
        });

        $('#fleet_wizard_next').on('click', function () {
            const carId = $('#fleet_wizard_car_id').val();
            const status = $('#fleet_target_status').val();
            if (!carId || !status) {
                alert('Please select a car and a target status.');
                return;
            }
            $('#fleet_hidden_swapped_with_car_id').val(status === 'vehicle_swap' ? String(carId) : '');
            $('#fleet_step1').addClass('d-none');
            $('#fleet_step2').removeClass('d-none');
            showPanel(status);
            refreshBalance('fleet_rsv');
            refreshBalance('fleet_swap');
            toggleSwapReasonSections();
            toggleFleetSwapPhvlNotes();
            toggleDamagedFault();
            toggleWrittenFault();
            updateStep2Summary();
            updateAvailableForRentWarning();
        });

        $('#fleet_wizard_back').on('click', function () {
            $('#fleet_step2').addClass('d-none');
            $('#fleet_step1').removeClass('d-none');
        });

        $form.on('submit', function (e) {
            if ($('#fleet_step2').hasClass('d-none')) {
                e.preventDefault();
                alert('Please click Next after selecting the car and status.');
                return false;
            }
            $('#fleet_hidden_swapped_with_car_id').prop('disabled', false);
            $('.fleet-status-panel').each(function () {
                const hidden = $(this).hasClass('d-none');
                $(this).find(':input').prop('disabled', hidden);
            });
            const ts = $('#fleet_target_status').val();
            if (ts !== 'vehicle_swap') {
                $('#fleet_hidden_swapped_with_car_id').prop('disabled', true);
            }
            return true;
        });

        if (oldTarget) {
            $('#fleet_hidden_swapped_with_car_id').val(
                oldTarget === 'vehicle_swap' ? String($('#fleet_wizard_car_id').val() || '') : ''
            );
            showPanel(oldTarget);
            refreshBalance('fleet_rsv');
            refreshBalance('fleet_swap');
            toggleSwapReasonSections();
            toggleFleetSwapPhvlNotes();
            toggleDamagedFault();
            toggleWrittenFault();
            updateStep2Summary();
            updateAvailableForRentWarning();
        }
    });
</script>
