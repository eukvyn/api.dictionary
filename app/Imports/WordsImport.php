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
        // Formatar os dados para inserção
        $data = [];
        foreach ($rows as $row) {
            $data[] = ['word' => $row[0]];
        }

        // Inserir ignorando duplicatas
        Word::insertOrIgnore($data);
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

