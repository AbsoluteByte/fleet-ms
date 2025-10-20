<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Counsel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CounselController extends Controller
{
    protected $url = 'counsels.';
    protected $dir = 'backend.counsels.';
    protected $name = 'Counsels';

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
        $counsels = Counsel::get();
        return view($this->dir.'index', compact('counsels'));
    }

    public function create()
    {
        $model = new Counsel();
        return view($this->dir.'create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:counsels',
        ]);

        Counsel::create($validated);

        return redirect()->route('counsels.index')
            ->with('success', 'Counsel created successfully.');
    }

    public function edit($id)
    {
        $model = Counsel::findOrFail($id);
        return view($this->dir.'edit', compact('model'));
    }

    public function update(Request $request, Counsel $counsel)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:counsels,name,' . $counsel->id,
        ]);

        $counsel->update($validated);

        return redirect()->route('counsels.index')
            ->with('success', 'Counsel updated successfully.');
    }

    public function destroy(Counsel $counsel)
    {
        $counsel->delete();

        return redirect()->route('counsels.index')
            ->with('success', 'Counsel deleted successfully.');
    }
}
