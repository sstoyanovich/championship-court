<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminCollectionController extends Controller
{
    /**
     * Display a listing of collections with search and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $query = Collection::query()->with(['rewards']);

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('sub_collection', 'like', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $collections = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $collections,
        ]);
    }

    /**
     * Store a newly created collection
     */
    public function store(Request $request): JsonResponse
    {
        $collection = Collection::create($request->all());
        $collection->load(['rewards']);

        return response()->json([
            'success' => true,
            'data' => $collection,
            'message' => 'Collection created successfully',
        ], 201);
    }

    /**
     * Display the specified collection
     */
    public function show(string $id): JsonResponse
    {
        $collection = Collection::with(['rewards'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $collection,
        ]);
    }

    /**
     * Update the specified collection
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $collection = Collection::findOrFail($id);
        $collection->update($request->all());
        $collection->load(['rewards']);

        return response()->json([
            'success' => true,
            'data' => $collection,
            'message' => 'Collection updated successfully',
        ]);
    }

    /**
     * Remove the specified collection
     */
    public function destroy(string $id): JsonResponse
    {
        $collection = Collection::findOrFail($id);
        $collection->delete();

        return response()->json([
            'success' => true,
            'message' => 'Collection deleted successfully',
        ]);
    }
}
