<?php

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
    Route::get('customers/get/data', [App\Http\Controllers\Backend\CustomerController::class, 'data'])->name('customers.data');

    Route::resource('roles', App\Http\Controllers\Backend\RoleController::class);
    Route::get('roles/get/data', [App\Http\Controllers\Backend\RoleController::class, 'data'])->name('roles.data');

    Route::resource('permissions', App\Http\Controllers\Backend\PermissionController::class);
    Route::get('permissions/get/data', [App\Http\Controllers\Backend\PermissionController::class, 'data'])->name('permissions.data');


    // Main Features
    Route::resource('companies', App\Http\Controllers\Backend\CompanyController::class);
    Route::resource('cars', App\Http\Controllers\Backend\CarController::class);
    Route::resource('drivers', App\Http\Controllers\Backend\DriverController::class);

    Route::post('drivers/{driver}/invite', [App\Http\Controllers\Backend\DriverController::class, 'invite'])->name('drivers.invite');
    Route::post('drivers/{driver}/resend-invitation', [App\Http\Controllers\Backend\DriverController::class, 'resendInvitation'])->name('drivers.resend-invitation');

    Route::resource('agreements', App\Http\Controllers\Backend\AgreementController::class);
    Route::get('agreements/{agreement}/pdf', [App\Http\Controllers\Backend\AgreementController::class, 'generatePDF'])->name('agreements.pdf');
    // Settings
    Route::resource('payments', App\Http\Controllers\Backend\PaymentController::class);
    Route::resource('users', App\Http\Controllers\Backend\UserController::class);
    Route::resource('statuses', App\Http\Controllers\Backend\StatusController::class);
    Route::resource('car-models', App\Http\Controllers\Backend\CarModelController::class);
    Route::resource('counsels', App\Http\Controllers\Backend\CounselController::class);
    Route::resource('insurance-providers', App\Http\Controllers\Backend\InsuranceProviderController::class);

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

    Route::post('agreements/{agreement}/regenerate-collections', function(\App\Models\Agreement $agreement) {
        $agreement->generateCollections();
        return response()->json(['success' => true]);
    })->name('agreements.regenerate-collections');

    // Dashboard API routes
    Route::get('dashboard/notifications', [App\Http\Controllers\Backend\DashboardController::class, 'getPaymentNotifications'])
        ->name('dashboard.notifications');

    Route::get('dashboard/fleet-notifications', [App\Http\Controllers\Backend\DashboardController::class, 'getFleetNotifications'])
        ->name('dashboard.fleet-notifications');

    // Single unified route for notifications
    Route::get('dashboard/fleet-notifications', [App\Http\Controllers\Backend\DashboardController::class, 'getFleetNotifications'])
        ->name('dashboard.fleet-notifications');

// Notifications index page
    Route::get('notifications', [App\Http\Controllers\Backend\DashboardController::class, 'notificationsIndex'])
        ->name('notifications.index');

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
