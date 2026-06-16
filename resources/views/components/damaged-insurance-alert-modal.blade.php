<div class="modal fade" id="damagedActiveInsuranceAlertModal" tabindex="-1" role="dialog"
     aria-labelledby="damagedActiveInsuranceAlertModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="damagedActiveInsuranceAlertModalLabel">
                    <i class="fa fa-exclamation-triangle"></i> Insurance action required
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="mb-3">
                    The following damaged vehicle(s) are still covered by an <strong>active company insurance</strong> policy.
                    Please remove them from the company insurance scheme.
                </p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="thead-light">
                        <tr>
                            <th>Registration</th>
                            <th>Provider</th>
                            <th>Expiry</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody id="damagedActiveInsuranceAlertList"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">
                    Remind me later
                </button>
                <button type="button" class="btn btn-primary" id="damagedActiveInsuranceDismissBtn">
                    Don't show again today
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const alertUrl = @json(route('alerts.damaged-active-insurance'));
        const dismissUrl = @json(route('alerts.damaged-active-insurance.dismiss'));
        const csrfToken = @json(csrf_token());

        let pendingCars = [];

        function renderRows(cars) {
            const tbody = document.getElementById('damagedActiveInsuranceAlertList');
            if (!tbody) {
                return;
            }

            tbody.innerHTML = cars.map(function (car) {
                const provider = car.provider ? car.provider : '—';
                const expiry = car.expiry ? car.expiry : '—';

                return '<tr>' +
                    '<td><strong>' + car.registration + '</strong></td>' +
                    '<td>' + provider + '</td>' +
                    '<td>' + expiry + '</td>' +
                    '<td class="text-nowrap"><a href="' + car.edit_url + '" class="btn btn-sm btn-outline-primary">Edit car insurance</a></td>' +
                    '</tr>';
            }).join('');
        }

        function showModal(cars) {
            pendingCars = cars;
            renderRows(cars);

            if (window.jQuery) {
                jQuery('#damagedActiveInsuranceAlertModal').modal('show');
            }
        }

        async function loadAlert() {
            try {
                const response = await fetch(alertUrl, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    return;
                }

                const data = await response.json();
                const cars = Array.isArray(data.cars) ? data.cars : [];

                if (cars.length > 0) {
                    showModal(cars);
                }
            } catch (error) {
                console.error('Damaged insurance alert failed:', error);
            }
        }

        async function dismissAlert() {
            if (!pendingCars.length) {
                if (window.jQuery) {
                    jQuery('#damagedActiveInsuranceAlertModal').modal('hide');
                }
                return;
            }

            try {
                await fetch(dismissUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        car_ids: pendingCars.map(function (car) { return car.id; }),
                    }),
                });
            } catch (error) {
                console.error('Damaged insurance dismiss failed:', error);
            }

            pendingCars = [];

            if (window.jQuery) {
                jQuery('#damagedActiveInsuranceAlertModal').modal('hide');
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const dismissBtn = document.getElementById('damagedActiveInsuranceDismissBtn');
            if (dismissBtn) {
                dismissBtn.addEventListener('click', dismissAlert);
            }

            loadAlert();
        });
    })();
</script>
