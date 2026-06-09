@extends('layouts.admin', ['title' => 'Add vehicle swap'])
@section('css')
    <style>
        .vehicle-swap-form-card > .card-header {
            padding-bottom: 1.25rem;
        }
    </style>
@endsection
@section('content')
    <section>
        <div class="row">
            <div class="col-12">
                <div class="card vehicle-swap-form-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Add vehicle swap</h4>
                        <a href="{{ route('vehicle-swaps.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fa fa-arrow-left"></i> Back to vehicle swaps
                        </a>
                    </div>
                    <hr class="my-0">
                    <div class="card-content">
                        <div class="card-body">
                            @include('alerts')
                            <form method="POST" action="{{ route('vehicle-swaps.store') }}" id="formCreateVehicleSwap">
                                @csrf
                                @include('backend.vehicle_swaps._form', ['cars' => $cars, 'vehicleSwap' => null])
                                <div class="form-group mb-0">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-check"></i> Save vehicle swap
                                    </button>
                                    <a href="{{ route('vehicle-swaps.index') }}" class="btn btn-outline-secondary ml-1">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('js')
    @php use App\Models\VehicleSwap; @endphp
    <script>
        $(document).ready(function () {
            const SWAP_REASON_PHVL = @json(VehicleSwap::REASON_PHVL_ISSUES);
            const SWAP_REASON_OTHERS = @json(VehicleSwap::REASON_OTHERS);

            function parseMoney(id) {
                const v = parseFloat(String($(id).val()).replace(',', '.'));
                return isNaN(v) ? 0 : v;
            }

            function refreshBalanceDisplay() {
                const rent = parseMoney('#agreed_rent');
                const advance = parseMoney('#agreed_advance');
                const paid = parseMoney('#amount_paid');
                const hasAny = ['#agreed_rent', '#agreed_advance', '#amount_paid'].some(function (sel) {
                    return String($(sel).val()).trim() !== '';
                });
                if (!hasAny) {
                    $('#balance_payable_on_pickup_display').val('');
                    return;
                }
                const bal = Math.max(0, Math.round((rent + advance - paid) * 100) / 100);
                $('#balance_payable_on_pickup_display').val(bal.toFixed(2));
            }

            function toggleReasonSections() {
                const reason = String($('#reason_for_swap').val() || '');
                const phvlWrap = $('#swap_phvl_issue_type_wrap');
                const phvlNotesWrap = $('#swap_phvl_issue_notes_wrap');
                const othersWrap = $('#swap_reason_notes_wrap');

                phvlWrap.toggleClass('d-none', reason !== SWAP_REASON_PHVL);
                othersWrap.toggleClass('d-none', reason !== SWAP_REASON_OTHERS);

                if (reason !== SWAP_REASON_PHVL) {
                    phvlNotesWrap.addClass('d-none');
                    return;
                }
                togglePhvlNotes();
            }

            function togglePhvlNotes() {
                const reason = String($('#reason_for_swap').val() || '');
                const phvlNotesWrap = $('#swap_phvl_issue_notes_wrap');
                if (reason !== SWAP_REASON_PHVL) {
                    phvlNotesWrap.addClass('d-none');
                    return;
                }
                const t = String($('#phvl_issue_type').val() || '');
                phvlNotesWrap.toggleClass('d-none', t === '');
            }

            $('#reason_for_swap').on('change', toggleReasonSections);
            $('#phvl_issue_type').on('change', togglePhvlNotes);

            $('#agreed_rent, #agreed_advance, #amount_paid').on('input change', refreshBalanceDisplay);
            toggleReasonSections();
            togglePhvlNotes();
            refreshBalanceDisplay();
        });
    </script>
@endsection
