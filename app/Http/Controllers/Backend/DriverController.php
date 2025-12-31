<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Mail\DriverInvitationMail;
use App\Models\Driver;
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
        $this->middleware('role:admin');
        view()->share('url', $this->url);
        view()->share('dir', $this->dir);
        view()->share('singular', Str::singular($this->name));
        view()->share('plural', Str::plural($this->name));
    }

    public function index()
    {
        $drivers = Driver::where('tenant_id', Auth::user()->tenant_id)->get();
        return view($this->dir .'index', compact('drivers'));
    }

    public function create()
    {
        return view($this->dir.'create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'dob' => 'required|date',
            'email' => 'required|email|unique:drivers',
            'phone_number' => 'required|string|max:20',
            'ni_number' => 'nullable|string|max:20',
            'address1' => 'required|string|max:255',
            'address2' => 'nullable|string|max:255',
            'post_code' => 'required|string|max:20',
            'town' => 'required|string|max:100',
            'county' => 'required|string|max:100',
            'country_id' => 'required|numeric|exists:countries,id',
            'driver_license_number' => 'required|string|unique:drivers',
            'driver_license_expiry_date' => 'required|date',
            'phd_license_number' => 'nullable|string',
            'phd_license_expiry_date' => 'nullable|date',
            'next_of_kin' => 'required|string|max:255',
            'next_of_kin_phone' => 'required|string|max:20',
            'driver_license_document' => 'nullable|file',
            'driver_phd_license_document' => 'nullable|file',
            'phd_card_document' => 'nullable|file',
            'dvla_license_summary' => 'nullable|file',
            'misc_document' => 'nullable|file',
            'proof_of_address_document' => 'nullable|file',
        ]);

        // Handle file uploads
        if ($request->hasFile('driver_license_document')) {
            $file = $request->file('driver_license_document');
            $mimeType = $file->getMimeType();
            if (str_starts_with($mimeType, 'image/')) {
                $dims = getimagesize($file);
                $width = $dims[0];
                $height = $dims[1];
                $name = time() . '-' . $width . '-' . $height . '.' . $file->extension();
            } else {
                $name = time() . '-' . uniqid() . '.' . $file->extension();
            }
            $path = public_path('uploads/driver_licenses/');
            $file = $request->file('driver_license_document');
            if ($file->move($path, $name)) {
                $validated['driver_license_document'] = $name;
            }
        }

        if ($request->hasFile('driver_phd_license_document')) {
            $file = $request->file('driver_phd_license_document');
            $mimeType = $file->getMimeType();
            if (str_starts_with($mimeType, 'image/')) {
                $dims = getimagesize($file);
                $width = $dims[0];
                $height = $dims[1];
                $name = time() . '-' . $width . '-' . $height . '.' . $file->extension();
            } else {
                $name = time() . '-' . uniqid() . '.' . $file->extension();
            }
            $path = public_path('uploads/driver_licenses/');
            $file = $request->file('driver_phd_license_document');
            if ($file->move($path, $name)) {
                $validated['driver_phd_license_document'] = $name;
            }
        }

        if ($request->hasFile('phd_card_document')) {
            $file = $request->file('phd_card_document');
            $mimeType = $file->getMimeType();
            if (str_starts_with($mimeType, 'image/')) {
                $dims = getimagesize($file);
                $width = $dims[0];
                $height = $dims[1];
                $name = time() . '-' . $width . '-' . $height . '.' . $file->extension();
            } else {
                $name = time() . '-' . uniqid() . '.' . $file->extension();
            }
            $path = public_path('uploads/driver_licenses/');
            $file = $request->file('phd_card_document');
            if ($file->move($path, $name)) {
                $validated['phd_card_document'] = $name;
            }
        }

        if ($request->hasFile('dvla_license_summary')) {
            $file = $request->file('dvla_license_summary');
            $mimeType = $file->getMimeType();
            if (str_starts_with($mimeType, 'image/')) {
                $dims = getimagesize($file);
                $width = $dims[0];
                $height = $dims[1];
                $name = time() . '-' . $width . '-' . $height . '.' . $file->extension();
            } else {
                $name = time() . '-' . uniqid() . '.' . $file->extension();
            }
            $path = public_path('uploads/driver_licenses/');
            $file = $request->file('dvla_license_summary');
            if ($file->move($path, $name)) {
                $validated['dvla_license_summary'] = $name;
            }
        }

        if ($request->hasFile('misc_document')) {
            $file = $request->file('misc_document');
            $mimeType = $file->getMimeType();
            if (str_starts_with($mimeType, 'image/')) {
                $dims = getimagesize($file);
                $width = $dims[0];
                $height = $dims[1];
                $name = time() . '-' . $width . '-' . $height . '.' . $file->extension();
            } else {
                $name = time() . '-' . uniqid() . '.' . $file->extension();
            }
            $path = public_path('uploads/driver_licenses/');
            $file = $request->file('misc_document');
            if ($file->move($path, $name)) {
                $validated['misc_document'] = $name;
            }
        }

        if ($request->hasFile('proof_of_address_document')) {
            $file = $request->file('proof_of_address_document');
            $mimeType = $file->getMimeType();
            if (str_starts_with($mimeType, 'image/')) {
                $dims = getimagesize($file);
                $width = $dims[0];
                $height = $dims[1];
                $name = time() . '-' . $width . '-' . $height . '.' . $file->extension();
            } else {
                $name = time() . '-' . uniqid() . '.' . $file->extension();
            }
            $path = public_path('uploads/driver_licenses/');
            $file = $request->file('proof_of_address_document');
            if ($file->move($path, $name)) {
                $validated['proof_of_address_document'] = $name;
            }
        }
        $validated['tenant_id'] = Auth::user()->tenant_id;
        $validated['createdBy'] = Auth::user()->id;
        Driver::create($validated);

        return redirect()->route($this->url.'index')
            ->with('success', 'Driver created successfully.');
    }

    public function show(Driver $driver)
    {
        return view($this->dir.'show', compact('driver'));
    }

    public function edit($id)
    {
        $model = Driver::findOrFail($id);
        return view($this->dir.'edit', compact('model'));
    }

    public function update(Request $request, Driver $driver)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'dob' => 'required|date',
            'email' => 'required|email|unique:drivers,email,' . $driver->id,
            'phone_number' => 'required|string|max:20',
            'ni_number' => 'nullable|string|max:20',
            'address1' => 'required|string|max:255',
            'address2' => 'nullable|string|max:255',
            'post_code' => 'required|string|max:20',
            'town' => 'required|string|max:100',
            'county' => 'required|string|max:100',
            'country_id' => 'required|numeric|exists:countries,id',
            'driver_license_number' => 'required|string|unique:drivers,driver_license_number,' . $driver->id,
            'driver_license_expiry_date' => 'required|date',
            'phd_license_number' => 'nullable|string',
            'phd_license_expiry_date' => 'nullable|date',
            'next_of_kin' => 'required|string|max:255',
            'next_of_kin_phone' => 'required|string|max:20',
            'driver_license_document' => 'nullable|file',
            'driver_phd_license_document' => 'nullable|file',
            'phd_card_document' => 'nullable|file',
            'dvla_license_summary' => 'nullable|file',
            'misc_document' => 'nullable|file',
            'proof_of_address_document' => 'nullable|file',
        ]);

        // Handle file uploads
        if ($request->hasFile('driver_license_document')) {
            $file = $request->file('driver_license_document');
            $mimeType = $file->getMimeType();
            if (str_starts_with($mimeType, 'image/')) {
                $dims = getimagesize($file);
                $width = $dims[0];
                $height = $dims[1];
                $name = time() . '-' . $width . '-' . $height . '.' . $file->extension();
            } else {
                $name = time() . '-' . uniqid() . '.' . $file->extension();
            }
            $path = public_path('uploads/driver_licenses/');
            $file = $request->file('driver_license_document');
            $oldImage = $driver->driver_license_document;
            if ($file->move($path, $name)) {
                if ($oldImage) {
                    $image_path = public_path('uploads/driver_licenses/' . $oldImage);
                    if (File::exists($image_path)) {
                        File::delete($image_path);
                    }
                }
                $validated['driver_license_document'] = $name;
            }
        }

        if ($request->hasFile('driver_phd_license_document')) {
            $file = $request->file('driver_phd_license_document');
            $mimeType = $file->getMimeType();
            if (str_starts_with($mimeType, 'image/')) {
                $dims = getimagesize($file);
                $width = $dims[0];
                $height = $dims[1];
                $name = time() . '-' . $width . '-' . $height . '.' . $file->extension();
            } else {
                $name = time() . '-' . uniqid() . '.' . $file->extension();
            }
            $path = public_path('uploads/driver_licenses/');
            $file = $request->file('driver_phd_license_document');
            $oldImage = $driver->driver_phd_license_document;
            if ($file->move($path, $name)) {
                if ($oldImage) {
                    $image_path = public_path('uploads/driver_licenses/' . $oldImage);
                    if (File::exists($image_path)) {
                        File::delete($image_path);
                    }
                }
                $validated['driver_phd_license_document'] = $name;
            }
        }

        if ($request->hasFile('phd_card_document')) {
            $file = $request->file('phd_card_document');
            $mimeType = $file->getMimeType();
            if (str_starts_with($mimeType, 'image/')) {
                $dims = getimagesize($file);
                $width = $dims[0];
                $height = $dims[1];
                $name = time() . '-' . $width . '-' . $height . '.' . $file->extension();
            } else {
                $name = time() . '-' . uniqid() . '.' . $file->extension();
            }
            $path = public_path('uploads/driver_licenses/');
            $file = $request->file('phd_card_document');
            $oldImage = $driver->phd_card_document;
            if ($file->move($path, $name)) {
                if ($oldImage) {
                    $image_path = public_path('uploads/driver_licenses/' . $oldImage);
                    if (File::exists($image_path)) {
                        File::delete($image_path);
                    }
                }
                $validated['phd_card_document'] = $name;
            }
        }

        if ($request->hasFile('dvla_license_summary')) {
            $file = $request->file('dvla_license_summary');
            $mimeType = $file->getMimeType();
            if (str_starts_with($mimeType, 'image/')) {
                $dims = getimagesize($file);
                $width = $dims[0];
                $height = $dims[1];
                $name = time() . '-' . $width . '-' . $height . '.' . $file->extension();
            } else {
                $name = time() . '-' . uniqid() . '.' . $file->extension();
            }
            $path = public_path('uploads/driver_licenses/');
            $file = $request->file('dvla_license_summary');
            $oldImage = $driver->dvla_license_summary;
            if ($file->move($path, $name)) {
                if ($oldImage) {
                    $image_path = public_path('uploads/driver_licenses/' . $oldImage);
                    if (File::exists($image_path)) {
                        File::delete($image_path);
                    }
                }
                $validated['dvla_license_summary'] = $name;
            }
        }

        if ($request->hasFile('misc_document')) {
            $file = $request->file('misc_document');
            $mimeType = $file->getMimeType();
            if (str_starts_with($mimeType, 'image/')) {
                $dims = getimagesize($file);
                $width = $dims[0];
                $height = $dims[1];
                $name = time() . '-' . $width . '-' . $height . '.' . $file->extension();
            } else {
                $name = time() . '-' . uniqid() . '.' . $file->extension();
            }
            $path = public_path('uploads/driver_licenses/');
            $file = $request->file('misc_document');
            $oldImage = $driver->misc_document;
            if ($file->move($path, $name)) {
                if ($oldImage) {
                    $image_path = public_path('uploads/driver_licenses/' . $oldImage);
                    if (File::exists($image_path)) {
                        File::delete($image_path);
                    }
                }
                $validated['misc_document'] = $name;
            }
        }

        if ($request->hasFile('proof_of_address_document')) {
            $file = $request->file('proof_of_address_document');
            $mimeType = $file->getMimeType();
            if (str_starts_with($mimeType, 'image/')) {
                $dims = getimagesize($file);
                $width = $dims[0];
                $height = $dims[1];
                $name = time() . '-' . $width . '-' . $height . '.' . $file->extension();
            } else {
                $name = time() . '-' . uniqid() . '.' . $file->extension();
            }
            $path = public_path('uploads/driver_licenses/');
            $file = $request->file('proof_of_address_document');
            $oldImage = $driver->proof_of_address_document;
            if ($file->move($path, $name)) {
                if ($oldImage) {
                    $image_path = public_path('uploads/driver_licenses/' . $oldImage);
                    if (File::exists($image_path)) {
                        File::delete($image_path);
                    }
                }
                $validated['proof_of_address_document'] = $name;
            }
        }
        $validated['updatedBy'] = Auth::user()->id;
        $driver->update($validated);

        return redirect()->route($this->url.'index')
            ->with('success', 'Driver updated successfully.');
    }

    public function destroy(Driver $driver)
    {
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
