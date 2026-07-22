@extends('layouts.admin', ['title' => 'Daily Financial Sheet'])

@section('content')
    <section id="basic-datatable">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header" style="position: static; z-index: unset; width: 100%;">
                        <h4 class="card-title mb-0">Daily Financial Sheet</h4>
                    </div>
                    <div class="card-body" style="margin-top: 0;">
                        @include('alerts')

                        <h5 class="mb-1">Open Sheets</h5>
                        <p class="text-muted">Days with pending payments or expenses awaiting approval.</p>
                        <div class="table-responsive mb-2">
                            <table class="table table-bordered table-striped">
                                <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($openDates as $openDate)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($openDate)->format('d M Y') }}</td>
                                        <td>
                                            <a href="{{ route('daily-financial-sheet.show', $openDate) }}" class="btn btn-sm btn-primary">
                                                Review Sheet
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center text-muted">No open sheets. All entries are approved.</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>

                        <h5 class="mb-1 mt-2">Approved History</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Cash In</th>
                                    <th>Cash Out</th>
                                    <th>Approved By</th>
                                    <th>Approved At</th>
                                    <th>Notes</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($approvedSheets as $approvedSheet)
                                    <tr>
                                        <td>{{ $approvedSheet->sheet_date->format('d M Y') }}</td>
                                        <td>£{{ number_format((float) $approvedSheet->cash_in, 2) }}</td>
                                        <td>£{{ number_format((float) $approvedSheet->cash_out, 2) }}</td>
                                        <td>{{ $approvedSheet->approvedByUser?->name ?? '—' }}</td>
                                        <td>{{ optional($approvedSheet->approved_at)->format('d M Y H:i') }}</td>
                                        <td>{{ $approvedSheet->approval_notes ?: '—' }}</td>
                                        <td>
                                            <a href="{{ route('daily-financial-sheet.show', $approvedSheet->sheet_date->toDateString()) }}" class="btn btn-sm btn-outline-info">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No approved sheets yet.</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{ $approvedSheets->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('css')
    <style>
        .navbar-floating .header-navbar-shadow {
            height: 90px !important;
        }
    </style>
@endsection
