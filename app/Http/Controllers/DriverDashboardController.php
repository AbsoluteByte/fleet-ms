<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Agreement;
use App\Models\AgreementCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DriverDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:driver']);
    }

    public function index()
    {
        $driver = $this->getAuthenticatedDriver();

        if (!$driver) {
            return redirect()->route('login')
                ->with('error', 'Driver profile not found. Please contact support.');
        }

        // Get driver's statistics
        $activeAgreements = $driver->activeAgreements()->count();
        $totalAgreements = $driver->agreements()->count();
        $pendingPayments = $driver->pendingPayments()->count();
        $overduePayments = $driver->overduePayments()->count();

        // Get recent agreements
        $recentAgreements = $driver->agreements()
            ->with(['car', 'company', 'status'])
            ->latest()
            ->take(5)
            ->get();

        // Get upcoming payments
        $upcomingPayments = $driver->collections()
            ->where('payment_status', 'pending')
            ->whereBetween('due_date', [now(), now()->addDays(30)])
            ->with(['agreement.car'])
            ->orderBy('due_date')
            ->take(5)
            ->get();

        // Get overdue payments
        $overduePayments = $driver->collections()
            ->where('payment_status', 'overdue')
            ->with(['agreement.car'])
            ->orderBy('due_date')
            ->get();


        return view('driver.dashboard', compact(
            'driver', 'activeAgreements', 'totalAgreements',
            'pendingPayments', 'overduePayments', 'recentAgreements',
            'upcomingPayments', 'overduePayments'
        ));
    }

    public function agreements()
    {
        $driver = $this->getAuthenticatedDriver();

        $agreements = $driver->agreements()
            ->with(['car', 'company', 'status', 'collections'])
            ->latest()
            ->paginate(10);

        return view('driver.agreements', compact('driver', 'agreements'));
    }

    public function showAgreement(Agreement $agreement)
    {
        $driver = $this->getAuthenticatedDriver();

        // Check if this agreement belongs to the authenticated driver
        if ($agreement->driver_id !== $driver->id) {
            abort(403, 'Unauthorized access to this agreement.');
        }

        $agreement->load(['car', 'company', 'status', 'collections' => function($query) {
            $query->orderBy('due_date');
        }]);

        return view('driver.agreement-details', compact('driver', 'agreement'));
    }

    public function payments()
    {
        $driver = $this->getAuthenticatedDriver();

        $payments = $driver->collections()
            ->with(['agreement.car'])
            ->orderBy('due_date', 'desc')
            ->paginate(15);

        return view('driver.payments', compact('driver', 'payments'));
    }

    public function profile()
    {
        $driver = $this->getAuthenticatedDriver();
        return view('driver.profile', compact('driver'));
    }

    public function updateProfile(Request $request)
    {
        $driver = $this->getAuthenticatedDriver();

        $validated = $request->validate([
            'phone_number' => 'required|string|max:20',
            'address1' => 'required|string|max:255',
            'address2' => 'nullable|string|max:255',
            'post_code' => 'required|string|max:20',
            'town' => 'required|string|max:100',
            'county' => 'required|string|max:100',
            'next_of_kin' => 'required|string|max:255',
            'next_of_kin_phone' => 'required|string|max:20',
        ]);

        $driver->update($validated);

        return redirect()->back()
            ->with('success', 'Profile updated successfully.');
    }

    private function getAuthenticatedDriver()
    {
        return Driver::where('user_id', Auth::id())->first();
    }
}
