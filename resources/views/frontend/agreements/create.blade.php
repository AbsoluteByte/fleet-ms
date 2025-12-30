{{-- resources/views/frontend/agreements/create.blade.php --}}
@extends('layouts.frontend')

@section('content')
    <div class="agreement-wizard-container">
        <div class="container py-5">
            <!-- Progress Header -->
            <div class="wizard-progress-wrapper mb-5">
                <div class="wizard-progress">
                    <div class="progress-step active" data-step="1">
                        <div class="step-circle">
                            <i class="fas fa-user"></i>
                        </div>
                        <span class="step-label">Driver</span>
                    </div>
                    <div class="progress-line"></div>
                    <div class="progress-step" data-step="2">
                        <div class="step-circle">
                            <i class="fas fa-car"></i>
                        </div>
                        <span class="step-label">Vehicle</span>
                    </div>
                    <div class="progress-line"></div>
                    <div class="progress-step" data-step="3">
                        <div class="step-circle">
                            <i class="fas fa-file-contract"></i>
                        </div>
                        <span class="step-label">Agreement</span>
                    </div>
                    <div class="progress-line"></div>
                    <div class="progress-step" data-step="4">
                        <div class="step-circle">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <span class="step-label">Insurance</span>
                    </div>
                    <div class="progress-line"></div>
                    <div class="progress-step" data-step="5">
                        <div class="step-circle">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <span class="step-label">Payment</span>
                    </div>
                    <div class="progress-line"></div>
                    <div class="progress-step" data-step="6">
                        <div class="step-circle">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <span class="step-label">Review</span>
                    </div>
                </div>
            </div>

            <form id="agreementForm" method="POST" action="{{ route('frontend.agreements.store') }}" enctype="multipart/form-data">
                @csrf

                <!-- Step 1: Driver Selection/Creation -->
                <div class="wizard-step" id="step-1">
                    <div class="step-card">
                        <div class="step-header">
                            <h2><i class="fas fa-user me-2"></i> Driver Information</h2>
                            <p class="text-muted">Select an existing driver or add a new one</p>
                        </div>
                        <div class="step-body">
                            <div class="driver-selection-section">
                                <div class="form-group mb-4">
                                    <label class="form-label fw-bold">Choose Driver</label>
                                    <div class="driver-options">
                                        <div class="option-card" onclick="showExistingDrivers()">
                                            <i class="fas fa-users fa-3x text-primary mb-3"></i>
                                            <h5>Select Existing Driver</h5>
                                            <p class="text-muted">Choose from registered drivers</p>
                                        </div>
                                        <div class="option-card" onclick="showNewDriverForm()">
                                            <i class="fas fa-user-plus fa-3x text-success mb-3"></i>
                                            <h5>Add New Driver</h5>
                                            <p class="text-muted">Register a new driver</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Existing Drivers Dropdown -->
                                <div id="existing-drivers-section" style="display: none;">
                                    <div class="form-group">
                                        <label for="driver_id" class="form-label">Select Driver *</label>
                                        <select name="driver_id" id="driver_id" class="form-select form-select-lg">
                                            <option value="">Choose a driver...</option>
                                            @foreach($drivers as $driver)
                                                <option value="{{ $driver->id }}" data-details='@json($driver)'>
                                                    {{ $driver->full_name }} - {{ $driver->phone }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div id="driver-preview" class="mt-3" style="display: none;"></div>
                                    <button type="button" class="btn btn-link" onclick="resetDriverSelection()">
                                        <i class="fas fa-arrow-left"></i> Back to options
                                    </button>
                                </div>

                                <!-- New Driver Form -->
                                <div id="new-driver-form" style="display: none;">
                                    <input type="hidden" name="create_new_driver" id="create_new_driver" value="0">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">First Name *</label>
                                            <input type="text" name="driver_first_name" class="form-control" placeholder="Enter first name">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Last Name *</label>
                                            <input type="text" name="driver_last_name" class="form-control" placeholder="Enter last name">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email *</label>
                                            <input type="email" name="driver_email" class="form-control" placeholder="driver@example.com">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Phone *</label>
                                            <input type="tel" name="driver_phone" class="form-control" placeholder="+44 7xxx xxx xxx">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">License Number *</label>
                                            <input type="text" name="driver_license_number" class="form-control" placeholder="Enter license number">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Date of Birth *</label>
                                            <input type="date" name="driver_dob" class="form-control">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Address</label>
                                            <textarea name="driver_address" class="form-control" rows="2" placeholder="Enter full address"></textarea>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-link mt-3" onclick="resetDriverSelection()">
                                        <i class="fas fa-arrow-left"></i> Back to options
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Vehicle Selection/Creation -->
                <div class="wizard-step" id="step-2" style="display: none;">
                    <div class="step-card">
                        <div class="step-header">
                            <h2><i class="fas fa-car me-2"></i> Vehicle Selection</h2>
                            <p class="text-muted">Select an existing vehicle or add a new one</p>
                        </div>
                        <div class="step-body">
                            <div class="vehicle-selection-section">
                                <div class="form-group mb-4">
                                    <label class="form-label fw-bold">Choose Vehicle</label>
                                    <div class="driver-options">
                                        <div class="option-card" onclick="showExistingVehicles()">
                                            <i class="fas fa-car fa-3x text-primary mb-3"></i>
                                            <h5>Select Existing Vehicle</h5>
                                            <p class="text-muted">Choose from available cars</p>
                                        </div>
                                        <div class="option-card" onclick="showNewVehicleForm()">
                                            <i class="fas fa-plus-circle fa-3x text-success mb-3"></i>
                                            <h5>Add New Vehicle</h5>
                                            <p class="text-muted">Register a new car</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Existing Vehicles Grid -->
                                <div id="existing-vehicles-section" style="display: none;">
                                    <div class="row g-3">
                                        @foreach($cars as $car)
                                            <div class="col-md-4">
                                                <div class="vehicle-card" onclick="selectVehicle({{ $car->id }})">
                                                    <input type="radio" name="car_id" value="{{ $car->id }}" id="car_{{ $car->id }}" style="display: none;">
                                                    <div class="vehicle-card-inner">
                                                        <div class="vehicle-image">
                                                            <i class="fas fa-car fa-4x text-primary"></i>
                                                        </div>
                                                        <div class="vehicle-details">
                                                            <h5>{{ $car->carModel->name }}</h5>
                                                            <p class="mb-1"><strong>Registration:</strong> {{ $car->registration }}</p>
                                                            <p class="mb-1"><strong>Color:</strong> {{ $car->color }}</p>
                                                            <p class="mb-0"><strong>Year:</strong> {{ $car->manufacture_year }}</p>
                                                        </div>
                                                        <div class="vehicle-check">
                                                            <i class="fas fa-check-circle"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <button type="button" class="btn btn-link mt-3" onclick="resetVehicleSelection()">
                                        <i class="fas fa-arrow-left"></i> Back to options
                                    </button>
                                </div>

                                <!-- New Vehicle Form -->
                                <div id="new-vehicle-form" style="display: none;">
                                    <input type="hidden" name="create_new_car" id="create_new_car" value="0">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Registration Number *</label>
                                            <input type="text" name="car_registration" class="form-control" placeholder="AB12 CDE">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Car Model *</label>
                                            <input type="text" name="car_model_name" class="form-control" placeholder="e.g. Toyota Corolla">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Color *</label>
                                            <select name="car_color" class="form-select">
                                                <option value="">Select Color</option>
                                                <option value="Black">Black</option>
                                                <option value="White">White</option>
                                                <option value="Silver">Silver</option>
                                                <option value="Blue">Blue</option>
                                                <option value="Red">Red</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Manufacture Year *</label>
                                            <input type="number" name="car_manufacture_year" class="form-control" placeholder="2020" min="1900" max="{{ date('Y') }}">
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-link mt-3" onclick="resetVehicleSelection()">
                                        <i class="fas fa-arrow-left"></i> Back to options
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Agreement Details -->
                <div class="wizard-step" id="step-3" style="display: none;">
                    <div class="step-card">
                        <div class="step-header">
                            <h2><i class="fas fa-file-contract me-2"></i> Agreement Details</h2>
                            <p class="text-muted">Enter rental agreement information</p>
                        </div>
                        <div class="step-body">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label">Company *</label>
                                    <select name="company_id" id="company_id" class="form-select form-select-lg" required>
                                        <option value="">Select Company</option>
                                        @foreach($companies as $company)
                                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Status *</label>
                                    <select name="status_id" class="form-select form-select-lg" required>
                                        <option value="">Select Status</option>
                                        @foreach($statuses as $status)
                                            <option value="{{ $status->id }}">{{ $status->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Start Date *</label>
                                    <input type="date" name="start_date" class="form-control form-control-lg" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">End Date *</label>
                                    <input type="date" name="end_date" class="form-control form-control-lg" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Agreed Rent (£) *</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text">£</span>
                                        <input type="number" name="agreed_rent" class="form-control" step="0.01" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Deposit Amount (£) *</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text">£</span>
                                        <input type="number" name="deposit_amount" class="form-control" step="0.01" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Rent Interval *</label>
                                    <select name="rent_interval" class="form-select form-select-lg" required>
                                        <option value="">Select Interval</option>
                                        <option value="Weekly">Weekly</option>
                                        <option value="Monthly">Monthly</option>
                                        <option value="Quarterly">Quarterly</option>
                                        <option value="Yearly">Yearly</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Insurance Options -->
                <div class="wizard-step" id="step-4" style="display: none;">
                    <div class="step-card">
                        <div class="step-header">
                            <h2><i class="fas fa-shield-alt me-2"></i> Insurance Options</h2>
                            <p class="text-muted">Choose your insurance preference</p>
                        </div>
                        <div class="step-body">
                            <div class="insurance-options mb-4">
                                <label class="form-label fw-bold mb-3">Will you be using your own insurance? *</label>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="insurance-option-card" onclick="selectInsuranceOption('own')">
                                            <input type="radio" name="using_own_insurance" value="1" id="own_insurance" style="display: none;">
                                            <i class="fas fa-user-shield fa-3x mb-3 text-primary"></i>
                                            <h5>Use My Own Insurance</h5>
                                            <p class="text-muted">I have my own insurance policy</p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="insurance-option-card" onclick="selectInsuranceOption('provider')">
                                            <input type="radio" name="using_own_insurance" value="0" id="provider_insurance" style="display: none;">
                                            <i class="fas fa-building fa-3x mb-3 text-success"></i>
                                            <h5>Use Provider Insurance</h5>
                                            <p class="text-muted">Select from our insurance providers</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Provider Insurance Section -->
                            <div id="provider-insurance-details" style="display: none;">
                                <div class="form-group">
                                    <label class="form-label">Select Insurance Provider *</label>
                                    <select name="insurance_provider_id" id="insurance_provider_id" class="form-select form-select-lg">
                                        <option value="">Choose provider...</option>
                                        @foreach($insuranceProviders as $provider)
                                            <option value="{{ $provider->id }}" data-company-id="{{ $provider->company_id }}">
                                                {{ $provider->provider_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Own Insurance Section -->
                            <div id="own-insurance-details" style="display: none;">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Provider Name *</label>
                                        <input type="text" name="own_insurance_provider_name" class="form-control" placeholder="Insurance company name">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Policy Number *</label>
                                        <input type="text" name="own_insurance_policy_number" class="form-control" placeholder="Policy number">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Start Date *</label>
                                        <input type="date" name="own_insurance_start_date" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">End Date *</label>
                                        <input type="date" name="own_insurance_end_date" class="form-control">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Insurance Type *</label>
                                        <select name="own_insurance_type" class="form-select">
                                            <option value="">Select Type</option>
                                            <option value="Comprehensive">Comprehensive</option>
                                            <option value="Third Party">Third Party</option>
                                            <option value="Third Party Fire & Theft">Third Party Fire & Theft</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Upload Insurance Document</label>
                                        <input type="file" name="own_insurance_proof_document" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                        <small class="text-muted">PDF, JPG, PNG (Max: 2MB)</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 5: Payment Schedule -->
                <div class="wizard-step" id="step-5" style="display: none;">
                    <div class="step-card">
                        <div class="step-header">
                            <h2><i class="fas fa-calendar-check me-2"></i> Payment Schedule</h2>
                            <p class="text-muted">Configure payment collection schedule</p>
                        </div>
                        <div class="step-body">
                            <div class="row g-4">
                                <div class="col-12">
                                    <label class="form-label fw-bold">Collection Type *</label>
                                    <select name="collection_type" id="collection_type" class="form-select form-select-lg" required>
                                        <option value="">Select Collection Type</option>
                                        <option value="weekly">Weekly (Every 7 days)</option>
                                        <option value="monthly">Monthly</option>
                                        <option value="static">One-time Payment</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="auto_schedule_collections" id="auto_schedule_collections" value="1" checked>
                                        <label class="form-check-label" for="auto_schedule_collections">
                                            <strong>Auto Schedule Collections</strong>
                                            <small class="d-block text-muted">Automatically create payment schedules based on collection type</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 6: Review & Submit -->
                <div class="wizard-step" id="step-6" style="display: none;">
                    <div class="step-card">
                        <div class="step-header">
                            <h2><i class="fas fa-check-circle me-2"></i> Review Your Agreement</h2>
                            <p class="text-muted">Please review all details before submitting</p>
                        </div>
                        <div class="step-body">
                            <div id="review-summary"></div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="wizard-navigation">
                    <button type="button" class="btn btn-outline-secondary btn-lg" id="prevBtn" onclick="changeStep(-1)" style="display: none;">
                        <i class="fas fa-arrow-left me-2"></i> Previous
                    </button>
                    <button type="button" class="btn btn-primary btn-lg" id="nextBtn" onclick="changeStep(1)">
                        Next <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                    <button type="submit" class="btn btn-success btn-lg" id="submitBtn" style="display: none;">
                        <i class="fas fa-check-circle me-2"></i> Submit Agreement
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('css')
        <style>
            :root {
                --primary-color: #4f46e5;
                --success-color: #10b981;
                --danger-color: #ef4444;
                --gray-100: #f3f4f6;
                --gray-200: #e5e7eb;
                --gray-300: #d1d5db;
                --gray-500: #6b7280;
                --gray-900: #111827;
            }

            .agreement-wizard-container {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                padding: 2rem 0;
            }

            .wizard-progress-wrapper {
                background: white;
                border-radius: 20px;
                padding: 2rem;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            }

            .wizard-progress {
                display: flex;
                align-items: center;
                justify-content: space-between;
                position: relative;
            }

            .progress-step {
                display: flex;
                flex-direction: column;
                align-items: center;
                position: relative;
                z-index: 2;
            }

            .step-circle {
                width: 60px;
                height: 60px;
                border-radius: 50%;
                background: var(--gray-200);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.5rem;
                color: var(--gray-500);
                transition: all 0.3s ease;
                border: 3px solid white;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            }

            .progress-step.active .step-circle {
                background: var(--primary-color);
                color: white;
                transform: scale(1.1);
            }

            .progress-step.completed .step-circle {
                background: var(--success-color);
                color: white;
            }

            .step-label {
                margin-top: 0.5rem;
                font-size: 0.875rem;
                font-weight: 600;
                color: var(--gray-500);
            }

            .progress-step.active .step-label {
                color: var(--primary-color);
            }

            .progress-line {
                flex: 1;
                height: 3px;
                background: var(--gray-200);
                position: relative;
                top: -15px;
            }

            .step-card {
                background: white;
                border-radius: 20px;
                padding: 2.5rem;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
                animation: slideIn 0.5s ease;
            }

            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .step-header h2 {
                font-size: 1.75rem;
                font-weight: 700;
                color: var(--gray-900);
                margin-bottom: 0.5rem;
            }

            .step-header p {
                font-size: 1rem;
                margin-bottom: 2rem;
            }

            .option-card {
                border: 2px solid var(--gray-200);
                border-radius: 15px;
                padding: 2rem;
                text-align: center;
                cursor: pointer;
                transition: all 0.3s ease;
                background: white;
            }

            .option-card:hover {
                border-color: var(--primary-color);
                transform: translateY(-5px);
                box-shadow: 0 10px 25px rgba(79, 70, 229, 0.2);
            }

            .driver-options {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 1.5rem;
            }

            .vehicle-card {
                border: 2px solid var(--gray-200);
                border-radius: 15px;
                cursor: pointer;
                transition: all 0.3s ease;
                background: white;
                position: relative;
                overflow: hidden;
            }

            .vehicle-card:hover {
                border-color: var(--primary-color);
                box-shadow: 0 5px 15px rgba(79, 70, 229, 0.2);
            }

            .vehicle-card.selected {
                border-color: var(--primary-color);
                background: #f0f9ff;
            }

            .vehicle-card-inner {
                padding: 1.5rem;
            }

            .vehicle-image {
                text-align: center;
                margin-bottom: 1rem;
            }

            .vehicle-check {
                position: absolute;
                top: 10px;
                right: 10px;
                font-size: 1.5rem;
                color: var(--success-color);
                display: none;
            }

            .vehicle-card.selected .vehicle-check {
                display: block;
            }

            .insurance-option-card {
                border: 2px solid var(--gray-200);
                border-radius: 15px;
                padding: 2rem;
                text-align: center;
                cursor: pointer;
                transition: all 0.3s ease;
                height: 100%;
                background: white;
            }

            .insurance-option-card:hover {
                border-color: var(--primary-color);
                transform: translateY(-5px);
                box-shadow: 0 10px 25px rgba(79, 70, 229, 0.2);
            }

            .insurance-option-card.selected {
                border-color: var(--primary-color);
                background: #f0f9ff;
            }

            .wizard-navigation {
                display: flex;
                justify-content: space-between;
                margin-top: 2rem;
                gap: 1rem;
            }

            .btn-lg {
                padding: 0.75rem 2rem;
                font-size: 1.1rem;
                border-radius: 10px;
                font-weight: 600;
            }

            .form-control-lg, .form-select-lg {
                padding: 0.75rem 1rem;
                font-size: 1.05rem;
                border-radius: 10px;
            }

            @media (max-width: 768px) {
                .wizard-progress {
                    overflow-x: auto;
                }

                .step-circle {
                    width: 50px;
                    height: 50px;
                    font-size: 1.2rem;
                }

                .step-label {
                    font-size: 0.75rem;
                }
            }
        </style>
    @endpush

    @push('js')
        <script>
            let currentStep = 1;
            const totalSteps = 6;

            function changeStep(direction) {
                if (direction === 1 && !validateStep(currentStep)) {
                    return;
                }

                // Mark current step as completed
                if (direction === 1) {
                    document.querySelector(`[data-step="${currentStep}"]`).classList.add('completed');
                }

                // Hide current step
                document.getElementById(`step-${currentStep}`).style.display = 'none';

                // Update current step
                currentStep += direction;

                // Show new step
                document.getElementById(`step-${currentStep}`).style.display = 'block';

                // Update progress
                updateProgress();

                // Update buttons
                updateButtons();

                // Scroll to top
                window.scrollTo({ top: 0, behavior: 'smooth' });

                // If review step, populate summary
                if (currentStep === 6) {
                    populateReviewSummary();
                }
            }

            function updateProgress() {
                // Remove active class from all steps
                document.querySelectorAll('.progress-step').forEach(step => {
                    step.classList.remove('active');
                });

                // Add active class to current step
                document.querySelector(`[data-step="${currentStep}"]`).classList.add('active');
            }

            function updateButtons() {
                document.getElementById('prevBtn').style.display = currentStep === 1 ? 'none' : 'block';
                document.getElementById('nextBtn').style.display = currentStep === totalSteps ? 'none' : 'block';
                document.getElementById('submitBtn').style.display = currentStep === totalSteps ? 'block' : 'none';
            }

            function validateStep(step) {
                // Add validation logic for each step
                switch(step) {
                    case 1:
                        return validateDriverStep();
                    case 2:
                        return validateVehicleStep();
                    case 3:
                        return validateAgreementStep();
                    case 4:
                        return validateInsuranceStep();
                    case 5:
                        return true; // Payment step
                    default:
                        return true;
                }
            }

            function validateDriverStep() {
                const driverId = document.getElementById('driver_id').value;
                const createNew = document.getElementById('create_new_driver').value;

                if (!driverId && createNew === '0') {
                    alert('Please select a driver or create a new one');
                    return false;
                }
                return true;
            }

            function validateVehicleStep() {
                const carId = document.querySelector('input[name="car_id"]:checked');
                const createNew = document.getElementById('create_new_car').value;

                if (!carId && createNew === '0') {
                    alert('Please select a vehicle or create a new one');
                    return false;
                }
                return true;
            }

            function validateAgreementStep() {
                const required = ['company_id', 'start_date', 'end_date', 'agreed_rent', 'deposit_amount'];
                for (let field of required) {
                    if (!document.querySelector(`[name="${field}"]`).value) {
                        alert('Please fill all required fields');
                        return false;
                    }
                }
                return true;
            }

            function validateInsuranceStep() {
                const usingOwn = document.querySelector('input[name="using_own_insurance"]:checked');
                if (!usingOwn) {
                    alert('Please select an insurance option');
                    return false;
                }
                return true;
            }

            // Driver selection functions
            function showExistingDrivers() {
                document.getElementById('existing-drivers-section').style.display = 'block';
                document.getElementById('new-driver-form').style.display = 'none';
                document.querySelector('.driver-options').style.display = 'none';
            }

            function showNewDriverForm() {
                document.getElementById('new-driver-form').style.display = 'block';
                document.getElementById('existing-drivers-section').style.display = 'none';
                document.querySelector('.driver-options').style.display = 'none';
                document.getElementById('create_new_driver').value = '1';
            }

            function resetDriverSelection() {
                document.getElementById('existing-drivers-section').style.display = 'none';
                document.getElementById('new-driver-form').style.display = 'none';
                document.querySelector('.driver-options').style.display = 'grid';
                document.getElementById('create_new_driver').value = '0';
            }

            // Vehicle selection functions
            function showExistingVehicles() {
                document.getElementById('existing-vehicles-section').style.display = 'block';
                document.getElementById('new-vehicle-form').style.display = 'none';
                document.querySelector('.vehicle-selection-section .driver-options').style.display = 'none';
            }

            function showNewVehicleForm() {
                document.getElementById('new-vehicle-form').style.display = 'block';
                document.getElementById('existing-vehicles-section').style.display = 'none';
                document.querySelector('.vehicle-selection-section .driver-options').style.display = 'none';
                document.getElementById('create_new_car').value = '1';
            }

            function resetVehicleSelection() {
                document.getElementById('existing-vehicles-section').style.display = 'none';
                document.getElementById('new-vehicle-form').style.display = 'none';
                document.querySelector('.vehicle-selection-section .driver-options').style.display = 'grid';
                document.getElementById('create_new_car').value = '0';
            }

            function selectVehicle(carId) {
                // Remove selected class from all cards
                document.querySelectorAll('.vehicle-card').forEach(card => {
                    card.classList.remove('selected');
                });

                // Add selected class to clicked card
                event.currentTarget.classList.add('selected');

                // Check the radio button
                document.getElementById(`car_${carId}`).checked = true;
            }

            // Insurance selection
            function selectInsuranceOption(type) {
                document.querySelectorAll('.insurance-option-card').forEach(card => {
                    card.classList.remove('selected');
                });

                event.currentTarget.classList.add('selected');

                if (type === 'own') {
                    document.getElementById('own_insurance').checked = true;
                    document.getElementById('own-insurance-details').style.display = 'block';
                    document.getElementById('provider-insurance-details').style.display = 'none';
                } else {
                    document.getElementById('provider_insurance').checked = true;
                    document.getElementById('provider-insurance-details').style.display = 'block';
                    document.getElementById('own-insurance-details').style.display = 'none';
                }
            }

            function populateReviewSummary() {
                // Build summary HTML
                let summary = '<div class="review-sections">';

                // Add all form data to summary
                summary += '</div>';

                document.getElementById('review-summary').innerHTML = summary;
            }

            // Initialize
            document.addEventListener('DOMContentLoaded', function() {
                updateProgress();
                updateButtons();
            });
        </script>
    @endpush
@endsection
