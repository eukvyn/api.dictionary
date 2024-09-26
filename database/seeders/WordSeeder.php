<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\WordsImport;

class WordSeeder extends Seeder
{
    public function run()
    {
        $txtFileUrl = 'https://raw.githubusercontent.com/meetDeveloper/freeDictionaryAPI/master/meta/wordList/english.txt'; // URL do arquivo
        $txtFilePath = storage_path('app/wordlist.txt');
        $csvFilePath = storage_path('app/wordlist.csv'); // Caminho do diretório permitido

        // Baixar o arquivo .txt
        $this->downloadTxtFile($txtFileUrl, $txtFilePath);

        // Converter o arquivo .txt para .csv
        $this->convertTxtToCsv($txtFilePath, $csvFilePath);

        // Importar o arquivo .csv para o banco de dados
        Excel::import(new WordsImport, $csvFilePath);
    }

    // Função para baixar o arquivo .txt da URL
    protected function downloadTxtFile($url, $destinationPath)
    {
        $txtFileContent = file_get_contents($url); // Baixa o conteúdo do arquivo da URL

        if ($txtFileContent === false) {
            throw new \Exception("Erro ao baixar o arquivo da URL: $url");
        }

        // Salva o conteúdo do arquivo no destino desejado
        file_put_contents($destinationPath, $txtFileContent);
    }

    // Função para converter o .txt para .csv
    protected function convertTxtToCsv($txtFilePath, $csvFilePath)
    {
        $txtFile = fopen($txtFilePath, 'r');
        $csvFile = fopen($csvFilePath, 'w');

        while (($line = fgets($txtFile)) !== false) {
            fputcsv($csvFile, [trim($line)]); // Remove espaços extras e escreve no CSV
        }

        fclose($txtFile);
        fclose($csvFile);
    }
}
