<?php

namespace App\Services;

use App\Models\AiTaskVersion;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\Style\Alignment;
use PhpOffice\PhpPresentation\Style\Bullet;

class PptxExporter
{
    public function export(AiTaskVersion $version): void
    {
        $slides = $version->payload ?? [];
        $presentation = new PhpPresentation();

        foreach ($slides as $index => $data) {
            $slide = $index === 0 ? $presentation->getActiveSlide() : $presentation->createSlide();
            $shape = $slide->createRichTextShape();
            $shape->setWidth(960);
            $shape->setHeight(540);
            $shape->setOffsetX(0);
            $shape->setOffsetY(0);
            $shape->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            $title = is_string($data['title'] ?? null) ? $data['title'] : 'Slide ' . ($index + 1);
            $shape->createTextRun($title)->getFont()->setBold(true)->setSize(24);
            $shape->createBreak();

            $bullets = $data['bullets'] ?? $data['bullet_points'] ?? [];
            if (is_string($bullets)) {
                $bullets = preg_split("/\r?\n/", $bullets);
            }
            foreach ($bullets as $bullet) {
                if (trim($bullet) === '') {
                    continue;
                }
                $paragraph = $shape->createParagraph();
                $paragraph->setBulletStyle(new Bullet());
                $paragraph->createTextRun($bullet)->getFont()->setSize(18);
            }

            $notes = $data['notes'] ?? $data['speaker_notes'] ?? null;
            if ($notes) {
                $lines = is_array($notes) ? $notes : preg_split("/\r?\n/", $notes);
                $noteShape = $slide->getNote()->createRichTextShape();
                foreach ($lines as $line) {
                    $noteShape->createTextRun($line);
                    $noteShape->createBreak();
                }
            }
        }

        $disk = 'private';
        $path = 'slides/' . Str::uuid() . '.pptx';
        Storage::disk($disk)->makeDirectory('slides');

        $writer = IOFactory::createWriter($presentation, 'PowerPoint2007');
        $writer->save(Storage::disk($disk)->path($path));

        $version->update([
            'file_disk' => $disk,
            'file_path' => $path,
        ]);
    }
}
