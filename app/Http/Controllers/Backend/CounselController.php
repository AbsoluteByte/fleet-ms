<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Counsel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CounselController extends Controller
{
    protected $url = 'counsels.';
    protected $dir = 'backend.counsels.';
    protected $name = 'Counsels';

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
        $counsels = Counsel::where('tenant_id', $tenant->id)->get();
        return view($this->dir.'index', compact('counsels'));
    }

    public function create()
    {
        $model = new Counsel();
        return view($this->dir.'create', compact('model'));
    }

    public function store(Request $request)
    {
        $tenant = Auth::user()->currentTenant();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:counsels',
        ]);
        $validated['tenant_id'] = $tenant->id;
        $validated['createdBy'] = Auth::id();
        Counsel::create($validated);

        return redirect()->route('counsels.index')
            ->with('success', 'Counsel created successfully.');
    }

    public function edit($id)
    {
        $tenant = Auth::user()->currentTenant();
        if (!$tenant) {
            return redirect()->route('dashboard')
                ->with('error', 'No active company found!');
        }
        $model = Counsel::where('tenant_id', $tenant->id)->findOrFail($id);
        return view($this->dir.'edit', compact('model'));
    }

    public function update(Request $request, Counsel $counsel)
    {
        $tenant = Auth::user()->currentTenant();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:counsels,name,' . $counsel->id,
        ]);
        $validated['tenant_id'] = $tenant->id;
        $validated['updatedBy'] = Auth::id();
        $counsel->update($validated);

        return redirect()->route('counsels.index')
            ->with('success', 'Counsel updated successfully.');
    }

    public function destroy(Counsel $counsel)
    {
        $tenant = Auth::user()->currentTenant();

        // ✅ Check ownership
        if ($counsel->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access');
        }
        $counsel->delete();

        return redirect()->route('counsels.index')
            ->with('success', 'Counsel deleted successfully.');
    }
}
