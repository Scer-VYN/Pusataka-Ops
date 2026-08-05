<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Category::query()
                ->withCount('books')
                ->orderBy('name')
                ->get(['id', 'name', 'rack']),
        ]);
    }

    public function racks(): JsonResponse
    {
        return response()->json([
            'data' => Category::query()
                ->select('rack')
                ->whereNotNull('rack')
                ->distinct()
                ->orderBy('rack')
                ->pluck('rack')
                ->values(),
        ]);
    }
}
