<?php

namespace App\Services;

use App\Models\AiTaskVersion;
use App\Models\SlideTemplate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\Slide\Background\Color as BgColor;
use PhpOffice\PhpPresentation\Slide\Background\Gradient as BgGradient;
use PhpOffice\PhpPresentation\Slide\Background\Image as BgImage;
use PhpOffice\PhpPresentation\Style\Alignment;
use PhpOffice\PhpPresentation\Style\Bullet;
use PhpOffice\PhpPresentation\Style\Color;

class PptxExporter
{
    public function export(AiTaskVersion $version): void
    {
        $payload = $version->payload ?? [];
        $theme = $payload['theme'] ?? SlideTemplate::defaultTheme();
        $slides = $payload['slides'] ?? $payload;
        $presentation = new PhpPresentation();
        $tempFiles = [];

        foreach ($slides as $index => $data) {
            $slide = $index === 0 ? $presentation->getActiveSlide() : $presentation->createSlide();

            $bg = $data['background'] ?? $theme['background_default'] ?? [];
            $this->applyBackground($slide, $bg, $tempFiles);

            $titleLayout = $theme['layout']['title'] ?? ['x'=>30,'y'=>30,'w'=>900,'h'=>80,'align'=>'left'];
            $titleShape = $slide->createRichTextShape();
            $titleShape->setWidth($titleLayout['w']);
            $titleShape->setHeight($titleLayout['h']);
            $titleShape->setOffsetX($titleLayout['x']);
            $titleShape->setOffsetY($titleLayout['y']);
            $align = $titleLayout['align'] ?? 'left';
            $alignment = Alignment::HORIZONTAL_LEFT;
            if ($align === 'center') $alignment = Alignment::HORIZONTAL_CENTER;
            if ($align === 'right') $alignment = Alignment::HORIZONTAL_RIGHT;
            $titleShape->getActiveParagraph()->getAlignment()->setHorizontal($alignment);

            $title = is_string($data['title'] ?? null) ? $data['title'] : 'Slide ' . ($index + 1);
            $run = $titleShape->createTextRun($this->sanitize($title));
            $titleFont = $run->getFont();
            if ($family = $theme['font']['family'] ?? null) {
                $titleFont->setName($family);
            }
            $titleFont->setSize($theme['font']['title_size'] ?? 24);
            $titleFont->setBold(($theme['font']['title_weight'] ?? 'bold') === 'bold');
            $titleColor = $data['colors']['title'] ?? $theme['palette']['primary'] ?? '#000000';
            $titleFont->setColor(new Color($this->hexToArgb($titleColor)));

            $bulletLayout = $theme['layout']['bullets'] ?? ['x'=>40,'y'=>130,'w'=>900,'h'=>400];
            $bulletShape = $slide->createRichTextShape();
            $bulletShape->setWidth($bulletLayout['w']);
            $bulletShape->setHeight($bulletLayout['h']);
            $bulletShape->setOffsetX($bulletLayout['x']);
            $bulletShape->setOffsetY($bulletLayout['y']);

            $bullets = $data['bullets'] ?? [];
            if (is_string($bullets)) {
                $bullets = preg_split("/\r?\n/", $bullets);
            }
            $bullets = array_values(array_filter(array_map(function ($bullet) {
                return is_string($bullet) ? $this->sanitize($bullet) : null;
            }, is_array($bullets) ? $bullets : []), function ($bullet) {
                return $bullet !== null && $bullet !== '';
            }));

            foreach ($bullets as $bullet) {
                $paragraph = $bulletShape->createParagraph();
                $paragraph->setBulletStyle(new Bullet());
                $paragraph->getAlignment()->setMarginLeft($bulletLayout['indent'] ?? 0);
                if ($ls = $bulletLayout['line_spacing'] ?? null) {
                    $paragraph->getAlignment()->setLineSpacing((int) ($ls * 100));
                }
                $run = $paragraph->createTextRun($bullet);
                $font = $run->getFont();
                if ($family = $theme['font']['family'] ?? null) {
                    $font->setName($family);
                }
                $font->setSize($theme['font']['body_size'] ?? 18);
                $font->setBold(($theme['font']['body_weight'] ?? 'normal') === 'bold');
                $color = $data['colors']['bullets'] ?? $theme['palette']['secondary'] ?? '#000000';
                $font->setColor(new Color($this->hexToArgb($color)));
            }

            $notes = $data['notes'] ?? null;
            if ($notes) {
                $lines = is_array($notes) ? $notes : preg_split("/\r?\n/", $notes);
                $noteShape = $slide->getNote()->createRichTextShape();
                foreach ($lines as $line) {
                    $text = is_string($line) ? $this->sanitize($line) : null;
                    if ($text === null || $text === '') {
                        continue;
                    }
                    $noteShape->createTextRun($text);
                    $noteShape->createBreak();
                }
            }
        }

        foreach ($tempFiles as $tmp) {
            @unlink($tmp);
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

    private function sanitize(string $text): string
    {
        return trim(strip_tags($text));
    }

    private function hexToArgb(string $hex): string
    {
        $hex = ltrim($hex, '#');
        return 'FF' . strtoupper($hex);
    }

    private function applyBackground($slide, array $bg, array &$tempFiles): void
    {
        $type = $bg['type'] ?? 'solid';
        if ($type === 'image' && !empty($bg['image_url'])) {
            $path = $this->downloadImage($bg['image_url']);
            if ($path) {
                $background = new BgImage();
                $background->setPath($path);
                $slide->setBackground($background);
                $tempFiles[] = $path;
                return;
            }
            $type = 'solid';
        }

        if ($type === 'gradient' && isset($bg['gradient']['from'], $bg['gradient']['to'])) {
            $background = new BgGradient();
            $background->setStartColor(new Color($this->hexToArgb($bg['gradient']['from'])));
            $background->setEndColor(new Color($this->hexToArgb($bg['gradient']['to'])));
            $slide->setBackground($background);
            return;
        }

        $color = $bg['color'] ?? ($bg['background'] ?? '#FFFFFF');
        $background = new BgColor();
        $background->setColor(new Color($this->hexToArgb($color)));
        $slide->setBackground($background);
    }

    private function downloadImage(string $url): ?string
    {
        try {
            $response = Http::timeout(10)->get($url);
            if ($response->successful()) {
                $path = storage_path('app/tmp/'.Str::uuid()->toString());
                if (!is_dir(dirname($path))) {
                    mkdir(dirname($path), 0777, true);
                }
                file_put_contents($path, $response->body());
                return $path;
            }
        } catch (\Throwable $e) {
        }
        return null;
    }
}
