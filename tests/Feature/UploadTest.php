<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadTest extends TestCase
{
    public function test_pdf_upload_is_stored_and_returns_url(): void
    {
        Storage::fake('private');

        $file = UploadedFile::fake()->createWithContent('test.pdf', '%PDF-1.4');

        $response = $this->postJson('/api/upload', [
            'file' => $file,
        ]);

        $response->assertOk()->assertJsonStructure(['url', 'path']);

        Storage::disk('private')->assertExists($response->json('path'));
    }

    public function test_invalid_file_type_is_rejected(): void
    {
        $file = UploadedFile::fake()->create('test.jpg', 100, 'image/jpeg');

        $response = $this->postJson('/api/upload', [
            'file' => $file,
        ]);

        $response->assertStatus(422);
    }
}
