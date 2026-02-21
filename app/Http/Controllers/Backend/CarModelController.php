<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\CarModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CarModelController extends Controller
{
    protected $url = 'car-models.';
    protected $dir = 'backend.carModels.';
    protected $name = 'Car Models';

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
        $carModels = CarModel::where('tenant_id', $tenant->id)->get();
        return view($this->dir. 'index', compact('carModels'));
    }

    public function create()
    {
        return view($this->dir.'create');
    }

    public function store(Request $request)
    {
        $tenant = Auth::user()->currentTenant();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:car_models',
        ]);
        $validated['tenant_id'] = $tenant->id;
        $validated['createdBy'] = Auth::id();
        CarModel::create($validated);

        return redirect()->route('car-models.index')
            ->with('success', 'Car model created successfully.');
    }

    public function edit($id)
    {
        $tenant = Auth::user()->currentTenant();

        if (!$tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }

        $model = CarModel::where('tenant_id', $tenant->id)->findOrFail($id);
        return view($this->dir.'edit', compact('model'));
    }

    public function update(Request $request, CarModel $carModel)
    {
        $tenant = Auth::user()->currentTenant();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:car_models,name,' . $carModel->id,
        ]);
        $validated['tenant_id'] = $tenant->id;
        $validated['updatedBy'] = Auth::id();
        $carModel->update($validated);

        return redirect()->route('car-models.index')
            ->with('success', 'Car model updated successfully.');
    }

    public function destroy(CarModel $carModel)
    {
        $tenant = Auth::user()->currentTenant();

        // ✅ Check ownership
        if ($carModel->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access');
        }
        $carModel->delete();

        return redirect()->route('car-models.index')
            ->with('success', 'Car model deleted successfully.');
    }
}
