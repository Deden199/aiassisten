<?php

namespace Tests\Unit;

use App\Services\DocumentParser;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpPresentation\IOFactory as PptIOFactory;
use PhpOffice\PhpPresentation\PhpPresentation;
use Psr\Log\NullLogger;
use Tests\TestCase;

class DocumentParserTest extends TestCase
{
    public function test_parses_pptx_file(): void
    {
        Storage::fake('local');

        $presentation = new PhpPresentation();
        $slide = $presentation->getActiveSlide();
        $shape = $slide->createRichTextShape();
        $shape->createTextRun('Hello PPTX');

        $path = 'sample.pptx';
        $writer = PptIOFactory::createWriter($presentation, 'PowerPoint2007');
        $writer->save(Storage::disk('local')->path($path));

        $parser = new DocumentParser(new NullLogger());
        $text = $parser->parse('local', $path);

        $this->assertSame('Hello PPTX', $text);
    }

    public function test_returns_empty_string_for_unsupported_format(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('file.xyz', 'data');

        $parser = new DocumentParser(new NullLogger());
        $text = $parser->parse('local', 'file.xyz');

        $this->assertSame('', $text);
    }
}
