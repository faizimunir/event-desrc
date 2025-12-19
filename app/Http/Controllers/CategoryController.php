<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::select('id', 'event_id', 'name', 'description', 'status', 'created_at', 'updated_at')
            ->where('status', 'active')
            ->with(['event' => function ($query) {
                $query->select('id', 'name', 'status');
            }])
            ->orderBy('name', 'asc')
            ->get();

        return response()->json($categories);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $category = Category::select('id', 'event_id', 'name', 'description', 'status', 'created_at', 'updated_at')
            ->with(['event' => function ($query) {
                $query->select('id', 'name', 'description', 'status');
            }])
            ->findOrFail($id);

        // Get packages via event_id (packages tersedia untuk semua category di event yang sama)
        $packages = \App\Models\Package::where('event_id', $category->event_id)
            ->where('status', 'active')
            ->select('id', 'event_id', 'name', 'description', 'price', 'status')
            ->get();

        $category->packages = $packages;

        return response()->json($category);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $category = Category::create($validated);

        return response()->json($category, 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'event_id' => 'sometimes|required|exists:events,id',
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|required|in:active,inactive',
        ]);

        $category->update($validated);

        return response()->json($category);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json(['message' => 'Category deleted successfully'], 200);
    }
}
