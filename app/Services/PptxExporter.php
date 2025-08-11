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
use PhpOffice\PhpPresentation\Shape\RichText;
use PhpOffice\PhpPresentation\Shape\Drawing\File as DrawingFile;

class PptxExporter
{
    private array $tempFiles = [];

    public function export(AiTaskVersion $version): void
    {
        $payload = $this->parsePayload($version->payload ?? []);
        $theme   = $payload['theme'] ?? SlideTemplate::defaultTheme();
        $slides  = $payload['slides'] ?? [];

        $presentation = new PhpPresentation();
        $this->tempFiles = [];

        $palette = $theme['palette'] ?? [
            'background' => '#0B1220',
            'primary'    => '#60A5FA',
            'secondary'  => '#A78BFA',
            'accent'     => '#34D399',
        ];
        $accentCycle = [$palette['primary'], $palette['secondary'], $palette['accent']];

        foreach ($slides as $i => $s) {
            $slide = $i === 0 ? $presentation->getActiveSlide() : $presentation->createSlide();
            $this->applyBackground($slide, $s['background'] ?? null, $theme);

            $titleColor  = $s['colors']['title']   ?? $palette['primary'];
            $bulletsCol  = $s['colors']['bullets'] ?? $palette['secondary'];
            $accentColor = $s['colors']['accent']  ?? $accentCycle[$i % count($accentCycle)];

            $layout = strtolower($s['layout'] ?? 'title-bullets');
            match ($layout) {
                'cover'         => $this->renderCover($slide, $s, $theme, $titleColor, $accentColor),
                'image-right'   => $this->renderImageText($slide, $s, $theme, $titleColor, $bulletsCol, 'right'),
                'image-left'    => $this->renderImageText($slide, $s, $theme, $titleColor, $bulletsCol, 'left'),
                'two-column'    => $this->renderTwoColumn($slide, $s, $theme, $titleColor, $bulletsCol),
                'quote'         => $this->renderQuote($slide, $s, $theme, $titleColor, $accentColor),
                'stat'          => $this->renderStat($slide, $s, $theme, $titleColor, $accentColor),
                'section-break' => $this->renderSectionBreak($slide, $s, $theme, $titleColor, $accentColor),
                default         => $this->renderTitleBullets($slide, $s, $theme, $titleColor, $bulletsCol),
            };
        }

        foreach ($this->tempFiles as $tmp) @unlink($tmp);

        $disk = 'private';
        $path = 'slides/' . Str::uuid() . '.pptx';
        Storage::disk($disk)->makeDirectory('slides');

        $writer = IOFactory::createWriter($presentation, 'PowerPoint2007');
        $writer->save(Storage::disk($disk)->path($path));

        $version->update(['file_disk' => $disk, 'file_path' => $path]);
    }

    private function parsePayload($raw): array
    {
        if (isset($raw['content']) && is_string($raw['content'])) {
            $decoded = json_decode($raw['content'], true);
            return is_array($decoded) ? $decoded : [];
        }
        return is_array($raw) ? $raw : [];
    }

    private function normalizeBullets(array $bullets): array
    {
        return array_values(array_filter(array_map(function ($b) {
            return is_string($b) ? trim(strip_tags($b)) : null;
        }, $bullets)));
    }

    /* Layout renderers */
    private function renderCover($slide, array $s, array $theme, string $titleColor, string $accent): void
    {
        $this->titleShape($slide, $s['title'] ?? 'Title', $theme['layout']['title'] ?? ['x'=>80,'y'=>120,'w'=>800,'h'=>120], $theme, $titleColor, 56);

        if (!empty($s['subtitle'])) {
            $rt = $slide->createRichTextShape();
            $rt->setOffsetX(80)->setOffsetY(260)->setWidth(800)->setHeight(80);
            $rt->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $run = $rt->createTextRun($s['subtitle']);
            $run->getFont()->setSize(26)->setColor(new Color($this->hexToArgb($accent)));
        }
        $this->accentBar($slide, $theme, $accent);
    }

    private function renderTitleBullets($slide, array $s, array $theme, string $titleColor, string $bulletsCol): void
    {
        $this->titleShape($slide, $s['title'] ?? 'Slide', $theme['layout']['title'], $theme, $titleColor);
        $this->bulletsShape($slide, $this->normalizeBullets($s['bullets'] ?? []), $theme['layout']['bullets'], $bulletsCol);
    }

    private function renderImageText($slide, array $s, array $theme, string $titleColor, string $bulletsCol, string $pos): void
    {
        $this->titleShape($slide, $s['title'] ?? 'Slide', $theme['layout']['title'], $theme, $titleColor);
        $area = $theme['layout']['bullets'];
        $imgW = (int) round($area['w'] * 0.40);
        $txtW = $area['w'] - $imgW - 20;

        $imgX = ($pos === 'right') ? ($area['x'] + $txtW + 20) : $area['x'];
        $txtX = ($pos === 'right') ? $area['x'] : ($area['x'] + $imgW + 20);

        $this->bulletsShape($slide, $this->normalizeBullets($s['bullets'] ?? []), $this->mergeLayout($area, ['x'=>$txtX,'w'=>$txtW]), $bulletsCol);

        if (!empty($s['image_url']) && ($path = $this->downloadToTmp($s['image_url']))) {
            $pic = new DrawingFile();
            $pic->setPath($path)->setOffsetX($imgX)->setOffsetY($area['y'])->setWidth($imgW);
            $slide->addShape($pic);
            $this->tempFiles[] = $path;
        }
    }

    private function renderTwoColumn($slide, array $s, array $theme, string $titleColor, string $bulletsCol): void
    {
        $this->titleShape($slide, $s['title'] ?? 'Overview', $theme['layout']['title'], $theme, $titleColor);
        $area = $theme['layout']['bullets'];
        $colW = (int)floor(($area['w'] - 20) / 2);

        $this->bulletsShape($slide, $this->normalizeBullets($s['col1'] ?? []), $this->mergeLayout($area, ['x'=>$area['x'],'w'=>$colW]), $bulletsCol);
        $this->bulletsShape($slide, $this->normalizeBullets($s['col2'] ?? []), $this->mergeLayout($area, ['x'=>$area['x'] + $colW + 20,'w'=>$colW]), $bulletsCol);
    }

    private function renderQuote($slide, array $s, array $theme, string $titleColor, string $accent): void
    {
        $quote  = $s['quote'] ?? ($s['title'] ?? 'Quote');
        $author = $s['author'] ?? null;

        $rt = $slide->createRichTextShape();
        $rt->setOffsetX(80)->setOffsetY(150)->setWidth(800)->setHeight(320);
        $rt->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $run = $rt->createTextRun('“'.$quote.'”');
        $run->getFont()->setSize(40)->setColor(new Color($this->hexToArgb($titleColor)));

        if ($author) {
            $rt->createBreak();
            $run2 = $rt->createTextRun('— '.$author);
            $run2->getFont()->setSize(20)->setColor(new Color($this->hexToArgb($accent)));
        }
        $this->accentBar($slide, $theme, $accent);
    }

    private function renderStat($slide, array $s, array $theme, string $titleColor, string $accent): void
    {
        $value = $s['stat_value'] ?? ($s['title'] ?? '42%');
        $desc  = $s['subtitle'] ?? null;

        $rt = $slide->createRichTextShape();
        $rt->setOffsetX(100)->setOffsetY(150)->setWidth(760)->setHeight(280);
        $rt->getActiveParagraph()->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $run = $rt->createTextRun($value);
        $run->getFont()->setSize(72)->setBold(true)->setColor(new Color($this->hexToArgb($titleColor)));

        if ($desc) {
            $rt->createBreak();
            $r2 = $rt->createTextRun($desc);
            $r2->getFont()->setSize(24)->setColor(new Color($this->hexToArgb($accent)));
        }
        $this->accentBar($slide, $theme, $accent);
    }

    private function renderSectionBreak($slide, array $s, array $theme, string $titleColor, string $accent): void
    {
        $this->titleShape($slide, $s['title'] ?? 'Section', ['x'=>120,'y'=>200,'w'=>720,'h'=>120,'align'=>'center'], $theme, $titleColor, 48);
        $this->accentBar($slide, $theme, $accent);
    }

    /* Shape primitives */
    private function titleShape($slide, string $title, array $layout, array $theme, string $color, int $sizeOverride = null): void
    {
        $rt = $slide->createRichTextShape();
        $rt->setWidth($layout['w'])->setHeight($layout['h'])->setOffsetX($layout['x'])->setOffsetY($layout['y']);
        $ha = match($layout['align'] ?? 'left') {
            'center' => Alignment::HORIZONTAL_CENTER,
            'right'  => Alignment::HORIZONTAL_RIGHT,
            default  => Alignment::HORIZONTAL_LEFT
        };
        $rt->getActiveParagraph()->getAlignment()->setHorizontal($ha);

        $run = $rt->createTextRun($title);
        $run->getFont()->setSize($sizeOverride ?? ($theme['font']['title_size'] ?? 44))->setBold(true)->setColor(new Color($this->hexToArgb($color)));
    }

    private function bulletsShape($slide, array $bullets, array $layout, string $color): void
    {
        $rt = $slide->createRichTextShape();
        $rt->setWidth($layout['w'])->setHeight($layout['h'])->setOffsetX($layout['x'])->setOffsetY($layout['y']);
        $indent = (int)($layout['indent'] ?? 10);
        $size   = (int)($layout['size'] ?? 22);

        foreach (array_slice($bullets, 0, 6) as $b) {
            $p = $rt->createParagraph();
            $p->setBulletStyle(new Bullet());
            if (method_exists($p, 'setIndent')) $p->setIndent($indent);
            $r = $p->createTextRun($b);
            $r->getFont()->setSize($size)->setColor(new Color($this->hexToArgb($color)));
        }
    }

    private function accentBar($slide, array $theme, string $accent): void
    {
        $layout = $theme['layout']['title'];
        $bar = new RichText();
        $bar->setOffsetX($layout['x'])->setOffsetY($layout['y'] + $layout['h'] + 8)->setWidth(min(220, $layout['w']))->setHeight(6);
        $bar->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color($this->hexToArgb($accent)));
        $slide->addShape($bar);
    }

    /* Background */
    private function applyBackground($slide, ?array $bg, ?array $theme): void
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

        $color = $bg['color'] ?? ($theme['background_default']['color'] ?? '#0B1220');
        $background = new BgColor();
        $background->setColor(new Color($this->hexToArgb($color)));
        $slide->setBackground($background);
    }

    private function createGradientLayer($slide, string $from, string $to): void
    {
        $rect = new RichText();
        $rect->setWidth(960)->setHeight(540)->setOffsetX(0)->setOffsetY(0);
        $rect->getFill()->setFillType(Fill::FILL_GRADIENT_LINEAR)->setStartColor(new Color($this->hexToArgb($from)))->setEndColor(new Color($this->hexToArgb($to)));
        $slide->addShape($rect);
    }

    private function mergeLayout(array $base, array $override): array
    {
        return array_merge($base, $override);
    }

    private function hexToArgb(string $hex): string
    {
        $hex = ltrim($hex, '#');
        return 'FF' . strtoupper($hex);
    }

    private function downloadToTmp(string $url): ?string
    {
        try {
            $res = Http::timeout(10)->get($url);
            if ($res->successful()) {
                $path = storage_path('app/tmp/' . Str::uuid()->toString());
                if (!is_dir(dirname($path))) mkdir(dirname($path), 0777, true);
                file_put_contents($path, $res->body());
                return $path;
            }
        } catch (\Throwable $e) {}
        return null;
    }
}
