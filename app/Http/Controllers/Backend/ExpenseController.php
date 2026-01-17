<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ExpenseController extends Controller
{
    protected $url = 'expenses.';
    protected $dir = 'backend.expenses.';
    protected $name = 'Expenses';

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
        $expenses = Expense::where('tenant_id', $tenant->id)->with('car')->latest()->paginate(10);
        return view($this->dir . 'index', compact('expenses'));
    }

    public function create()
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }
        $cars = Car::where('tenant_id', $tenant->id)->with(['carModel', 'company'])->get();
        return view($this->dir . 'create', compact('cars'));
    }


    public function store(Request $request)
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->back()
                ->with('error', 'No active company found!');
        }
        $validated = $request->validate([
            'car_id' => 'required|exists:cars,id',
            'type' => 'required|string|max:255',
            'date' => 'required|date',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'document' => 'nullable|file',
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
            $path = public_path('uploads/expense_documents/');
            $file = $request->file('document');
            if ($file->move($path, $name)) {
                $validated['document'] = $name;
            }
        }
        // ✅ Add tenant_id automatically
        $validated['tenant_id'] = $tenant->id;
        Expense::create($validated);

        return redirect()->route('expenses.index')
            ->with('success', 'Expense created successfully.');
    }

    public function show(Expense $expense)
    {

        $tenant = Auth::user()->currentTenant();

        // ✅ Check ownership
        if ($expense->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to this car');
        }
        $expense->load('car');
        return view('expenses.show', compact('expense'));
    }

    public function edit($id)
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }

        $model = Expense::where('tenant_id', $tenant->id)->findOrFail($id);
        $cars = Car::where('tenant_id', $tenant->id)->with(['carModel', 'company'])->get();

        return view($this->dir . 'edit', compact('model', 'cars'));
    }

    public function update(Request $request, Expense $expense)
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->back()
                ->with('error', 'No active company found!');
        }
        $validated = $request->validate([
            'car_id' => 'required|exists:cars,id',
            'type' => 'required|string|max:255',
            'date' => 'required|date',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'document' => 'nullable|file',
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
            $path = public_path('uploads/expense_documents/');
            $file = $request->file('document');
            $oldImage = $expense->document;
            if ($file->move($path, $name)) {
                if ($oldImage) {
                    $image_path = public_path('uploads/expense_documents/' . $oldImage);
                    if (File::exists($image_path)) {
                        File::delete($image_path);
                    }
                }
                $validated['document'] = $name;
            }
        }
        // ✅ Ensure tenant_id stays the same
        $validated['tenant_id'] = $tenant->id;
        $expense->update($validated);

        return redirect()->route('expenses.index')
            ->with('success', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense)
    {
        $tenant = Auth::user()->currentTenant();

        // ✅ Check ownership
        if ($expense->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access');
        }
        if ($expense) {
            $image_path = public_path('uploads/expense_documents/' . $expense->document);
            if (File::exists($image_path)) {
                File::delete($image_path);
            }
        }
        $expense->delete();

        return redirect()->route('expenses.index')
            ->with('success', 'Expense deleted successfully.');
    }
}
