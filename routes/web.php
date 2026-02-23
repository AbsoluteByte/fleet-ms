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
    Route::resource('cars', App\Http\Controllers\Backend\CarController::class);
    Route::resource('drivers', App\Http\Controllers\Backend\DriverController::class);

    Route::post('drivers/{driver}/invite', [App\Http\Controllers\Backend\DriverController::class, 'invite'])->name('drivers.invite');
    Route::post('drivers/{driver}/resend-invitation', [App\Http\Controllers\Backend\DriverController::class, 'resendInvitation'])->name('drivers.resend-invitation');

    Route::resource('agreements', App\Http\Controllers\Backend\AgreementController::class);
    Route::get('agreements/{agreement}/pdf', [App\Http\Controllers\Backend\AgreementController::class, 'generatePDF'])->name('agreements.pdf');

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
    Route::resource('payments', App\Http\Controllers\Backend\PaymentController::class);
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

    // ✅ Notifications index page
    Route::get('notifications', [App\Http\Controllers\Backend\DashboardController::class, 'notificationsIndex'])
        ->name('notifications.index');

    Route::get('payments/notifications', [App\Http\Controllers\Backend\DashboardController::class, 'paymentsIndex'])
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
