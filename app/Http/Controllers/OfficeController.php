<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\Reservation;
use Illuminate\Http\Request;
use App\Http\Resources\OfficeResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\JsonResource;

class OfficeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(): JsonResource
    {
        $offices = Office::query()
            ->where('approval_status', Office::APPROVAL_APPROVED)
            ->where('hidden', false)
            ->when(request('host_id'), fn ($builder) => $builder->whereUserId(request('host_id')))
            ->when(request('user_id'),
                fn ($builder) => 
                    $builder->whereRelation('reservations', 'user_id', '=', request('user_id'))
            )
            ->when(request('lat') && request('lng'),
                fn ($builder) => $builder->nearestTo(request('lat'), request('lng')),
                fn ($builder) => $builder->orderBy('id', 'ASC')
            )
            ->latest('id')
            ->with(['images', 'tags', 'user'])
            ->withCount(['reservations' => fn ($builder) => $builder->whereStatus(Reservation::STATUS_ACTIVE)])
            ->paginate(20);

        return OfficeResource::collection($offices);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Office  $office
     * @return \Illuminate\Http\Response
     */
    public function show(Office $office): JsonResource
    {
        $office->loadCount([
            'reservations' => fn ($builder) => $builder->whereStatus(Reservation::STATUS_ACTIVE)
        ])
        ->load(['images', 'tags', 'user']);

        return OfficeResource::make($office);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Office  $office
     * @return \Illuminate\Http\Response
     */
    public function edit(Office $office)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Office  $office
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Office $office)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Office  $office
     * @return \Illuminate\Http\Response
     */
    public function destroy(Office $office)
    {
        //
    }
}
