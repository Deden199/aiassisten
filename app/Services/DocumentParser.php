<?php

namespace App\Services;

use App\Exceptions\DocumentParseException;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use Psr\Log\LoggerInterface;
use Spatie\PdfToText\Pdf;

class DocumentParser
{
    public function __construct(private LoggerInterface $logger)
    {
    }

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
            $this->logger->error('Document parsing failed', [
                'path' => $fullPath,
                'message' => $e->getMessage(),
            ]);

            throw new DocumentParseException("Failed to parse document at {$path}", 0, $e);
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
