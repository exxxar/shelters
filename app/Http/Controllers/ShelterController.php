<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShelterStoreRequest;
use App\Http\Requests\ShelterUpdateRequest;
use App\Http\Resources\ShelterCollection;
use App\Http\Resources\ShelterResource;
use App\Models\Shelter;
use Illuminate\Http\Request;

class ShelterController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return \App\Http\Resources\ShelterCollection
     */
    public function index(Request $request)
    {
        $shelters = Shelter::all();

        return new ShelterCollection($shelters);
    }

    /**
     * @param \App\Http\Requests\ShelterStoreRequest $request
     * @return \App\Http\Resources\ShelterResource
     */
    public function store(ShelterStoreRequest $request)
    {
        $shelter = Shelter::create($request->validated());

        return new ShelterResource($shelter);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Shelter $shelter
     * @return \App\Http\Resources\ShelterResource
     */
    public function show(Request $request, Shelter $shelter)
    {
        return new ShelterResource($shelter);
    }

    /**
     * @param \App\Http\Requests\ShelterUpdateRequest $request
     * @param \App\Models\Shelter $shelter
     * @return \App\Http\Resources\ShelterResource
     */
    public function update(ShelterUpdateRequest $request, Shelter $shelter)
    {
        $shelter->update($request->validated());

        return new ShelterResource($shelter);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Shelter $shelter
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Shelter $shelter)
    {
        $shelter->delete();

        return response()->noContent();
    }
}
