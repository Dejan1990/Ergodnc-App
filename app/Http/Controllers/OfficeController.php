<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\Reservation;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Resources\OfficeResource;
use Illuminate\Http\Response;
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
            ->when(request('user_id'), fn ($builder) => $builder->whereUserId(request('user_id')))
            ->when(request('visitor_id'),
                fn ($builder) => 
                    $builder->whereRelation('reservations', 'user_id', '=', request('visitor_id'))
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
    public function create(): JsonResource
    {
        abort_unless(
            auth()->user()->tokenCan('office.create'),
            Response::HTTP_FORBIDDEN
        );

        $attributes = validator(request()->all(),
            [
                'title' => ['required', 'string'],
                'description' => ['required', 'string'],
                'lat' => ['required', 'numeric'],
                'lng' => ['required', 'numeric'],
                'address_line1' => ['required', 'string'],
                'hidden' => ['bool'],
                'price_per_day' => ['required', 'integer', 'min:100'],
                'monthly_discount' => ['integer', 'min:0', 'max:90'],

                'tags' => ['array'],
                'tags.*' => ['integer', Rule::exists('tags', 'id')]
            ]
        )->validate();

        $attributes['approval_status'] = Office::APPROVAL_PENDING;

        $office = auth()->user()->offices()->create(
            Arr::except($attributes, ['tags'])
        );

        $office->tags()->sync($attributes['tags']);

        return OfficeResource::make(
            $office->load(['images', 'tags', 'user'])
        );
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
