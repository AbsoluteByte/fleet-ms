<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Agreement;
use App\Models\Car;
use App\Models\Driver;
use App\Models\Company;
use App\Models\InsuranceProvider;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgreementController extends Controller
{
    public function create()
    {
        $companies = Company::all();
        $drivers = Driver::all();
        $cars = Car::with('carModel')->get();
        $insuranceProviders = InsuranceProvider::all();
        $statuses = Status::where('type', 'agreement')->get();

        return view('frontend.agreements.create', compact(
            'companies', 'drivers', 'cars', 'insuranceProviders', 'statuses'
        ));
    }

    public function store(Request $request)
    {
        // Similar validation as admin panel
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'driver_id' => 'required|exists:drivers,id',
            'car_id' => 'required|exists:cars,id',
            'status_id' => 'required|exists:statuses,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'agreed_rent' => 'required|numeric|min:0',
            'deposit_amount' => 'required|numeric|min:0',
            'using_own_insurance' => 'required|boolean',
            // ... other validations
        ]);

        try {
            $agreement = DB::transaction(function () use ($validated, $request) {
                // Create agreement
                $agreement = Agreement::create($validated);

                // Handle collections, insurance, etc.

                return $agreement;
            });

            return redirect()->route('frontend.agreements.success')
                ->with('success', 'Agreement created successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating agreement: ' . $e->getMessage());
        }
    }
}
