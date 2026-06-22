@extends('layouts.admin', ['title' => 'Your AI assistant'])

@section('content')
    <section id="ai-page">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Your AI assistant</h4>
                    </div>
                    <div class="card-content">
                        <div class="card-body card-dashboard">
                            @include('alerts')

                            <ul class="nav nav-pills mb-2" id="ai-tabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="ai-road-tax-tab" data-toggle="pill" href="#ai-road-tax-pane" role="tab" aria-controls="ai-road-tax-pane" aria-selected="true">
                                        Add Road Tax
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content" id="ai-tab-content">
                                <div class="tab-pane fade show active" id="ai-road-tax-pane" role="tabpanel" aria-labelledby="ai-road-tax-tab">
                                    <div class="border rounded p-2 p-md-3">
                                        <h5 class="mb-1">Add Road Tax from V11 slips</h5>
                                        <p class="text-muted">
                                            Upload photos or scans of UK DVLA <strong>Vehicle tax reminder (V11)</strong> slips.
                                            Google Gemini will read each slip and extract registration, start date, term, and amount paid.
                                            You can review and correct anything unclear before saving to the matching car.
                                        </p>
                                        <p class="text-muted small mb-3">
                                            Start date is calculated as the day after the printed tax expiry date.
                                            Supported formats: JPG, PNG, WEBP (or a ZIP of images).
                                        </p>

                                        <form method="POST" action="{{ route('ai.road-tax.analyze') }}"
                                              enctype="multipart/form-data" id="formRoadTaxAnalyze">
                                            @csrf
                                            <div class="row">
                                                <div class="col-md-6 form-group">
                                                    <label for="upload_zip">ZIP file (optional)</label>
                                                    <input type="file" name="upload_zip" id="upload_zip"
                                                           class="form-control @error('upload_zip') is-invalid @enderror"
                                                           accept=".zip">
                                                    @error('upload_zip')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                    <small class="text-muted">ZIP may contain multiple slip images (max 50 MB).</small>
                                                </div>
                                                <div class="col-md-6 form-group">
                                                    <label for="upload_files">Image files (optional)</label>
                                                    <input type="file" name="upload_files[]" id="upload_files"
                                                           class="form-control @error('upload_files') is-invalid @enderror @error('upload_files.*') is-invalid @enderror"
                                                           accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple>
                                                    @error('upload_files')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                    @error('upload_files.*')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                    <small class="text-muted">Select one or more images (max 10 MB each).</small>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-primary" id="btnAnalyzeRoadTax">
                                                <i class="fa fa-magic"></i> Analyze slips with AI
                                            </button>
                                        </form>
                                    </div>
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
    <script>
        $(document).ready(function () {
            if (window.location.hash === '#add-road-tax') {
                $('#ai-road-tax-tab').tab('show');
            }

            $('#formRoadTaxAnalyze').on('submit', function () {
                var $btn = $('#btnAnalyzeRoadTax');
                $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Analyzing…');
            });
        });
    </script>
@endsection
