<?php

namespace Tests\Feature;

use App\Models\{AiProject, Tenant, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpPresentation\IOFactory as PptIOFactory;
use PhpOffice\PhpPresentation\PhpPresentation;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_rejects_unsupported_file(): void
    {
        Storage::fake('private');

        $tenant = Tenant::create([
            'id'   => (string) Str::uuid(),
            'name' => 'T',
            'slug' => 't',
        ]);

        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $file = UploadedFile::fake()->create('test.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($user)->post('/projects', [
            'title' => 'Test',
            'file'  => $file,
        ]);

        $response->assertSessionHasErrors('file');
    }

    public function test_store_accepts_pptx_and_parses_text(): void
    {
        Storage::fake('private');

        $tenant = Tenant::create([
            'id'   => (string) Str::uuid(),
            'name' => 'T',
            'slug' => 't',
        ]);

        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $presentation = new PhpPresentation();
        $slide = $presentation->getActiveSlide();
        $shape = $slide->createRichTextShape();
        $shape->createTextRun('Hello PPTX');

        $temp = tempnam(sys_get_temp_dir(), 'ppt');
        $writer = PptIOFactory::createWriter($presentation, 'PowerPoint2007');
        $writer->save($temp);
        $upload = new UploadedFile($temp, 'sample.pptx', null, null, true);

        $response = $this->actingAs($user)->post('/projects', [
            'title' => 'My PPT',
            'file'  => $upload,
        ]);

        $response->assertSessionHasNoErrors();

        $project = AiProject::first();
        $this->assertSame('Hello PPTX', $project->source_text);
    }
}
