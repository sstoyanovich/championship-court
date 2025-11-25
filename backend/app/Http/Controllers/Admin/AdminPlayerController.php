<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminPlayerController extends Controller
{
    /**
     * Display a listing of players with search and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $query = Player::query()->with('collection');

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('team', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%");
            });
        }

        // Collection filter
        if ($request->filled('collection_id')) {
            $query->where('collection_id', $request->collection_id);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $players = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $players,
        ]);
    }

    /**
     * Store a newly created player
     */
    public function store(Request $request): JsonResponse
    {
        $player = Player::create($request->all());

        return response()->json([
            'success' => true,
            'data' => $player,
            'message' => 'Player created successfully',
        ], 201);
    }

    /**
     * Display the specified player
     */
    public function show(string $id): JsonResponse
    {
        $player = Player::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $player,
        ]);
    }

    /**
     * Update the specified player
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $player = Player::findOrFail($id);
        $player->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $player,
            'message' => 'Player updated successfully',
        ]);
    }

    /**
     * Remove the specified player
     */
    public function destroy(string $id): JsonResponse
    {
        $player = Player::findOrFail($id);
        $player->delete();

        return response()->json([
            'success' => true,
            'message' => 'Player deleted successfully',
        ]);
    }

    /**
     * Get list of available card art images
     */
    public function getCardArtImages(): JsonResponse
    {
        $cardArtPath = public_path('images/card_art');
        $images = [];

        if (is_dir($cardArtPath)) {
            // Recursively scan directory for image files
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($cardArtPath, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file->getFilename())) {
                    // Get relative path from card_art directory
                    $relativePath = str_replace($cardArtPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                    // Convert backslashes to forward slashes for consistency
                    $relativePath = str_replace('\\', '/', $relativePath);
                    $images[] = [
                        'filename' => $file->getFilename(),
                        'path' => $relativePath,
                        'fullUrl' => asset('images/card_art/' . $relativePath),
                    ];
                }
            }
        }

        // Sort by filename
        usort($images, function ($a, $b) {
            return strcmp($a['filename'], $b['filename']);
        });

        return response()->json([
            'success' => true,
            'data' => $images,
        ]);
    }

    /**
     * Bulk update players
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'player_ids' => 'required|array',
            'player_ids.*' => 'integer|exists:players,id',
            'updates' => 'required|array',
        ]);

        $playerIds = $validated['player_ids'];
        $updates = $validated['updates'];

        // Only allow specific fields to be bulk updated
        $allowedFields = ['obtainable_from_packs', 'is_tradeable', 'collection_id', 'card_tier'];
        $filteredUpdates = array_filter($updates, function ($key) use ($allowedFields) {
            return in_array($key, $allowedFields);
        }, ARRAY_FILTER_USE_KEY);

        if (empty($filteredUpdates)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid fields to update',
            ], 400);
        }

        $updated = Player::whereIn('id', $playerIds)->update($filteredUpdates);

        return response()->json([
            'success' => true,
            'message' => "Successfully updated {$updated} players",
            'updated_count' => $updated,
        ]);
    }
}
