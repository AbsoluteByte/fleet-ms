<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class CustomerController extends Controller
{
    protected $url = 'customers.';
    protected $dir = 'backend.customers.';
    protected $name = 'Customers';

    public function __construct()
    {
        $this->middleware('role:superuser');
        view()->share('url', $this->url);
        view()->share('dir', $this->dir);
        view()->share('singular', Str::singular($this->name));
        view()->share('plural', Str::plural($this->name));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::where('parent_id', null)->role(['admin'])->get();
        return view($this->dir . 'index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $model = new User();
        return view($this->dir . 'create', compact('model'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => ['required', 'string', 'max:50'],
        ]);

        $model = new User();
        $model->name = request('name', null);
        $model->save();

        return redirect()->route($this->url . 'index')->with('success', Str::singular($this->name) . ' saved Successfully!');
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\User $user
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $model = User::where('id', $id)->firstOrFail();
        return view($this->dir . 'show', compact('model'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\User $User
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $model = User::where('id', $id)->firstOrFail();

        return view($this->dir . 'edit', compact('model'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\User $User
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $model = User::where('id', $id)->firstOrFail();

        $this->validate($request, [
            'name' => ['required', 'string', 'max:50'],
        ]);

        $model->name = request('name', null);
        $model->save();

        return redirect()->route($this->url . 'index')->with('success', Str::singular($this->name) . ' updated Successfully!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\User $User
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $model = User::where('id', $id)->firstOrFail();
        $model->delete();

        return redirect()->route($this->url . 'index')->with('success', Str::singular($this->name) . ' deleted Successfully!');
    }

}
