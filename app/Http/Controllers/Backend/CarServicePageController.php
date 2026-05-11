<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CarServicePageController extends Controller
{
    public const HISTORY_INITIAL_ROWS = 5;

    public function __construct()
    {
        $this->middleware('role:admin|manager|user');
    }

    public function index(Request $request)
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found! Please contact administrator.');
        }

        $cars = Car::query()
            ->forCurrentTenant()
            ->with(['company', 'carModel'])
            ->orderBy('registration')
            ->get();

        $selectedCar = null;
        $services = collect();

        $carId = $request->query('car_id');
        if ($carId) {
            $selectedCar = Car::query()
                ->forCurrentTenant()
                ->whereKey($carId)
                ->first();

            if ($selectedCar) {
                $services = $selectedCar->services()
                    ->orderByDesc('service_date')
                    ->orderByDesc('id')
                    ->get();
            }
        }

        return view('backend.car_services.index', [
            'cars' => $cars,
            'selectedCar' => $selectedCar,
            'services' => $services,
            'initialHistoryLimit' => self::HISTORY_INITIAL_ROWS,
        ]);
    }

    public function store(Request $request)
    {
        $tenant = Auth::user()->currentTenant();

        if (! $tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found! Please contact administrator.');
        }

        $validated = $request->validate([
            'car_id' => 'required|exists:cars,id',
            'service_date' => 'required|date',
            'service_mileage' => 'nullable|integer|min:0',
            'service_notes' => 'nullable|string',
            'service_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $mileage = $validated['service_mileage'] ?? null;
        $notes = $validated['service_notes'] ?? null;

        $car = Car::query()
            ->forCurrentTenant()
            ->whereKey($validated['car_id'])
            ->first();

        if (! $car) {
            return redirect()
                ->route('car-services.index')
                ->with('error', 'Car not found or not available for your company.');
        }

        $serviceData = [
            'tenant_id' => $tenant->id,
            'service_date' => $validated['service_date'],
            'mileage' => $mileage,
            'notes' => $notes,
            'created_by' => Auth::id(),
        ];

        if ($request->hasFile('service_document')) {
            $serviceData['document'] = $this->uploadFile(
                $request->file('service_document'),
                'uploads/cars/service_documents'
            );
        }

        $alreadyExists = $car->services()
            ->whereDate('service_date', $serviceData['service_date'])
            ->exists();

        if (! $alreadyExists) {
            $car->services()->create($serviceData);

            return redirect()
                ->route('car-services.index', ['car_id' => $car->id])
                ->with('success', 'Service record saved.');
        }

        return redirect()
            ->route('car-services.index', ['car_id' => $car->id])
            ->with('warning', 'A service for this date already exists for this car.');
    }

    private function uploadFile($file, $directory)
    {
        $mimeType = $file->getMimeType();

        if (str_starts_with($mimeType, 'image/')) {
            $dims = getimagesize($file);
            $width = $dims[0];
            $height = $dims[1];
            $name = time().'-'.uniqid().'-'.$width.'-'.$height.'.'.$file->extension();
        } else {
            $name = time().'-'.uniqid().'.'.$file->extension();
        }

        $path = public_path($directory);

        if (! file_exists($path)) {
            mkdir($path, 0755, true);
        }

        if ($file->move($path, $name)) {
            return $name;
        }

        throw new \Exception('Failed to upload file');
    }
}
