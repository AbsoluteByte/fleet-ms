<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\CarModel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CarModelController extends Controller
{
    protected $url = 'car-models.';
    protected $dir = 'backend.carModels.';
    protected $name = 'Car Models';

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
        $carModels = CarModel::get();
        return view($this->dir. 'index', compact('carModels'));
    }

    public function create()
    {
        return view($this->dir.'create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:car_models',
        ]);

        CarModel::create($validated);

        return redirect()->route('car-models.index')
            ->with('success', 'Car model created successfully.');
    }

    public function edit($id)
    {
        $model = CarModel::findOrFail($id);
        return view($this->dir.'edit', compact('model'));
    }

    public function update(Request $request, CarModel $carModel)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:car_models,name,' . $carModel->id,
        ]);

        $carModel->update($validated);

        return redirect()->route('car-models.index')
            ->with('success', 'Car model updated successfully.');
    }

    public function destroy(CarModel $carModel)
    {
        $carModel->delete();

        return redirect()->route('car-models.index')
            ->with('success', 'Car model deleted successfully.');
    }
}
