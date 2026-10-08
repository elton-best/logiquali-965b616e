<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OfferResource;
use App\Models\Offer;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    public function index()
    {
        $offers = Offer::with(['norms', 'subscriptions'])
            ->paginate(20);

        return OfferResource::collection($offers);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'norms' => 'required|array|min:1',
            'norms.*' => 'exists:norms,id',
            'is_active' => 'boolean',
            'price' => 'required|numeric|min:0',
            'duration_months' => 'required|integer|min:1',
        ]);

        $offer = Offer::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'is_active' => $validated['is_active'] ?? true,
            'price' => $validated['price'],
            'duration_months' => $validated['duration_months'],
        ]);

        // Attach norms
        $offer->norms()->attach($validated['norms']);

        return new OfferResource($offer->load('norms'));
    }

    public function show(Offer $offer)
    {
        return new OfferResource($offer->load(['norms', 'subscriptions']));
    }

    public function update(Request $request, Offer $offer)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'norms' => 'sometimes|array|min:1',
            'norms.*' => 'exists:norms,id',
            'is_active' => 'boolean',
            'price' => 'sometimes|numeric|min:0',
            'duration_months' => 'sometimes|integer|min:1',
        ]);

        $offer->update(array_diff_key($validated, ['norms' => '']));

        // Sync norms if provided
        if (isset($validated['norms'])) {
            $offer->norms()->sync($validated['norms']);
        }

        return new OfferResource($offer->load('norms'));
    }

    public function destroy(Offer $offer)
    {
        $offer->delete();

        return response()->json(null, 204);
    }
}

