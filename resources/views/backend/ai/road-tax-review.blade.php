@extends('layouts.admin', ['title' => 'Review road tax slips'])

@section('content')
    <section>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                        <h4 class="card-title mb-0">Review extracted road tax data</h4>
                        <a href="{{ route('ai.index') }}#add-road-tax" class="btn btn-outline-secondary btn-sm">
                            <i class="fa fa-arrow-left"></i> Upload again
                        </a>
                    </div>
                    <hr>
                    <div class="card-body">
                        @include('alerts')

                        <p class="mb-2">
                            <strong>Analyzed at:</strong> {{ $review['analyzed_at'] ?? '—' }}
                            &nbsp;|&nbsp;
                            <strong>Slips:</strong> {{ count($review['rows'] ?? []) }}
                        </p>
                        <p class="text-muted small">
                            Rows highlighted in yellow need your attention. Uncheck any slip you do not want to save.
                            @if(!empty($review['account_warning']))
                                <strong>AI extraction failed for all slips — manual entry is still supported.</strong>
                            @endif
                        </p>

                        <form method="POST" action="{{ route('ai.road-tax.apply') }}" id="formRoadTaxApply">
                            @csrf
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead>
                                    <tr>
                                        <th style="width: 50px;">Include</th>
                                        <th>File</th>
                                        <th>Preview</th>
                                        <th>Registration</th>
                                        <th>Start date</th>
                                        <th>Term</th>
                                        <th>Amount (£)</th>
                                        <th>Status</th>
                                        <th>AI notes</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($review['rows'] as $index => $row)
                                        @php
                                            $needsReview = !empty($row['needs_review']) || ($row['extraction_status'] ?? '') !== 'ok';
                                            $previewName = basename($row['file_path'] ?? $row['filename']);
                                        @endphp
                                        <tr class="{{ $needsReview ? 'table-warning' : '' }}">
                                            <td class="text-center align-middle">
                                                <input type="hidden" name="rows[{{ $index }}][row_id]" value="{{ $row['row_id'] }}">
                                                <input type="hidden" name="rows[{{ $index }}][include]" value="0">
                                                <input type="checkbox" name="rows[{{ $index }}][include]" value="1"
                                                       class="row-include-checkbox"
                                                       {{ old("rows.{$index}.include", '1') == '1' ? 'checked' : '' }}>
                                            </td>
                                            <td class="align-middle text-nowrap">{{ $row['filename'] }}</td>
                                            <td class="align-middle">
                                                <a href="{{ document_view_url(route('ai.road-tax.preview', ['batchId' => $review['batch_id'], 'filename' => $previewName])) }}"
                                                   target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary document-view-link">
                                                    View
                                                </a>
                                            </td>
                                            <td class="align-middle">
                                                <input type="text" name="rows[{{ $index }}][registration]"
                                                       class="form-control form-control-sm {{ $needsReview && empty($row['registration']) ? 'border-danger' : '' }}"
                                                       value="{{ old("rows.{$index}.registration", $row['registration']) }}"
                                                       placeholder="e.g. KD17 UAP">
                                            </td>
                                            <td class="align-middle">
                                                <input type="date" name="rows[{{ $index }}][start_date]"
                                                       class="form-control form-control-sm {{ $needsReview && empty($row['start_date']) ? 'border-danger' : '' }}"
                                                       value="{{ old("rows.{$index}.start_date", $row['start_date']) }}">
                                            </td>
                                            <td class="align-middle">
                                                <select name="rows[{{ $index }}][term]"
                                                        class="form-control form-control-sm {{ $needsReview && empty($row['term']) ? 'border-danger' : '' }}">
                                                    <option value="">— Select —</option>
                                                    <option value="6 months" {{ old("rows.{$index}.term", $row['term']) === '6 months' ? 'selected' : '' }}>6 months</option>
                                                    <option value="12 months" {{ old("rows.{$index}.term", $row['term']) === '12 months' ? 'selected' : '' }}>12 months</option>
                                                </select>
                                            </td>
                                            <td class="align-middle">
                                                <input type="number" name="rows[{{ $index }}][amount]" step="0.01" min="0"
                                                       class="form-control form-control-sm {{ $needsReview && empty($row['amount']) ? 'border-danger' : '' }}"
                                                       value="{{ old("rows.{$index}.amount", $row['amount']) }}">
                                            </td>
                                            <td class="align-middle">
                                                @if(($row['extraction_status'] ?? '') !== 'ok')
                                                    <span class="badge badge-danger">Failed</span>
                                                    @if(!empty($row['message']))
                                                        <div class="small text-danger">{{ $row['message'] }}</div>
                                                    @endif
                                                @elseif($needsReview)
                                                    <span class="badge badge-warning">Needs review</span>
                                                @else
                                                    <span class="badge badge-success">Ready</span>
                                                @endif
                                            </td>
                                            <td class="align-middle small text-muted">
                                                {{ $row['notes'] ?? '—' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <button type="submit" class="btn btn-primary" id="btnConfirmRoadTax">
                                <i class="fa fa-check"></i> Confirm &amp; save road tax
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('js')
    <script>
        $(document).ready(function () {
            $('#formRoadTaxApply').on('submit', function (e) {
                var included = $('.row-include-checkbox:checked').length;
                if (included === 0) {
                    e.preventDefault();
                    alert('Please include at least one slip to save.');
                    return;
                }

                $('#btnConfirmRoadTax').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving…');
            });
        });
    </script>
@endsection
