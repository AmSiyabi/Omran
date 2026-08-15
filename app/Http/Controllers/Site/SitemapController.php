<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Instructor;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        /** @var list<array{loc: string, lastmod: ?string, priority: string}> $urls */
        $urls = [
            ['loc' => route('public.home'), 'lastmod' => null, 'priority' => '1.0'],
            ['loc' => route('public.courses'), 'lastmod' => null, 'priority' => '0.9'],
            ['loc' => route('public.work'), 'lastmod' => null, 'priority' => '0.6'],
            ['loc' => route('public.instructors'), 'lastmod' => null, 'priority' => '0.6'],
            ['loc' => route('public.about'), 'lastmod' => null, 'priority' => '0.6'],
            ['loc' => route('public.contact'), 'lastmod' => null, 'priority' => '0.5'],
        ];

        foreach (Course::query()->where('is_published', true)->get(['slug', 'updated_at']) as $course) {
            $urls[] = [
                'loc' => route('public.courses.show', $course->slug),
                'lastmod' => $course->updated_at?->toDateString(),
                'priority' => '0.8',
            ];
        }

        foreach (Instructor::query()->where('is_public', true)->get(['id', 'updated_at']) as $instructor) {
            $urls[] = [
                'loc' => route('public.instructors.show', $instructor->id),
                'lastmod' => $instructor->updated_at?->toDateString(),
                'priority' => '0.5',
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($urls as $url) {
            $xml .= '  <url><loc>'.e($url['loc']).'</loc>';

            if ($url['lastmod'] !== null) {
                $xml .= '<lastmod>'.$url['lastmod'].'</lastmod>';
            }

            $xml .= '<priority>'.$url['priority'].'</priority></url>'."\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
