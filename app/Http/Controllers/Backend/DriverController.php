<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Mail\DriverInvitationMail;
use App\Models\Driver;
use App\Services\DriverPersistenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class DriverController extends Controller
{
    protected $url = 'drivers.';
    protected $dir = 'backend.drivers.';
    protected $name = 'Drivers';

    public function __construct()
    {
        $this->middleware('role:admin|manager|user');
        view()->share('url', $this->url);
        view()->share('dir', $this->dir);
        view()->share('singular', Str::singular($this->name));
        view()->share('plural', Str::plural($this->name));
    }

    public function index()
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found! Please contact administrator.');
        }
        $drivers = Driver::where('tenant_id', $tenant->id)->get();
        return view($this->dir .'index', compact('drivers'));
    }

    public function create()
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }
        return view($this->dir.'create');
    }

    public function store(Request $request, DriverPersistenceService $driverPersistence)
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->back()
                ->with('error', 'No active company found!');
        }

        $driverPersistence->createFromRequest($request, $tenant);

        return redirect()->route($this->url.'index')
            ->with('success', 'Driver created successfully.');
    }

    public function show(Driver $driver)
    {
        $tenant = Auth::user()->currentTenant();

        // ✅ Check ownership
        if ($driver->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to this car');
        }
        return view($this->dir.'show', compact('driver'));
    }

    public function edit($id)
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }
        $model = Driver::where('tenant_id', $tenant->id)->findOrFail($id);
        return view($this->dir.'edit', compact('model'));
    }

    public function update(Request $request, Driver $driver, DriverPersistenceService $driverPersistence)
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->back()
                ->with('error', 'No active company found!');
        }

        $driverPersistence->updateFromRequest($driver, $request, $tenant);

        return redirect()->route($this->url.'index')
            ->with('success', 'Driver updated successfully.');
    }

    public function destroy(Driver $driver)
    {
        $tenant = Auth::user()->currentTenant();

        // ✅ Check ownership
        if ($driver->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access');
        }

        if ($driver) {
            $files = [
                $driver->driver_license_document,
                $driver->driver_phd_license_document,
                $driver->phd_card_document,
                $driver->dvla_license_summary,
                $driver->misc_document,
                $driver->proof_of_address_document,
            ];

            foreach ($files as $file) {
                if ($file) {
                    $path = public_path('uploads/driver_licenses/' . $file);
                    if (File::exists($path)) {
                        File::delete($path);
                    }
                }
            }

            $driver->delete();
        }

        return redirect()->route($this->url.'index')
            ->with('success', 'Driver deleted successfully.');
    }

    public function invite(Driver $driver)
    {
        if (!$driver->canBeInvited()) {
            return redirect()->back()
                ->with('error', 'Driver has already been invited or invitation is still pending.');
        }

        try {
            // Generate invitation token
            $token = $driver->generateInvitationToken();
            // Update invitation status
            $driver->update([
                'is_invited' => true,
                'invited_at' => now()
            ]);

            // Send invitation email
            Mail::to($driver->email)->send(new DriverInvitationMail($driver));

            return redirect()->back()
                ->with('success', 'Invitation sent successfully to ' . $driver->full_name);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to send invitation: ' . $e->getMessage());
        }
    }

    public function resendInvitation(Driver $driver)
    {
        if ($driver->hasAcceptedInvitation()) {
            return redirect()->back()
                ->with('error', 'Driver has already accepted the invitation.');
        }

        try {
            // Generate new token
            $token = $driver->generateInvitationToken();

            // Update invitation time
            $driver->update(['invited_at' => now()]);

            // Resend invitation email
            Mail::to($driver->email)->send(new DriverInvitationMail($driver));

            return redirect()->back()
                ->with('success', 'Invitation resent successfully to ' . $driver->full_name);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to resend invitation: ' . $e->getMessage());
        }
    }
}
