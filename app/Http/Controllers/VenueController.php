<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Venue;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
	    $venues = Venue::join('cities', 'venues.city_id', '=', 'cities.id')
		    ->select('venues.*')
		    ->orderBy("cities.name")
		    ->paginate(10);
        return view('admin.venues.index', compact('venues'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $cities = City::orderBy('name', 'asc')
	                    ->pluck('name', 'id')
	                    ->all();
        return view('admin.venues.create', compact('cities'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
	    $request->validate([
		    'city_id' => 'bail|required|numeric|exists:cities,id',
		    'name' => 'required|string',
	    ]);

        Venue::create($request->all());
        return redirect('/admin/venues');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
	    $cities = City::orderBy('name', 'asc')
		    ->pluck('name', 'id')
		    ->all();
	    $venue = Venue::findOrFail($id);

	    return view('admin.venues.edit', compact('cities', 'venue'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

	    $request->validate([
		    'city_id' => 'bail|required|numeric|exists:cities,id',
		    'name' => 'required|string',
	    ]);
        Venue::findOrFail($id)->update($request->all());
        return redirect('/admin/venues');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Venue::findOrFail($id)->delete();
        return redirect('/admin/venues');
    }

    public function api_index(){
        return Venue::orderBy('name')->get()->toJson();
    }
}
