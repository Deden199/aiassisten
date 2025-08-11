<?php

namespace App\Services;

use App\Exceptions\DocumentParseException;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpPresentation\IOFactory as PptIOFactory;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
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
                'ppt', 'pptx' => $this->parsePowerPoint($fullPath),
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
        $phpWord = WordIOFactory::load($fullPath);
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

    protected function parsePowerPoint(string $fullPath): string
    {
        $presentation = PptIOFactory::load($fullPath);
        $text = '';

        foreach ($presentation->getAllSlides() as $slide) {
            foreach ($slide->getShapeCollection() as $shape) {
                if (method_exists($shape, 'getParagraphs')) {
                    foreach ($shape->getParagraphs() as $paragraph) {
                        foreach ($paragraph->getRichTextElements() as $element) {
                            $content = trim($element->getText());
                            if ($content !== '') {
                                $text .= $content . "\n";
                            }
                        }
                    }
                } elseif (method_exists($shape, 'getText')) {
                    $content = trim($shape->getText());
                    if ($content !== '') {
                        $text .= $content . "\n";
                    }
                }
            }

            $notes = $slide->getNote();
            if ($notes) {
                foreach ($notes->getShapeCollection() as $noteShape) {
                    if (method_exists($noteShape, 'getParagraphs')) {
                        foreach ($noteShape->getParagraphs() as $paragraph) {
                            foreach ($paragraph->getRichTextElements() as $element) {
                                $content = trim($element->getText());
                                if ($content !== '') {
                                    $text .= $content . "\n";
                                }
                            }
                        }
                    } elseif (method_exists($noteShape, 'getText')) {
                        $content = trim($noteShape->getText());
                        if ($content !== '') {
                            $text .= $content . "\n";
                        }
                    }
                }
            }
        }

        return trim($text);
    }
}
