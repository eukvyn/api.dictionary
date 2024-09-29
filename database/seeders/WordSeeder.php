<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\WordsImport;

class WordSeeder extends Seeder
{
    public function run()
    {
        $txtFileUrl = 'https://raw.githubusercontent.com/meetDeveloper/freeDictionaryAPI/master/meta/wordList/english.txt';
        $txtFilePath = storage_path('app/wordlist.txt');
        $csvFilePath = storage_path('app/wordlist.csv');

        $this->downloadTxtFile($txtFileUrl, $txtFilePath);

        $this->convertTxtToCsv($txtFilePath, $csvFilePath);

        Excel::import(new WordsImport, $csvFilePath);
    }

    protected function downloadTxtFile($url, $destinationPath)
    {
        $txtFileContent = file_get_contents($url);

        if ($txtFileContent === false) {
            throw new \Exception("Erro ao baixar o arquivo da URL: $url");
        }

        file_put_contents($destinationPath, $txtFileContent);
    }

    protected function convertTxtToCsv($txtFilePath, $csvFilePath)
    {
        $txtFile = fopen($txtFilePath, 'r');
        $csvFile = fopen($csvFilePath, 'w');

        while (($line = fgets($txtFile)) !== false) {
            fputcsv($csvFile, [trim($line)]);
        }

        fclose($txtFile);
        fclose($csvFile);
    }
}
