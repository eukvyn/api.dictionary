<?php

namespace App\Http\Controllers;

use App\Models\Word;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FavoriteController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/entries/en/{word}/favorite",
     *     summary="Add a word to favorites",
     *     description="Save the specified word to the user's list of favorites",
     *     operationId="addFavorite",
     *     tags={"Favorites"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="word",
     *         in="path",
     *         description="The word to add to favorites",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Word added to favorites",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Word favorited successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Word not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Word not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Word already in favorites",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Word is already in favorites")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Server Error")
     *         )
     *     )
     * )
     */
    public function store($word, Request $request)
    {
        $user = $request->user();

        // Generate a unique cache key based on word
        $cacheKey = 'word_detail:' . strtolower($word);

        if ($user->favorites()->where('word_id', function ($query) use ($word) {
            $query->select('id')->from('words')->where('word', $word)->limit(1);
        })->exists()) {
            return response()->json(['message' => 'Word is already in favorites'], 409);
        }

        $wordModel = Word::where('word', $word)->first();

        if (!$wordModel) {
            // If the word does not exist in the database, search in the external API
            try {
                $externalResponse = Http::timeout(5)->get("https://api.dictionaryapi.dev/api/v2/entries/en/{$word}");

                if ($externalResponse->successful()) {
                    $data = $externalResponse->json();

                    if (empty($data)) {
                        return response()->json(['message' => 'Word not found'], 404);
                    }

                    $wordModel = Word::create(['word' => $word]);

                    Cache::tags(['words'])->put($cacheKey, $data, now()->addMinutes(60));
                } else if ($externalResponse->status() == 404) {
                    return response()->json(['message' => 'Word not found'], 404);
                } else {
                    return response()->json(['message' => 'Error fetching word details'], $externalResponse->status());
                }
            } catch (\Exception $e) {
                Log::error("Error fetching word details: " . $e->getMessage());
                return response()->json(['message' => 'Server Error'], 500);
            }
        }

        // Add the word to the user's favorites
        $user->favorites()->attach($wordModel->id);

        // Invalidates the cache related to the user's favorite words
        Cache::tags(['favorites:' . $user->id])->flush();

        return response()->json(['message' => 'Word favorited successfully'], 201);
    }

    /**
     * @OA\Delete(
     *     path="/api/entries/en/{word}/unfavorite",
     *     summary="Remove a word from favorites",
     *     description="Remove the specified word from the user's list of favorites",
     *     operationId="removeFavorite",
     *     tags={"Favorites"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="word",
     *         in="path",
     *         description="The word to remove from favorites",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Word removed from favorites",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Word removed from favorites successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Word not found in favorites",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Word not found in favorites")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Server Error")
     *         )
     *     )
     * )
     */
    public function destroy($word, Request $request)
    {
        $user = $request->user();

        $wordModel = Word::where('word', $word)->first();

        if (!$wordModel) {
            return response()->json(['message' => 'Word not found'], 404);
        }

        if (!$user->favorites()->where('word_id', $wordModel->id)->exists()) {
            return response()->json(['message' => 'Word not found in favorites'], 404);
        }

        // Remove word from favorites
        $user->favorites()->detach($wordModel->id);

        // Invalidates the cache related to the user's favorite words
        Cache::tags(['favorites:' . $user->id])->flush();

        return response()->json(['message' => 'Word removed from favorites successfully'], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/user/me/favorites",
     *     summary="List favorite words",
     *     description="Retrieve the list of words favorited by the authenticated user",
     *     operationId="listFavorites",
     *     tags={"Favorites"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No favorites found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No favorites found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Server Error")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Generate a unique cache key based on user
        $cacheKey = 'favorites:' . $user->id;

        // Start timer
        $startTime = microtime(true);

        // Attempt to retrieve from cache
        if (Cache::tags(['favorites:' . $user->id])->has($cacheKey)) {
            $cachedData = Cache::tags(['favorites:' . $user->id])->get($cacheKey);
            $responseTime = round((microtime(true) - $startTime) * 1000, 2); // in ms

            return response()->json($cachedData)
                ->header('x-cache', 'HIT')
                ->header('x-response-time', "{$responseTime}ms");
        }

        // Search for user's favorite words
        $favorites = $user->favorites()->pluck('word');

        if ($favorites->isEmpty()) {
            return response()->json(['message' => 'No favorites found'], 404);
        }

        $responseData = $favorites->toArray();

        // Save to cache for 60 minutes
        Cache::tags(['favorites:' . $user->id])->put($cacheKey, $responseData, now()->addMinutes(60));

        $responseTime = round((microtime(true) - $startTime) * 1000, 2); // in ms

        return response()->json($responseData)
            ->header('x-cache', 'MISS')
            ->header('x-response-time', "{$responseTime}ms");
    }
}
