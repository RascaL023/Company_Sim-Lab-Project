<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::query()
            ->when($request->filled('type'), fn ($q, $type) => $q->where('type', $type));

        return Category::paginate($request->query('per_page', 15));
    }

    public function store(Request $request)
    {
        return Category::create($request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:alat,bahan',
            'description' => 'nullable|string',
        ]));
    }

    public function show(Category $category)
    {
        return $category;
    }

    public function update(Request $request, Category $category)
    {
        $category->update($request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:alat,bahan',
            'description' => 'nullable|string',
        ]));

        return $category;
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return response()->json(null, 204);
    }
}
