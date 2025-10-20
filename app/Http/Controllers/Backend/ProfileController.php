<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;

use App\Models\ProfileDetail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use BaconQrCode\Writer;
use BaconQrCode\Renderer\GDLibRenderer;

class ProfileController extends Controller
{
    //protected $url = 'profile-update.';
    protected $dir = 'backend.profile.';
    protected $name = 'Profile Detail';

    public function __construct()
    {
        //$this->middleware('role:superuser');
        view()->share('dir', $this->dir);
        view()->share('singular', Str::singular($this->name));
        view()->share('plural', Str::plural($this->name));
    }

    public function index(): View
    {
        return view($this->dir . 'profile');
    }

    public function create()
    {
        return view($this->dir . 'setup');
    }

    public function update(Request $request): RedirectResponse
    {
        $model = User::find(auth()->user()->id);

        if ($request->hasFile('profile_image')) {
            $dims = getimagesize($request->profile_image);
            $width = $dims[0];
            $height = $dims[1];
            $name = time() . '-' . $width . '-' . $height . '.' . $request->file('profile_image')->extension();
            $path = public_path('uploads/users/');
            $file = $request->file('profile_image');
            if ($file->move($path, $name)) {
                $model->profile_image = $name;
            }
        }
        $model->name = request('name', 'null');
        $model->email = request('email', 'null');
        $model->save();

        return redirect()->route('profile')->with('success', Str::singular($this->name) . ' updated Successfully!');
    }

    public function change_password(Request $request)
    {
        $this->validate($request, [
            'old_password' => ['required'],
            'password' => ['required'],
            'password_confirmation' => ['same:password'],
        ]);
        //$data = $request->all();
        $user = User::find(auth()->user()->id);
        if (!(Hash::check($request->get('old_password'), $user->password))) {
            return redirect()->back()->with('error', 'You have entered wrong old password!');
        } else {
            User::find(auth()->user()->id)->update(['password' => Hash::make($request->password)]);
            return redirect()->route('profile')->with('success', Str::singular($this->name) . 'Password updated Successfully!');
        }

    }


}
