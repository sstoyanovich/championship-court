<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\PackController;
use App\Http\Controllers\ImageProxyController;
use App\Http\Controllers\LineupController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\Admin\AdminPlayerController;
use App\Http\Controllers\Admin\AdminProgramController;
use App\Http\Controllers\Admin\AdminPackController;
use App\Http\Controllers\Admin\AdminCollectionController;
use App\Http\Controllers\Admin\AdminCollectionRewardController;
use App\Http\Controllers\Admin\AdminTeamAffinityController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Image proxy to avoid CORS issues (public)
Route::get('/image-proxy', [ImageProxyController::class, 'proxy']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Pack management
    Route::get('/packs', [PackController::class, 'index']);
    Route::post('/packs/{packId}/open', [PackController::class, 'open']);
    Route::post('/packs/{packId}/select', [PackController::class, 'selectCards']);

    // Collection management
    Route::get('/collection', [CollectionController::class, 'index']);
    Route::get('/collection/stats', [CollectionController::class, 'stats']);

    // Collections management
    Route::get('/collections', [CollectionController::class, 'getCollections']);
    Route::get('/collections/{collectionName}/cards', [CollectionController::class, 'getCollectionCards']);
    Route::get('/collections/{collectionName}/teams', [CollectionController::class, 'getCollectionTeams']);
    Route::get('/collections/{collectionName}/teams/{team}', [CollectionController::class, 'getTeamCards']);
    Route::post('/cards/{cardId}/lock', [CollectionController::class, 'lockCard']);
    Route::post('/cards/{cardId}/unlock', [CollectionController::class, 'unlockCard']);

    // Lineup management
    Route::get('/lineups', [LineupController::class, 'index']);
    Route::post('/lineups', [LineupController::class, 'store']);
    Route::get('/lineups/{id}', [LineupController::class, 'show']);
    Route::put('/lineups/{id}', [LineupController::class, 'update']);
    Route::delete('/lineups/{id}', [LineupController::class, 'destroy']);
    Route::post('/lineups/{lineupId}/slots', [LineupController::class, 'updateSlot']);

    // Stats tracking
    Route::post('/stats/upload-screenshot', [StatsController::class, 'uploadScreenshot']);
    Route::post('/stats/confirm', [StatsController::class, 'confirmStats']);
    Route::get('/stats/card/{userCardId}', [StatsController::class, 'getCardStats']);

    // Programs
    Route::get('/programs', [ProgramController::class, 'index']);
    Route::get('/programs/{id}', [ProgramController::class, 'show']);
    Route::get('/programs/progress/user', [ProgramController::class, 'getUserProgress']);
    Route::post('/programs/rewards/{rewardId}/claim', [ProgramController::class, 'claimReward']);

    // Market / Card Trading
    Route::post('/cards/sell', [MarketController::class, 'sell']);
    Route::post('/cards/buy', [MarketController::class, 'buy']);
    Route::get('/cards/{playerId}/price', [MarketController::class, 'getPrice']);
});

// Admin routes
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Bulk operations (must be before apiResource to avoid route conflicts)
    Route::post('players/bulk-update', [AdminPlayerController::class, 'bulkUpdate']);

    // Card art images
    Route::get('card-art-images', [AdminPlayerController::class, 'getCardArtImages']);

    // Resource routes
    Route::apiResource('players', AdminPlayerController::class);
    Route::apiResource('programs', AdminProgramController::class);
    Route::apiResource('packs', AdminPackController::class);
    Route::apiResource('collections', AdminCollectionController::class);

    // Collection rewards management
    Route::get('collections/{collectionId}/rewards', [AdminCollectionRewardController::class, 'index']);
    Route::post('collections/{collectionId}/rewards', [AdminCollectionRewardController::class, 'store']);
    Route::get('rewards/{rewardId}', [AdminCollectionRewardController::class, 'show']);
    Route::put('rewards/{rewardId}', [AdminCollectionRewardController::class, 'update']);
    Route::delete('rewards/{rewardId}', [AdminCollectionRewardController::class, 'destroy']);

    // Team Affinity management
    Route::get('team-affinity', [AdminTeamAffinityController::class, 'index']);
    Route::post('team-affinity/create-all', [AdminTeamAffinityController::class, 'createAll']);
    Route::post('team-affinity/bulk-rewards', [AdminTeamAffinityController::class, 'bulkAddRewards']);
    Route::post('team-affinity/bulk-challenges', [AdminTeamAffinityController::class, 'bulkAddChallenges']);
    Route::patch('team-affinity/{programId}/rewards/{rewardId}', [AdminTeamAffinityController::class, 'updateReward']);

    // Unlock pending rewards
    Route::post('program-rewards/{rewardId}/unlock', [AdminProgramController::class, 'unlockReward']);
});
