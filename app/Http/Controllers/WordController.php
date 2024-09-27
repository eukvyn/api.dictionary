<?php

namespace App\Http\Controllers;

use App\Models\Word;
use Illuminate\Http\Request;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="Dictionary API Documentation",
 *      description="API to search words in a dictionary",
 *      @OA\Contact(
 *          email="support@example.com"
 *      )
 * )
 *
 * @OA\Tag(
 *     name="Words",
 *     description="Operations related to word search"
 * )
 */
class WordController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/entries/en",
     *     summary="Retrieve words",
     *     description="Get a list of words with optional search query and pagination",
     *     operationId="getWords",
     *     tags={"Words"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search for words that start with this string",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Limit the number of results",
     *         required=false,
     *         @OA\Schema(type="integer", default=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="results", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="totalDocs", type="integer"),
     *             @OA\Property(property="next", type="string"),
     *             @OA\Property(property="previous", type="string"),
     *             @OA\Property(property="hasNext", type="boolean"),
     *             @OA\Property(property="hasPrev", type="boolean"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $limit = $request->query('limit', 100);

        $query = Word::query();

        if ($search) {
            $query = Word::where('word', 'LIKE', $search . '%');
        }

        $words = $query->cursorPaginate($limit);

        return response()->json([
            'results' => $words->items(),
            'totalDocs' => $query->count(),
            'next' => $words->nextPageUrl(),
            'previous' => $words->previousPageUrl(),
            'hasNext' => $words->hasMorePages(),
            'hasPrev' => $words->previousPageUrl() !== null
        ]);
    }
}
