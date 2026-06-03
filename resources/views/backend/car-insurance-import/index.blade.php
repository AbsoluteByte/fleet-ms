@extends('layouts.admin', ['title' => 'Import insurance from PDFs'])
@section('content')
    <section>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Bulk import insurance certificates</h4>
                        <a href="{{ route('cars.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fa fa-arrow-left"></i> Back to cars
                        </a>
                    </div>
                    <hr>
                    <div class="card-body">
                        @include('alerts')
                        <p class="text-muted">
                            Upload PDF certificates (or a ZIP of PDFs). Each file is matched to a car by registration.
                            Successful imports create <strong>Active</strong> insurance with the PDF attached.
                            Cars with non-expired active insurance are skipped. Expired active policies are cancelled first, then replaced.
                        </p>
                        <p class="text-muted small mb-2">
                            Expected filename format:
                            <code>1_BH17RFX_TOYOTA PRIUS_27-05-2026to26-05-2027.pdf</code>
                        </p>
                        <form method="POST" action="{{ route('car-insurance-import.store') }}"
                              enctype="multipart/form-data" id="formInsuranceImport">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label for="insurance_provider_id">Insurance provider <span class="text-danger">*</span></label>
                                    <select name="insurance_provider_id" id="insurance_provider_id"
                                            class="form-control @error('insurance_provider_id') is-invalid @enderror" required>
                                        <option value="">— Select provider —</option>
                                        @foreach($insuranceProviders as $provider)
                                            <option value="{{ $provider->id }}"
                                                {{ (string) old('insurance_provider_id') === (string) $provider->id ? 'selected' : '' }}>
                                                {{ $provider->provider_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('insurance_provider_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="notify_before_expiry">Notify before expiry (days) <span class="text-danger">*</span></label>
                                    <input type="number" name="notify_before_expiry" id="notify_before_expiry"
                                           class="form-control @error('notify_before_expiry') is-invalid @enderror"
                                           min="1" max="365" required
                                           value="{{ old('notify_before_expiry', 30) }}">
                                    @error('notify_before_expiry')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="upload_zip">ZIP file (optional)</label>
                                    <input type="file" name="upload_zip" id="upload_zip"
                                           class="form-control @error('upload_zip') is-invalid @enderror"
                                           accept=".zip">
                                    @error('upload_zip')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">ZIP may contain multiple PDF certificates (max 50 MB).</small>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="upload_files">PDF files (optional)</label>
                                    <input type="file" name="upload_files[]" id="upload_files"
                                           class="form-control @error('upload_files') is-invalid @enderror @error('upload_files.*') is-invalid @enderror"
                                           accept=".pdf" multiple>
                                    @error('upload_files')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    @error('upload_files.*')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Select one or more PDFs (max 10 MB each). Folder upload is not supported — use ZIP for bulk.</small>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-upload"></i> Import insurance
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
