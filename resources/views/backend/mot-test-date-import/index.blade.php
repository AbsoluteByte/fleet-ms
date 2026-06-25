@extends('layouts.admin', ['title' => 'Import MOT test dates'])
@section('content')
    <section>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Import MOT test dates</h4>
                        <a href="{{ route('cars.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fa fa-arrow-left"></i> Back to cars
                        </a>
                    </div>
                    <div class="card-body">
                        @include('alerts')
                        <p class="text-muted">
                            Upload a spreadsheet to update the <strong>test date</strong> on each car's latest MOT entry.
                            Cars are matched by registration.
                        </p>
                        <p class="text-muted small mb-2">
                            Expected columns:
                            <code>CAR REG</code>,
                            <code>MOT TEST DATE</code>,
                            <code>MOT EXPIRY DATE</code> (optional, for reference only).
                        </p>
                        <p class="text-muted small mb-2">
                            Supported date formats include <code>28.04.2025</code>, <code>28/04/2025</code>, and <code>2025-04-28</code>.
                        </p>
                        <form method="POST" action="{{ route('mot-test-date-import.store') }}"
                              enctype="multipart/form-data" id="formMotTestDateImport">
                            @csrf
                            <div class="row">
                                <div class="col-md-8 form-group">
                                    <label for="upload_file">Spreadsheet file <span class="text-danger">*</span></label>
                                    <input type="file" name="upload_file" id="upload_file"
                                           class="form-control @error('upload_file') is-invalid @enderror"
                                           accept=".csv,.xlsx" required>
                                    @error('upload_file')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Upload a CSV or XLSX file (max 10 MB).</small>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-upload"></i> Import MOT test dates
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
