<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::query()
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->query('type')))
            ->orderBy('name')
            ->paginate($request->query('per_page', 15));

        return CategoryResource::collection($categories);
    }

    public function store(Request $request)
    {
        $category = Category::create($request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:alat,bahan',
            'description' => 'nullable|string',
        ]));

        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Category $category)
    {
        return new CategoryResource($category);
    }

    public function update(Request $request, Category $category)
    {
        $category->update($request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:alat,bahan',
            'description' => 'nullable|string',
        ]));

        return new CategoryResource($category);
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return response()->json(null, 204);
    }
}
