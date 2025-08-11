<?php

namespace App\Services;

use App\Exceptions\DocumentParseException;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use Psr\Log\LoggerInterface;
use Smalot\PdfParser\Parser as PdfParser;
use Spatie\PdfToText\Pdf;

class DocumentParser
{
    public function __construct(private LoggerInterface $logger) {}

    public function parse(string $disk, string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $fullPath = Storage::disk($disk)->path($path);

        try {
            return match ($ext) {
                'pdf' => $this->parsePdf($fullPath),
                'doc', 'docx' => $this->parseWord($fullPath),
                // Support parsing of PowerPoint presentations. We reuse the
                // PhpPresentation library that is already pulled in via
                // composer. This allows users to upload `.ppt` and
                // `.pptx` files and still get meaningful text content
                // extracted from their slides. Without this case the
                // DocumentParser would silently return an empty string and
                // users could be confused why nothing happens. See
                // \PhpOffice\PhpPresentation\IOFactory for details.
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

    protected function parsePdf(string $fullPath): string
    {
        $binary = config('services.pdftotext_binary');
        $firstException = null;
        if ($binary && is_executable($binary)) {
            $this->logger->info('Parsing PDF using Spatie\\PdfToText', [
                'path' => $fullPath,
                'binary' => $binary,
            ]);

            try {
                return Pdf::fromPath($fullPath)
                    ->setPdfBinary($binary)
                    ->text();
            } catch (\Throwable $e) {
                // Keep the first exception so we can include it in the
                // fallback error context below. We cannot reference $e
                // outside of this catch block otherwise. See
                // https://php.watch/versions/8.0/catch-variable-scope
                $firstException = $e;
                $this->logger->warning('Spatie\\PdfToText failed, falling back to Smalot\\PdfParser', [
                    'path' => $fullPath,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->logger->info('Parsing PDF using Smalot\\PdfParser', [
            'path' => $fullPath,
        ]);

        try {
            $parser = new PdfParser;
            return $parser->parseFile($fullPath)->getText();
        } catch (\Throwable $fallbackException) {
            $context = [
                'path' => $fullPath,
                'smalot_error' => $fallbackException->getMessage(),
            ];

            // Include the first exception's message if Spatie failed above
            if ($firstException) {
                $context['spatie_error'] = $firstException->getMessage();
            }

            $this->logger->error('PDF parsing failed', $context);

            return '';
        }
    }

    /**
     * Extract plain text from a PowerPoint file using PhpPresentation.
     *
     * When reading .ppt and .pptx files we load the presentation via
     * PhpPresentation and iterate over all slides and shapes, pulling
     * out any rich text elements. This provides a best-effort extraction
     * of visible text so that uploaded presentations can be summarised
     * or converted into other outputs. If parsing fails for any reason
     * we log the error and return an empty string.
     */
    protected function parsePowerPoint(string $fullPath): string
    {
        try {
            $reader = \PhpOffice\PhpPresentation\IOFactory::createReader('PowerPoint2007');
            $presentation = $reader->load($fullPath);
            $text = '';

            foreach ($presentation->getAllSlides() as $slide) {
                foreach ($slide->getShapeCollection() as $shape) {
                    if ($shape instanceof \PhpOffice\PhpPresentation\Shape\RichText) {
                        foreach ($shape->getRichTextElements() as $element) {
                            if ($element instanceof \PhpOffice\PhpPresentation\Shape\RichText\TextElement) {
                                $text .= $element->getText() . "\n";
                            }
                        }
                    }
                }
            }

            return trim($text);
        } catch (\Throwable $e) {
            // Log the error but do not expose details to the end user
            $this->logger->error('PowerPoint parsing failed', [
                'path' => $fullPath,
                'error' => $e->getMessage(),
            ]);
            return '';
        }
    }
}
