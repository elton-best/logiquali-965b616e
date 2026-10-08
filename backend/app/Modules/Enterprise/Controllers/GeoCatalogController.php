<?php

namespace App\Modules\Enterprise\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GeoCatalogController extends Controller
{
    public function countries(Request $request)
    {
        $q = mb_strtolower((string) $request->query('q', ''));
        $limit = min(max((int) $request->query('limit', 500), 1), 500);

        $countries = collect(config('geo_catalog.countries', []))
            ->map(fn (array $country) => [
                'code' => $country['code'],
                'name' => $country['name'],
            ])
            ->when($q !== '', fn ($collection) => $collection->filter(function (array $country) use ($q) {
                return str_contains(mb_strtolower($country['name']), $q)
                    || str_contains(mb_strtolower($country['code']), $q);
            }))
            ->values()
            ->take($limit);

        return response()->json([
            'data' => $countries,
        ]);
    }

    public function cities(Request $request, string $countryCode)
    {
        $q = mb_strtolower((string) $request->query('q', ''));
        $limit = min(max((int) $request->query('limit', 500), 1), 500);
        $countryCode = strtoupper(trim($countryCode));

        $country = collect(config('geo_catalog.countries', []))
            ->first(fn (array $entry) => strtoupper($entry['code']) === $countryCode);

        if (!$country) {
            return response()->json([
                'message' => 'Country not found',
                'errors' => [
                    'country_code' => ['Invalid country code'],
                ],
            ], 422);
        }

        $cities = collect($country['cities'] ?? [])
            ->map(fn (string $name) => ['name' => $name])
            ->when($q !== '', fn ($collection) => $collection->filter(function (array $city) use ($q) {
                return str_contains(mb_strtolower($city['name']), $q);
            }))
            ->values()
            ->take($limit);

        return response()->json([
            'data' => $cities,
            'meta' => [
                'country' => [
                    'code' => $country['code'],
                    'name' => $country['name'],
                ],
            ],
        ]);
    }
}

