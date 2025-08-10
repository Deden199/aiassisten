<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use Spatie\PdfToText\Pdf;

class DocumentParser
{
    public function parse(string $disk, string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $fullPath = Storage::disk($disk)->path($path);

        try {
            return match ($ext) {
                'pdf' => Pdf::getText($fullPath),
                'doc', 'docx' => $this->parseWord($fullPath),
                'txt' => (string) Storage::disk($disk)->get($path),
                default => '',
            };
        } catch (\Throwable $e) {
            return '';
        }
    }

    protected function parseWord(string $fullPath): string
    {
        $phpWord = IOFactory::load($fullPath);
        $text = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text .= $element->getText()."\n";
                }
            }
        }

        return trim($text);
    }
}
