<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pack;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminPackController extends Controller
{
    /**
     * Display a listing of packs with search and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $query = Pack::query();

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%");
            });
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $packs = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $packs,
        ]);
    }

    /**
     * Store a newly created pack
     */
    public function store(Request $request): JsonResponse
    {
        $pack = Pack::create($request->all());

        return response()->json([
            'success' => true,
            'data' => $pack,
            'message' => 'Pack created successfully',
        ], 201);
    }

    /**
     * Display the specified pack
     */
    public function show(string $id): JsonResponse
    {
        $pack = Pack::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $pack,
        ]);
    }

    /**
     * Update the specified pack
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $pack = Pack::findOrFail($id);
        $pack->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $pack,
            'message' => 'Pack updated successfully',
        ]);
    }

    /**
     * Remove the specified pack
     */
    public function destroy(string $id): JsonResponse
    {
        $pack = Pack::findOrFail($id);
        $pack->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pack deleted successfully',
        ]);
    }
}
