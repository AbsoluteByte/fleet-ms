<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Penalty;
use App\Models\Agreement;
use App\Models\Status;
use Illuminate\Http\Request;
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
        $penalties = Penalty::with(['agreement', 'status'])->get();
        return view($this->dir . 'index', compact('penalties'));
    }

    public function create()
    {
        $agreements = Agreement::with(['driver', 'car', 'company'])->get();
        $statuses = Status::where('type', 'penalty')->get();

        return view($this->dir . 'create', compact('agreements', 'statuses'));
    }

    public function store(Request $request)
    {
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

        Penalty::create($validated);

        return redirect()->route('penalties.index')
            ->with('success', 'Penalty created successfully.');
    }

    public function show(Penalty $penalty)
    {
        $penalty->load(['agreement.driver', 'agreement.car', 'status']);
        return view($this->dir . 'show', compact('penalty'));
    }

    public function edit($id)
    {
        $model = Penalty::findOrFail($id);
        $agreements = Agreement::with(['driver', 'car', 'company'])->get();
        $statuses = Status::where('type', 'penalty')->get();

        return view($this->dir . 'edit', compact('model', 'agreements', 'statuses'));
    }

    public function update(Request $request, Penalty $penalty)
    {
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

        $penalty->update($validated);

        return redirect()->route('penalties.index')
            ->with('success', 'Penalty updated successfully.');
    }

    public function destroy(Penalty $penalty)
    {
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
