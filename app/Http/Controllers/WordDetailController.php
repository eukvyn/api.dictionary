<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Words",
 *     description="Operations related to word search"
 * )
 */
class WordDetailController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/entries/en/{word}",
     *     summary="Retrieve word details",
     *     description="Get detailed information about a specific word by proxying the Free Dictionary API",
     *     operationId="getWordDetails",
     *     tags={"Words"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="word",
     *         in="path",
     *         description="The word to retrieve details for",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="word", type="string"),
     *                 @OA\Property(property="phonetics", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="meanings", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="license", type="object"),
     *                 @OA\Property(property="sourceUrls", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Word not found"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server Error"
     *     )
     * )
     */
    public function show($word, Request $request)
    {
        // Gerar uma chave única para o cache baseada na palavra
        $cacheKey = 'word_detail:' . strtolower($word);

        // Iniciar o cronômetro para medir o tempo de resposta
        $startTime = microtime(true);

        // Verificar se os dados já estão no cache Redis
        if (Cache::has($cacheKey)) {
            $cachedData = Cache::get($cacheKey);
            $responseTime = round((microtime(true) - $startTime) * 1000, 2); // em ms

            return response()->json($cachedData)
                ->header('x-cache', 'HIT')
                ->header('x-response-time', "{$responseTime}ms");
        }

        // Fazer a requisição à API externa
        try {
            $externalResponse = Http::timeout(5)->get("https://api.dictionaryapi.dev/api/v2/entries/en/{$word}");

            if ($externalResponse->successful()) {
                $data = $externalResponse->json();

                // Verificar se a palavra foi encontrada
                if (empty($data)) {
                    return response()->json(['message' => 'Word not found'], 404);
                }

                // Salvar no cache por 60 minutos
                Cache::put($cacheKey, $data, now()->addMinutes(60));

                $responseTime = round((microtime(true) - $startTime) * 1000, 2); // em ms

                return response()->json($data)
                    ->header('x-cache', 'MISS')
                    ->header('x-response-time', "{$responseTime}ms");
            } else if ($externalResponse->status() == 404) {
                return response()->json(['message' => 'Word not found'], 404);
            } else {
                return response()->json(['message' => 'Error fetching word details'], $externalResponse->status());
            }
        } catch (\Exception $e) {
            // Log de erro para diagnóstico
            Log::error("Error fetching word details: " . $e->getMessage());

            return response()->json(['message' => 'Server Error'], 500);
        }
    }
}
