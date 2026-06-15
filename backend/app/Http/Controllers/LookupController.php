<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\City;
use Illuminate\Http\JsonResponse;

class LookupController extends Controller
{
    public function categories(): JsonResponse
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description', 'sort_order']);

        return response()->json([
            'data' => $categories,
        ]);
    }

    public function cities(): JsonResponse
    {
        $cities = City::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'province', 'country_code', 'latitude', 'longitude', 'sort_order']);

        return response()->json([
            'data' => $cities,
        ]);
    }
}
