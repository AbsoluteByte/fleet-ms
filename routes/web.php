<?php

use App\Http\Controllers\Frontend\AgreementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();
Auth::routes(['verify' => true]);
Route::get('logout', [App\Http\Controllers\Auth\LoginController::class, 'logout']);
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::get('driver/invitation/{token}', [\App\Http\Controllers\HomeController::class, 'showAcceptForm'])->name('driver.accept-invitation');
Route::post('driver/invitation/{token}', [\App\Http\Controllers\HomeController::class, 'acceptInvitation']);

Route::get('/agreement/create', [AgreementController::class, 'create'])->name('frontend.agreements.create');
Route::post('/agreement/store', [AgreementController::class, 'store'])->name('frontend.agreements.store');
Route::get('/agreement/success', [AgreementController::class, 'success'])->name('frontend.agreements.success');

Route::get('/sign/{token}', [App\Http\Controllers\SigningController::class, 'show'])->name('sign.show');
Route::post('/sign/{token}', [App\Http\Controllers\SigningController::class, 'submit'])->name('sign.submit');
Route::get('/sign/{token}/success', [App\Http\Controllers\SigningController::class, 'success'])->name('sign.success');
Route::get('/sign/{token}/preview', [App\Http\Controllers\SigningController::class, 'preview'])->name('sign.preview');
// Webhook Route (Outside auth middleware)
Route::post('stripe/webhook', [App\Http\Controllers\StripeWebhookController::class, 'handleWebhook'])
    ->name('stripe.webhook');

Route::post('hellosign/webhook', [App\Http\Controllers\Backend\AgreementController::class, 'helloSignWebhook'])
    ->name('hellosign.webhook');

Route::prefix('admin')->middleware('auth')->group(function () {
    // Dashboard
    Route::get('/', [App\Http\Controllers\Backend\DashboardController::class, 'index']);
    Route::get('dashboard', [App\Http\Controllers\Backend\DashboardController::class, 'index'])->name('dashboard');
    Route::get('file-manager', [App\Http\Controllers\Backend\DashboardController::class, 'fileManager'])->name('file-manager');

    // Profile
    Route::get('/profile', [App\Http\Controllers\Backend\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [App\Http\Controllers\Backend\ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [App\Http\Controllers\Backend\ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('profile', [App\Http\Controllers\Backend\ProfileController::class, 'index'])->name('profile');
    Route::put('update-profile', [App\Http\Controllers\Backend\ProfileController::class, 'update'])->name('update-profile');
    Route::put('change-password', [App\Http\Controllers\Backend\ProfileController::class, 'change_password'])->name('change-password');

    Route::resource('customers', App\Http\Controllers\Backend\CustomerController::class);
    Route::post('customers/{id}/suspend', [App\Http\Controllers\Backend\CustomerController::class, 'suspend'])->name('customers.suspend');
    Route::post('customers/{id}/activate', [App\Http\Controllers\Backend\CustomerController::class, 'activate'])->name('customers.activate');

    Route::resource('roles', App\Http\Controllers\Backend\RoleController::class);

    Route::resource('permissions', App\Http\Controllers\Backend\PermissionController::class);

    // Subscription Management
    Route::prefix('subscription')->name('subscription.')->group(function () {
        Route::get('/', [App\Http\Controllers\Backend\SubscriptionController::class, 'index'])->name('index');
        Route::get('/packages', [App\Http\Controllers\Backend\SubscriptionController::class, 'packages'])->name('packages');
        Route::post('/subscribe/{package}', [App\Http\Controllers\Backend\SubscriptionController::class, 'subscribe'])->name('subscribe');
        Route::post('/cancel', [App\Http\Controllers\Backend\SubscriptionController::class, 'cancel'])->name('cancel');
        Route::post('/resume', [App\Http\Controllers\Backend\SubscriptionController::class, 'resume'])->name('resume');
        Route::get('/invoices', [App\Http\Controllers\Backend\SubscriptionController::class, 'invoices'])->name('invoices');
        Route::get('/invoices/{invoice}', [App\Http\Controllers\Backend\SubscriptionController::class, 'viewInvoice'])->name('invoices.view'); // ✅ Add this
        Route::get('/payment-methods', [App\Http\Controllers\Backend\SubscriptionController::class, 'paymentMethods'])->name('payment-methods');
        Route::post('/payment-methods/add', [App\Http\Controllers\Backend\SubscriptionController::class, 'addPaymentMethod'])->name('payment-methods.add');
        Route::delete('/payment-methods/{paymentMethod}', [App\Http\Controllers\Backend\SubscriptionController::class, 'removePaymentMethod'])->name('payment-methods.remove');
    });

    // Packages Routes
    Route::resource('packages', App\Http\Controllers\Backend\PackageController::class);
    Route::post('packages/{package}/toggle-status', [App\Http\Controllers\Backend\PackageController::class, 'toggleStatus'])->name('packages.toggle-status');

    // Main Features
    Route::resource('companies', App\Http\Controllers\Backend\CompanyController::class);
    Route::get('car-services', [App\Http\Controllers\Backend\CarServicePageController::class, 'index'])->name('car-services.index');
    Route::post('car-services', [App\Http\Controllers\Backend\CarServicePageController::class, 'store'])->name('car-services.store');
    Route::delete('car-services/{carService}/document', [App\Http\Controllers\Backend\CarServicePageController::class, 'destroyDocument'])->name('car-services.document.destroy');

    Route::get('phvl', [App\Http\Controllers\Backend\PhvlController::class, 'index'])->name('phvl.index');
    Route::get('phvl/data', [App\Http\Controllers\Backend\PhvlController::class, 'data'])->name('phvl.data');
    Route::patch('phvl/progress/{car}', [App\Http\Controllers\Backend\PhvlController::class, 'updateProgress'])->name('phvl.update-progress');
    Route::post('phvl/{car}/complete-pass', [App\Http\Controllers\Backend\PhvlController::class, 'completePass'])->name('phvl.complete-pass');
    Route::post('phvl/{car}/add-mot', [App\Http\Controllers\Backend\PhvlController::class, 'addMot'])->name('phvl.add-mot');
    Route::get('phvl/archive', [App\Http\Controllers\Backend\PhvlArchiveController::class, 'index'])->name('phvl.archive');
    Route::get('phvl/archive/data', [App\Http\Controllers\Backend\PhvlArchiveController::class, 'data'])->name('phvl.archive.data');
    Route::get('phvl/archive/{archive}/timeline', [App\Http\Controllers\Backend\PhvlArchiveController::class, 'timeline'])->name('phvl.archive.timeline');

    Route::get('reports', [App\Http\Controllers\Backend\ReportController::class, 'index'])->name('reports.index');

    Route::get('cars/reports/status/{status}', [App\Http\Controllers\Backend\CarController::class, 'statusReport'])->name('cars.reports.status');
    Route::get('cars/reports/available-by-phv', [App\Http\Controllers\Backend\CarController::class, 'availableByPhv'])->name('cars.reports.available-by-phv');
    Route::get('cars/reports/awaiting-phv', [App\Http\Controllers\Backend\CarController::class, 'awaitingPhv'])->name('cars.reports.awaiting-phv');
    Route::get('cars/{car}/view/v5/{v5_index?}', [App\Http\Controllers\Backend\CarController::class, 'viewV5'])->name('cars.view.v5')->whereNumber('v5_index');
    Route::get('cars/{car}/download/v5/{v5_index?}', [App\Http\Controllers\Backend\CarController::class, 'downloadV5'])->name('cars.download.v5')->whereNumber('v5_index');
    Route::get('cars/{car}/mots/{car_mot}/download', [App\Http\Controllers\Backend\CarController::class, 'downloadMot'])->name('cars.mots.download');
    Route::get('cars/{car}/phvs/{car_phv}/download', [App\Http\Controllers\Backend\CarController::class, 'downloadPhv'])->name('cars.phvs.download');
    Route::get('cars/{car}/notifications', [App\Http\Controllers\Backend\DashboardController::class, 'getCarNotifications'])->name('cars.notifications');
    Route::post('cars/{car}/apply-sorn', [App\Http\Controllers\Backend\CarController::class, 'applySorn'])->name('cars.apply-sorn');
    Route::post('cars/{car}/end-sorn', [App\Http\Controllers\Backend\CarController::class, 'endSorn'])->name('cars.end-sorn');
    Route::delete('cars/{car}/v5-document/{v5_index?}', [App\Http\Controllers\Backend\CarController::class, 'destroyV5Document'])->name('cars.v5-document.destroy')->whereNumber('v5_index');
    Route::delete('cars/{car}/mots/{car_mot}/document', [App\Http\Controllers\Backend\CarController::class, 'destroyMotDocument'])->name('cars.mots.document.destroy');
    Route::delete('cars/{car}/phvs/{car_phv}/document', [App\Http\Controllers\Backend\CarController::class, 'destroyPhvDocument'])->name('cars.phvs.document.destroy');
    Route::delete('cars/{car}/insurance-document', [App\Http\Controllers\Backend\CarController::class, 'destroyInsuranceDocument'])->name('cars.insurance-document.destroy');
    Route::delete('cars/{car}/sorn-document', [App\Http\Controllers\Backend\CarController::class, 'destroySornDocument'])->name('cars.sorn-document.destroy');
    Route::delete('cars/{car}/mots/{car_mot}', [App\Http\Controllers\Backend\CarController::class, 'destroyMot'])->name('cars.mots.destroy');
    Route::delete('cars/{car}/road-taxes/{car_road_tax}', [App\Http\Controllers\Backend\CarController::class, 'destroyRoadTax'])->name('cars.road-taxes.destroy');
    Route::delete('cars/{car}/phvs/{car_phv}', [App\Http\Controllers\Backend\CarController::class, 'destroyPhv'])->name('cars.phvs.destroy');
    Route::resource('cars', App\Http\Controllers\Backend\CarController::class);
    Route::get('car-status', [App\Http\Controllers\Backend\CarStatusController::class, 'create'])->name('car-status.create');
    Route::post('car-status', [App\Http\Controllers\Backend\CarStatusController::class, 'store'])->name('car-status.store');
    Route::put('car-status/{car}/current', [App\Http\Controllers\Backend\CarStatusController::class, 'updateCurrent'])->name('car-status.current.update');

    Route::get('car-insurance-import', [App\Http\Controllers\Backend\CarInsuranceImportController::class, 'index'])->name('car-insurance-import.index');
    Route::post('car-insurance-import', [App\Http\Controllers\Backend\CarInsuranceImportController::class, 'store'])->name('car-insurance-import.store');
    Route::get('car-insurance-import/report', [App\Http\Controllers\Backend\CarInsuranceImportController::class, 'report'])->name('car-insurance-import.report');

    Route::resource('reservations', App\Http\Controllers\Backend\ReservationController::class)->except(['show']);
    Route::resource('vehicle-swaps', App\Http\Controllers\Backend\VehicleSwapController::class)->except(['show']);
    Route::delete('drivers/{driver}/documents/{document}', [App\Http\Controllers\Backend\DriverController::class, 'destroyDocument'])->name('drivers.documents.destroy');
    Route::resource('drivers', App\Http\Controllers\Backend\DriverController::class);

    Route::post('drivers/{driver}/invite', [App\Http\Controllers\Backend\DriverController::class, 'invite'])->name('drivers.invite');
    Route::post('drivers/{driver}/resend-invitation', [App\Http\Controllers\Backend\DriverController::class, 'resendInvitation'])->name('drivers.resend-invitation');

    Route::resource('agreements', App\Http\Controllers\Backend\AgreementController::class);
    Route::get('agreements/{agreement}/pdf', [App\Http\Controllers\Backend\AgreementController::class, 'generatePDF'])->name('agreements.pdf');
    Route::get('agreements/{agreement}/pdf/preview', [App\Http\Controllers\Backend\AgreementController::class, 'previewPDF'])->name('agreements.pdf.preview');
    Route::get('agreements/{agreement}/permission-letter', [App\Http\Controllers\Backend\AgreementController::class, 'permissionLetterPDF'])->name('agreements.permission-letter');

    // Inside admin prefix group
    Route::post('agreements/{agreement}/send-esign', [App\Http\Controllers\Backend\AgreementController::class, 'sendForESignature'])
        ->name('agreements.send-esign');
    Route::get('agreements/{agreement}/esign-status', [App\Http\Controllers\Backend\AgreementController::class, 'checkESignStatus'])
        ->name('agreements.esign-status');
    Route::post('agreements/{agreement}/resend-esign', [App\Http\Controllers\Backend\AgreementController::class, 'resendESignature'])
        ->name('agreements.resend-esign');
    Route::get('agreements/{agreement}/view-signed', [App\Http\Controllers\Backend\AgreementController::class, 'viewSignedDocument'])
        ->name('agreements.view-signed');

    // Settings
    Route::get('payments/drivers/{driver}', [App\Http\Controllers\Backend\PaymentController::class, 'driver'])->name('payments.driver');
    Route::resource('payments', App\Http\Controllers\Backend\PaymentController::class);
    Route::resource('payment-settings', App\Http\Controllers\Backend\PaymentSettingController::class)
        ->parameters(['payment-settings' => 'paymentSetting']);
    Route::resource('users', App\Http\Controllers\Backend\UserController::class);
    Route::resource('statuses', App\Http\Controllers\Backend\StatusController::class);
    Route::resource('car-models', App\Http\Controllers\Backend\CarModelController::class);
    Route::resource('counsels', App\Http\Controllers\Backend\CounselController::class);
    Route::resource('insurance-providers', App\Http\Controllers\Backend\InsuranceProviderController::class);

    Route::get('settings', [App\Http\Controllers\Backend\SettingsController::class, 'index'])->name('settings.index');
    Route::get('settings/{setting}/edit', [App\Http\Controllers\Backend\SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('settings/{setting}', [App\Http\Controllers\Backend\SettingsController::class, 'update'])->name('settings.update');

    // Expenses
    Route::resource('claims', App\Http\Controllers\Backend\ClaimController::class);
    Route::resource('penalties', App\Http\Controllers\Backend\PenaltyController::class);
    Route::resource('expenses', App\Http\Controllers\Backend\ExpenseController::class);

    // Insurance Policies
    Route::resource('insurance-policies', App\Http\Controllers\Backend\InsurancePolicyController::class);
    Route::get('insurance-policies-expiring', [App\Http\Controllers\Backend\InsurancePolicyController::class, 'expiring'])
        ->name('insurance-policies.expiring');

    // Enhanced agreement routes
    Route::post('agreements/{agreement}/collections/{collection}/pay', [App\Http\Controllers\Backend\AgreementController::class, 'payCollection'])
        ->name('agreements.collections.pay');

    Route::post('agreements/{agreement}/regenerate-collections', function (\App\Models\Agreement $agreement) {
        $agreement->generateCollections();

        return response()->json(['success' => true]);
    })->name('agreements.regenerate-collections');

    // Dashboard API routes

    // ✅ Fleet notifications API (for header bell)
    Route::get('dashboard/fleet-notifications', [App\Http\Controllers\Backend\DashboardController::class, 'getFleetNotifications'])
        ->name('dashboard.fleet-notifications');

    Route::get('alerts/damaged-active-insurance', [App\Http\Controllers\Backend\DamagedCarInsuranceAlertController::class, 'index'])
        ->name('alerts.damaged-active-insurance');
    Route::post('alerts/damaged-active-insurance/dismiss', [App\Http\Controllers\Backend\DamagedCarInsuranceAlertController::class, 'dismiss'])
        ->name('alerts.damaged-active-insurance.dismiss');

    // ✅ Notifications index page
    Route::get('notifications', [App\Http\Controllers\Backend\DashboardController::class, 'notificationsIndex'])
        ->name('notifications.index');

    Route::get('payments-notifications', [App\Http\Controllers\Backend\DashboardController::class, 'paymentsIndex'])
        ->name('payments.notifications');

    // Collection payment routes
    Route::post('collections/{collection}/pay', [App\Http\Controllers\Backend\AgreementController::class, 'payCollection'])
        ->name('collections.pay');

});

Route::middleware(['auth', 'role:driver'])->prefix('driver')->name('driver.')->group(function () {
    Route::get('dashboard', [App\Http\Controllers\DriverDashboardController::class, 'index'])->name('dashboard');
    Route::get('agreements', [App\Http\Controllers\DriverDashboardController::class, 'agreements'])->name('agreements');
    Route::get('agreements/{agreement}', [App\Http\Controllers\DriverDashboardController::class, 'showAgreement'])->name('agreements.show');
    Route::get('payments', [App\Http\Controllers\DriverDashboardController::class, 'payments'])->name('payments');
    Route::get('profile', [App\Http\Controllers\DriverDashboardController::class, 'profile'])->name('profile');
    Route::post('profile', [App\Http\Controllers\DriverDashboardController::class, 'updateProfile'])->name('profile.update');
});
