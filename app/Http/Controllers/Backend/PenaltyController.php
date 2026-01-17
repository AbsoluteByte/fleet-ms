<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Penalty;
use App\Models\Agreement;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PenaltyController extends Controller
{
    protected $url = 'penalties.';
    protected $dir = 'backend.penalties.';
    protected $name = 'Penalties';

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
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found! Please contact administrator.');
        }
        $penalties = Penalty::where('tenant_id', $tenant->id)->with(['agreement', 'status'])->get();
        return view($this->dir . 'index', compact('penalties'));
    }

    public function create()
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }
        $agreements = Agreement::where('tenant_id', $tenant->id)->with(['driver', 'car', 'company'])->get();
        $statuses = Status::where('type', 'penalty')->get();

        return view($this->dir . 'create', compact('agreements', 'statuses'));
    }

    public function store(Request $request)
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->back()
                ->with('error', 'No active company found!');
        }
        $validated = $request->validate([
            'agreement_id' => 'required|exists:agreements,id',
            'date' => 'required|date',
            'due_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'document' => 'nullable|file',
            'status_id' => 'required|exists:statuses,id',
        ]);


        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $mimeType = $file->getMimeType();
            if (str_starts_with($mimeType, 'image/')) {
                $dims = getimagesize($file);
                $width = $dims[0];
                $height = $dims[1];
                $name = time() . '-' . $width . '-' . $height . '.' . $file->extension();
            } else {
                $name = time() . '.' . $file->extension();
            }
            $path = public_path('uploads/penalties/');
            $file = $request->file('document');
            if ($file->move($path, $name)) {
                $validated['document'] = $name;
            }
        }
        // ✅ Add tenant_id automatically
        $validated['tenant_id'] = $tenant->id;
        Penalty::create($validated);

        return redirect()->route('penalties.index')
            ->with('success', 'Penalty created successfully.');
    }

    public function show(Penalty $penalty)
    {
        $tenant = Auth::user()->currentTenant();

        // ✅ Check ownership
        if ($penalty->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to this car');
        }
        $penalty->load(['agreement.driver', 'agreement.car', 'status']);
        return view($this->dir . 'show', compact('penalty'));
    }

    public function edit($id)
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }
        $model = Penalty::where('tenant_id', $tenant->id)->findOrFail($id);
        $agreements = Agreement::where('tenant_id', $tenant->id)->with(['driver', 'car', 'company'])->get();
        $statuses = Status::where('type', 'penalty')->get();

        return view($this->dir . 'edit', compact('model', 'agreements', 'statuses'));
    }

    public function update(Request $request, Penalty $penalty)
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->back()
                ->with('error', 'No active company found!');
        }
        $validated = $request->validate([
            'agreement_id' => 'required|exists:agreements,id',
            'date' => 'required|date',
            'due_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'document' => 'nullable|file',
            'status_id' => 'required|exists:statuses,id',
        ]);

        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $mimeType = $file->getMimeType();
            if (str_starts_with($mimeType, 'image/')) {
                $dims = getimagesize($file);
                $width = $dims[0];
                $height = $dims[1];
                $name = time() . '-' . $width . '-' . $height . '.' . $file->extension();
            } else {
                $name = time() . '.' . $file->extension();
            }
            $path = public_path('uploads/penalties/');
            $file = $request->file('document');
            $oldImage = $penalty->document;
            if ($file->move($path, $name)) {
                if ($oldImage) {
                    $image_path = public_path('uploads/penalties/' . $oldImage);
                    if (File::exists($image_path)) {
                        File::delete($image_path);
                    }
                }
                $validated['document'] = $name;
            }
        }
        // ✅ Ensure tenant_id stays the same
        $validated['tenant_id'] = $tenant->id;
        $penalty->update($validated);

        return redirect()->route('penalties.index')
            ->with('success', 'Penalty updated successfully.');
    }

    public function destroy(Penalty $penalty)
    {
        $tenant = Auth::user()->currentTenant();

        // ✅ Check ownership
        if ($penalty->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access');
        }
        if ($penalty) {
            $image_path = public_path('uploads/penalties/' . $penalty->document);
            if (File::exists($image_path)) {
                File::delete($image_path);
            }
        }
        $penalty->delete();

        return redirect()->route('penalties.index')
            ->with('success', 'Penalty deleted successfully.');
    }
}
