<?php

namespace Tests\Unit;

use App\Services\DocumentParser;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class DocumentParserTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_uses_spatie_parser_when_binary_is_available(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('file.pdf', 'dummy');

        $binaryPath = sys_get_temp_dir().'/pdftotext';
        file_put_contents($binaryPath, '#!/bin/sh\nexit 0');
        chmod($binaryPath, 0755);

        config(['services.pdftotext_binary' => $binaryPath]);

        $pdfMock = Mockery::mock();
        $pdfMock->shouldReceive('setPdfBinary')->with($binaryPath)->andReturnSelf();
        $pdfMock->shouldReceive('text')->andReturn('spatie text');

        Mockery::mock('alias:Spatie\\PdfToText\\Pdf')
            ->shouldReceive('fromPath')->andReturn($pdfMock);

        Mockery::mock('overload:Smalot\\PdfParser\\Parser')
            ->shouldNotReceive('parseFile');

        $logger = Mockery::spy(LoggerInterface::class);
        $parser = new DocumentParser($logger);

        $result = $parser->parse('local', 'file.pdf');

        $this->assertSame('spatie text', $result);
        $logger->shouldHaveReceived('info')->with('Parsing PDF using Spatie\\PdfToText', Mockery::type('array'));
    }

    public function test_uses_smalot_parser_when_binary_is_missing(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('file.pdf', 'dummy');

        config(['services.pdftotext_binary' => '/path/to/missing']);

        Mockery::mock('alias:Spatie\\PdfToText\\Pdf')
            ->shouldNotReceive('fromPath');

        $parserResult = Mockery::mock();
        $parserResult->shouldReceive('getText')->andReturn('smalot text');

        Mockery::mock('overload:Smalot\\PdfParser\\Parser')
            ->shouldReceive('parseFile')->andReturn($parserResult);

        $logger = Mockery::spy(LoggerInterface::class);
        $parser = new DocumentParser($logger);

        $result = $parser->parse('local', 'file.pdf');

        $this->assertSame('smalot text', $result);
        $logger->shouldHaveReceived('info')->with('Parsing PDF using Smalot\\PdfParser', Mockery::type('array'));
    }
}

