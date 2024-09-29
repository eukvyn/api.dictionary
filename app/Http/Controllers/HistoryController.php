<?php

namespace App\Http\Controllers;

use App\Models\History;
use App\Models\Word;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HistoryController extends Controller
{
    public static function addToHistory($user, $word)
    {
        $wordModel = Word::where('word', $word)->first();

        if (!$wordModel) {
            return response()->json(['message' => 'Word not found'], 404);
        }

        // Check if the word is already in the user's history
        $existingHistory = $user->histories()->where('word_id', $wordModel->id)->first();

        if ($existingHistory) {
            // If the word is already in the history, just update the timestamp
            $existingHistory->pivot->touch();  // Updates the 'updated_at' column
        } else {
            // Otherwise, add the word to the history.
            $user->histories()->attach($wordModel->id);
        }

        Cache::tags(["user_history:{$user->id}"])->flush();
    }


    /**
     * @OA\Get(
     *     path="/api/user/me/history",
     *     summary="Returns the list of words visited by the user with cursor pagination",
     *     tags={"History"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of words to return per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Parameter(
     *         name="cursor",
     *         in="query",
     *         description="Cursor for paging",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of words with access information",
     *         @OA\JsonContent(
     *             @OA\Property(property="results", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="word", type="string", example="fire"),
     *                     @OA\Property(property="added", type="string", example="2022-05-05T19:28:13.531Z")
     *                 )
     *             ),
     *             @OA\Property(property="totalDocs", type="integer", example=20),
     *             @OA\Property(property="next", type="string", example="http://api.example.com/user/me/history?cursor=eyJpdiI6Im..."),
     *             @OA\Property(property="previous", type="string", example="http://api.example.com/user/me/history?cursor=eyJpdiI6Im..."),
     *             @OA\Property(property="hasNext", type="boolean", example=true),
     *             @OA\Property(property="hasPrev", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated user"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $limit = $request->query('limit', 10);
        $cursor = $request->query('cursor');

        // Generate a unique cache key based on user, limit, and cursor
        $cacheKey = "user_history:{$user->id}_{$cursor}_{$limit}";

        // Start timer
        $startTime = microtime(true);

        // Attempt to retrieve from cache
        if (Cache::tags(["user_history:{$user->id}"])->has($cacheKey)) {
            $cachedData = Cache::tags(["user_history:{$user->id}"])->get($cacheKey);
            $responseTime = round((microtime(true) - $startTime) * 1000, 2); // in ms
            return response()->json($cachedData)
                ->header('x-cache', 'HIT')
                ->header('x-response-time', "{$responseTime}ms");
        }

        // Sort history by 'updated_at' field
        $query = $user->histories()->orderBy('pivot_updated_at', 'desc');

        $words = $query->cursorPaginate($limit, ['*'], 'cursor', $cursor);

        $responseData = [
            'results' => collect($words->items())->map(function ($word) {
                return [
                    'word' => $word->word,
                    'added' => $word->pivot->updated_at,
                ];
            }),
            'totalDocs' => $query->count(),
            'next' => $words->nextPageUrl(),
            'previous' => $words->previousPageUrl(),
            'hasNext' => $words->hasMorePages(),
            'hasPrev' => $words->previousPageUrl() !== null,
        ];

        Cache::tags(["user_history:{$user->id}"])->put($cacheKey, $responseData, now()->addMinutes(60));

        $responseTime = round((microtime(true) - $startTime) * 1000, 2); // in ms

        return response()->json($responseData)
            ->header('x-cache', 'MISS')
            ->header('x-response-time', "{$responseTime}ms");
    }
}
