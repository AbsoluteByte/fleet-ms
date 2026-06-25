@extends('layouts.admin', ['title' => 'Insurance import report'])
@section('css')
    <link rel="stylesheet" href="{{ asset('app-assets/vendors/css/tables/datatable/datatables.min.css') }}">
@endsection
@section('content')
    @php
        $successRows = array_values(array_filter($report['rows'], fn ($r) => ($r['status'] ?? '') === 'success'));
        $skippedRows = array_values(array_filter($report['rows'], fn ($r) => ($r['status'] ?? '') === 'skipped'));
        $failedRows = array_values(array_filter($report['rows'], fn ($r) => ($r['status'] ?? '') === 'failed'));
    @endphp
    <section>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                        <h4 class="card-title mb-0">Insurance import report</h4>
                        <div>
                            <a href="{{ route('car-insurance-import.index') }}" class="btn btn-primary btn-sm">
                                <i class="fa fa-upload"></i> Import again
                            </a>
                            <a href="{{ route('cars.index') }}" class="btn btn-outline-secondary btn-sm">
                                Cars list
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">
                            <strong>Provider:</strong> {{ $report['insurance_provider_name'] ?? '—' }}
                            &nbsp;|&nbsp;
                            <strong>Imported at:</strong> {{ $report['imported_at'] ?? '—' }}
                        </p>
                        <div class="row mb-2">
                            <div class="col-md-3">
                                <div class="border rounded p-2 text-center">
                                    <div class="text-muted small">Total files</div>
                                    <strong class="h4 mb-0">{{ $report['summary']['total'] ?? 0 }}</strong>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-2 text-center border-success">
                                    <div class="text-muted small">Imported</div>
                                    <strong class="h4 mb-0 text-success">{{ $report['summary']['success'] ?? 0 }}</strong>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-2 text-center border-warning">
                                    <div class="text-muted small">Skipped</div>
                                    <strong class="h4 mb-0 text-warning">{{ $report['summary']['skipped'] ?? 0 }}</strong>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-2 text-center border-danger">
                                    <div class="text-muted small">Failed</div>
                                    <strong class="h4 mb-0 text-danger">{{ $report['summary']['failed'] ?? 0 }}</strong>
                                </div>
                            </div>
                        </div>

                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab" href="#tab-success" role="tab">
                                    Imported ({{ count($successRows) }})
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#tab-skipped" role="tab">
                                    Skipped ({{ count($skippedRows) }})
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#tab-failed" role="tab">
                                    Failed ({{ count($failedRows) }})
                                </a>
                            </li>
                        </ul>
                        <div class="tab-content border-left border-right border-bottom p-2">
                            <div class="tab-pane active" id="tab-success" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered import-report-table" id="tableSuccess">
                                        <thead>
                                        <tr>
                                            <th>Registration</th>
                                            <th>Start</th>
                                            <th>Expiry</th>
                                            <th>File</th>
                                            <th>Details</th>
                                            <th>Verify</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @forelse($successRows as $row)
                                            <tr>
                                                <td><strong>{{ $row['registration'] ?? '—' }}</strong></td>
                                                <td>{{ $row['start_date'] ?? '—' }}</td>
                                                <td>{{ $row['expiry_date'] ?? '—' }}</td>
                                                <td class="text-nowrap">{{ $row['filename'] ?? '—' }}</td>
                                                <td>{{ $row['message'] ?? '' }}</td>
                                                <td>
                                                    @if(!empty($row['car_edit_url']))
                                                        <a href="{{ $row['car_edit_url'] }}" class="btn btn-sm btn-outline-primary"
                                                           target="_blank" rel="noopener noreferrer">
                                                            Edit car
                                                        </a>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">No successful imports.</td>
                                            </tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="tab-pane" id="tab-skipped" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered import-report-table" id="tableSkipped">
                                        <thead>
                                        <tr>
                                            <th>File</th>
                                            <th>Registration</th>
                                            <th>Reason</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @forelse($skippedRows as $row)
                                            <tr>
                                                <td>{{ $row['filename'] ?? '—' }}</td>
                                                <td>{{ $row['registration'] ?? '—' }}</td>
                                                <td>{{ $row['message'] ?? '—' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center text-muted">No skipped files.</td>
                                            </tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="tab-pane" id="tab-failed" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered import-report-table" id="tableFailed">
                                        <thead>
                                        <tr>
                                            <th>File</th>
                                            <th>Registration</th>
                                            <th>Error</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @forelse($failedRows as $row)
                                            <tr>
                                                <td>{{ $row['filename'] ?? '—' }}</td>
                                                <td>{{ $row['registration'] ?? '—' }}</td>
                                                <td>{{ $row['message'] ?? '—' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center text-muted">No failed files.</td>
                                            </tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('js')
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.min.js') }}"></script>
    <script src="{{ asset('app-assets/vendors/js/tables/datatable/datatables.bootstrap4.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            $('.import-report-table').each(function () {
                if ($.fn.DataTable.isDataTable(this)) {
                    return;
                }
                $(this).DataTable({
                    pageLength: 25,
                    order: [],
                    autoWidth: false,
                });
            });
        });
    </script>
@endsection
