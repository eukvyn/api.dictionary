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
        // Buscar a palavra no banco de dados
        $wordModel = Word::where('word', $word)->first();

        if (!$wordModel) {
            return response()->json(['message' => 'Word not found'], 404);
        }

        // Verificar se a palavra já está no histórico do usuário
        $existingHistory = $user->histories()->where('word_id', $wordModel->id)->first();

        if ($existingHistory) {
            // Se a palavra já está no histórico, apenas atualiza o timestamp
            $existingHistory->pivot->touch();  // Atualiza a coluna 'updated_at'
        } else {
            // Caso contrário, adiciona a palavra ao histórico
            $user->histories()->attach($wordModel->id);
        }

        Cache::tags(["user_history:{$user->id}"])->flush();
    }


    /**
     * @OA\Get(
     *     path="/api/user/me/history",
     *     summary="Retorna a lista de palavras visitadas pelo usuário com paginação por cursores",
     *     tags={"History"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Número de palavras a retornar por página",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Parameter(
     *         name="cursor",
     *         in="query",
     *         description="Cursor para a páginação",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de palavras com informações de acesso",
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
     *         description="Usuário não autenticado"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $limit = $request->query('limit', 10);
        $cursor = $request->query('cursor');

        $cacheKey = "user_history:{$user->id}_{$cursor}_{$limit}";

        // Start timer
        $startTime = microtime(true);

        if (Cache::tags(["user_history:{$user->id}"])->has($cacheKey)) {
            $cachedData = Cache::tags(["user_history:{$user->id}"])->get($cacheKey);
            $responseTime = round((microtime(true) - $startTime) * 1000, 2); // in ms
            return response()->json($cachedData)
                ->header('x-cache', 'HIT')
                ->header('x-response-time', "{$responseTime}ms");
        }

        // Ordenar o histórico pelo campo 'updated_at' na tabela de pivot
        $query = $user->histories()->orderBy('pivot_updated_at', 'desc');

        // Usar paginação com cursor
        $words = $query->cursorPaginate($limit, ['*'], 'cursor', $cursor);

        // Transformar o array em uma coleção antes de aplicar o 'map'
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
