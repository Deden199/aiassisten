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
use PhpOffice\PhpPresentation\Slide\Background\Image as BgImage;
use PhpOffice\PhpPresentation\Style\Fill;
use PhpOffice\PhpPresentation\Style\Alignment;
use PhpOffice\PhpPresentation\Style\Bullet;
use PhpOffice\PhpPresentation\Style\Color;
use PhpOffice\PhpPresentation\Shape\Drawing;
use PhpOffice\PhpPresentation\Shape\RichText;

class PptxExporter
{
    private array $tempFiles = [];

    public function export(AiTaskVersion $version): void
    {
        $rawPayload = $version->payload ?? [];
        if (isset($rawPayload['content']) && is_string($rawPayload['content'])) {
            $decoded = json_decode($rawPayload['content'], true);
            $payload = is_array($decoded) ? $decoded : [];
        } else {
            $payload = $rawPayload;
        }
        $theme = $payload['theme'] ?? SlideTemplate::defaultTheme();
        $slides = $payload['slides'] ?? $payload;
        $presentation = new PhpPresentation();
        $this->tempFiles = [];

        foreach ($slides as $index => $data) {
            $slide = $index === 0 ? $presentation->getActiveSlide() : $presentation->createSlide();

            $bg = $data['background'] ?? null;
            $this->applyBackground($slide, $bg, $theme);

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
            $titleFont->setColor(new Color($this->hexToArgb($titleColor, $theme['palette']['primary'] ?? '#000000')));

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
            $bullets = array_slice($bullets, 0, 6);

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
                $font->setColor(new Color($this->hexToArgb($color, $theme['palette']['secondary'] ?? '#000000')));
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

        foreach ($this->tempFiles as $tmp) {
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

    private function hexToArgb(string $hex, string $fallback = '#111827'): string
    {
        $hex = ltrim($hex, '#');
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            $hex = ltrim($fallback, '#');
            if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
                $hex = '111827';
            }
        }
        return 'FF' . strtoupper($hex);
    }

    private function slideSize(PhpPresentation $presentation): array
    {
        try {
            $layout = $presentation->getLayout();
            if (method_exists($layout, 'getCX') && method_exists($layout, 'getCY')) {
                return [$layout->getCX(), $layout->getCY()];
            }
        } catch (\Throwable $e) {
        }
        return [960, 540];
    }

    private function createGradientLayer(\PhpOffice\PhpPresentation\Slide $slide, string $from, string $to): void
    {
        $presentation = $slide->getParent();
        [$width, $height] = $this->slideSize($presentation);
        $shape = new Drawing\Shape();
        $shape->setWidth($width);
        $shape->setHeight($height);
        $shape->setOffsetX(0);
        $shape->setOffsetY(0);
        $shape->getFill()->setFillType(Fill::FILL_GRADIENT_LINEAR);
        $shape->getFill()->setStartColor(new Color($this->hexToArgb($from)));
        $shape->getFill()->setEndColor(new Color($this->hexToArgb($to)));
        $slide->addShape($shape);
    }

    private function applyBackground($slide, ?array $bg = null, ?array $theme = null): void
    {
        $bg = $bg ?? [];
        $type = $bg['type'] ?? 'solid';

        if ($type === 'image' && !empty($bg['image_url'])) {
            if ($path = $this->downloadToTmp($bg['image_url'])) {
                $background = new BgImage();
                $background->setPath($path);
                $slide->setBackground($background);
                $this->tempFiles[] = $path;
                return;
            }
            $type = 'solid';
        }

        if ($type === 'gradient' && isset($bg['gradient']['from'], $bg['gradient']['to'])) {
            $this->createGradientLayer($slide, $bg['gradient']['from'], $bg['gradient']['to']);
            return;
        }

        $color = $bg['color'] ?? ($theme['background_default']['color'] ?? '#FFFFFF');
        $background = new BgColor();
        $background->setColor(new Color($this->hexToArgb($color, $theme['background_default']['color'] ?? '#FFFFFF')));
        $slide->setBackground($background);
    }

    private function downloadToTmp(string $url): ?string
    {
        try {
            $response = Http::timeout(10)->get($url);
            if ($response->successful()) {
                $path = storage_path('app/tmp/' . Str::uuid()->toString());
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
