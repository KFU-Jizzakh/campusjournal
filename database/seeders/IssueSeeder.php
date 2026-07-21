<?php

namespace Database\Seeders;

use App\Models\Issue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class IssueSeeder extends Seeder
{
    public function run(): void
    {
        $issue = Issue::firstOrCreate(
            ['number' => 1, 'year' => 2026],
            [
                'volume' => 1,
                'title' => 'Русский язык как язык науки',
                'theme' => 'Русский язык как язык науки',
                'description' => 'Первый номер журнала посвящен русскому языку не только как предмету изучения, но как действенному инструменту для получения современных знаний, проведения исследований и построения академической карьеры. Номер включает научно-аналитические статьи, кейсы преподавания на русском языке, методические материалы и обзор русскоязычных научных порталов.',
                'status' => 'published',
                'published_at' => '2026-12-01',
            ]
        );

        if ($issue->status === 'published' && ! $issue->pdf_path) {
            $pdfPath = 'issues/'.$issue->id.'.pdf';
            Storage::disk('public')->put($pdfPath, $this->generateIssuePdf($issue));
            $issue->update(['pdf_path' => $pdfPath]);
        }

        Issue::firstOrCreate(
            ['number' => 2, 'year' => 2027],
            [
                'volume' => 1,
                'title' => 'Международные образовательные проекты',
                'theme' => 'Международные образовательные проекты',
                'description' => 'Номер выступает как площадка для обмена успешным опытом и обсуждения вызовов в реализации совместных программ, сетевого взаимодействия, академической мобильности между филиалами и с головными вузами. Включает экспертно-аналитические статьи о транснациональном образовании, кейсы двойных дипломов и обзор грантовых возможностей.',
                'status' => 'planned',
            ]
        );

        Issue::firstOrCreate(
            ['number' => 3, 'year' => 2027],
            [
                'volume' => 1,
                'title' => 'Культурный код в преподавании',
                'theme' => 'Культурный код в преподавании',
                'description' => 'Номер исследует, как культурные особенности студентов и преподавателей влияют на образовательный процесс, и как через преподавание предметов транслируются ценности и смыслы. Связан с ежегодным Форумом — в основу могут лечь лучшие доклады и дискуссии.',
                'status' => 'planned',
            ]
        );

        Issue::firstOrCreate(
            ['number' => 4, 'year' => 2027],
            [
                'volume' => 1,
                'title' => 'Карьера выпускника',
                'theme' => 'Карьера выпускника',
                'description' => 'Номер показывает конечный результат работы филиала — успешного, востребованного специалиста. Включает исследование карьерных траекторий выпускников, портреты успеха, карьерные инструкции и взгляд работодателей на диплом российского филиала.',
                'status' => 'planned',
            ]
        );
    }

    private function generateIssuePdf(Issue $issue): string
    {
        $lines = [
            'Global Campus RU',
            '',
            "Tom {$issue->volume}, No{$issue->number} ({$issue->year})",
            $issue->title,
            '',
            $issue->theme ? "Тема: {$issue->theme}" : null,
            '',
            $issue->description,
            '',
            'Содержание',
            '',
        ];

        $articles = $issue->articles()->where('status', 'published')->with('authors')->get();

        foreach ($articles as $i => $article) {
            $authors = $article->authors->pluck('full_name')->join(', ');
            $lines[] = ($i + 1).'. '.$article->title;
            if ($authors) {
                $lines[] = '   '.$authors;
            }
            $lines[] = '';
        }

        $content = implode("\n", array_filter($lines, fn ($l) => $l !== null));

        return $this->buildPdf($content);
    }

    private function buildPdf(string $text): string
    {
        $stream = $this->pdfEncodeStream($text);
        $streamLen = strlen($stream);

        $objects = [];
        $objects[1] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj";
        $objects[2] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj";
        $objects[3] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj";
        $objects[4] = "4 0 obj\n<< /Length {$streamLen} >>\nstream\n{$stream}\nendstream\nendobj";
        $objects[5] = "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj";

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $num => $obj) {
            $offsets[$num] = strlen($pdf);
            $pdf .= $obj."\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    private function pdfEncodeStream(string $text): string
    {
        $lines = explode("\n", $text);
        $commands = "BT\n/F1 11 Tf\n";
        $y = 800;

        foreach ($lines as $line) {
            if ($y < 40) {
                break;
            }
            $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
            $commands .= "1 0 0 1 40 {$y} Tm\n({$escaped}) Tj\n";
            $y -= 16;
        }

        $commands .= 'ET';

        return $commands;
    }
}
