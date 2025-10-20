<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }


    public function showAcceptForm($token)
    {
        $driver = Driver::where('invitation_token', $token)->first();

        if (!$driver) {
            return redirect()->route('login')
                ->with('error', 'Invalid invitation link.');
        }

        if ($driver->hasAcceptedInvitation()) {
            return redirect()->route('login')
                ->with('info', 'This invitation has already been accepted. Please login with your credentials.');
        }

        if ($driver->isInvitationExpired()) {
            return redirect()->route('login')
                ->with('error', 'This invitation has expired. Please contact your fleet manager for a new invitation.');
        }

        return view('driver.accept-invitation', compact('driver'));
    }

    public function acceptInvitation(Request $request, $token)
    {
        $driver = Driver::where('invitation_token', $token)->first();

        if (!$driver || $driver->hasAcceptedInvitation() || $driver->isInvitationExpired()) {
            return redirect()->route('login')
                ->with('error', 'Invalid or expired invitation.');
        }


        $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'terms' => 'required|accepted',
        ]);

        try {
            // Create user account for driver
            $user = User::create([
                'name' => $driver->full_name,
                'email' => $driver->email,
                'password' => Hash::make($request->password),
            ]);

            // Assign driver role
            $user->assignRole('driver');

            // Link driver to user
            $driver->update([
                'user_id' => $user->id,
                'invitation_accepted_at' => now(),
                'invitation_token' => null
            ]);

            // Auto login the driver
            Auth::login($user);

            return redirect()->route('driver.dashboard')
                ->with('success', 'Welcome! Your account has been created successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create account: ' . $e->getMessage());
        }
    }
}
