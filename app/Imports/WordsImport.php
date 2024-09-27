<?php

namespace App\Imports;

use App\Models\Word;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;

class WordsImport implements ToCollection, WithChunkReading, WithBatchInserts
{
    public function collection(Collection $rows)
    {
        $data = [];

        foreach ($rows as $row) {
            // Obtenha a palavra
            $word = trim($row[0]);

            // Verifique se é uma palavra válida: sem espaços, apenas letras (evitar frases)
            if ($this->isValidWord($word)) {
                $data[] = ['word' => $word];
            }
        }

        // Inserir ignorando duplicatas
        Word::insertOrIgnore($data);
    }

    /**
     * Valida se o termo é uma palavra válida.
     */
    private function isValidWord($word)
    {
        // Verifica se a palavra contém apenas letras e não contém espaços
        return preg_match('/^[a-zA-Z\-]+$/', $word);
    }

    public function batchSize(): int
    {
        return 1000; // Ajustar o tamanho do lote de inserção
    }

    public function chunkSize(): int
    {
        return 1000; // Ajustar o tamanho do chunk
    }
}
