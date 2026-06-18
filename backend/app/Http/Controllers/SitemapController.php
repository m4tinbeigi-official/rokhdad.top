<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\Person;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    /**
     * Serve the sitemap index.
     * GET /sitemap.xml
     */
    public function index(): Response
    {
        $xml = Cache::remember('sitemap.index', 3600, function () {
            $base = rtrim((string) config('app.url'), '/');

            $urls = collect([
                ['loc' => $base.'/', 'changefreq' => 'daily', 'priority' => '1.0'],
                ['loc' => $base.'/events', 'changefreq' => 'hourly', 'priority' => '0.9'],
                ['loc' => $base.'/categories', 'changefreq' => 'weekly', 'priority' => '0.7'],
                ['loc' => $base.'/cities', 'changefreq' => 'weekly', 'priority' => '0.7'],
            ]);

            // Events
            Event::query()->where('status', 'published')->select(['slug', 'updated_at'])->orderByDesc('updated_at')->chunk(500, function ($events) use (&$urls, $base) {
                foreach ($events as $event) {
                    $urls->push([
                        'loc' => $base.'/events/'.$event->slug,
                        'lastmod' => $event->updated_at?->toAtomString(),
                        'changefreq' => 'daily',
                        'priority' => '0.8',
                    ]);
                }
            });

            // Organizers
            Organizer::query()->where('is_active', true)->select(['slug', 'updated_at'])->chunk(200, function ($organizers) use (&$urls, $base) {
                foreach ($organizers as $org) {
                    $urls->push([
                        'loc' => $base.'/organizers/'.$org->slug,
                        'lastmod' => $org->updated_at?->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.6',
                    ]);
                }
            });

            // People
            Person::query()->select(['slug', 'updated_at'])->chunk(200, function ($people) use (&$urls, $base) {
                foreach ($people as $person) {
                    $urls->push([
                        'loc' => $base.'/people/'.$person->slug,
                        'lastmod' => $person->updated_at?->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.5',
                    ]);
                }
            });

            $lines = ['<?xml version="1.0" encoding="UTF-8"?>'];
            $lines[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
            foreach ($urls as $url) {
                $lines[] = '  <url>';
                $lines[] = '    <loc>'.htmlspecialchars($url['loc']).'</loc>';
                if (! empty($url['lastmod'])) {
                    $lines[] = '    <lastmod>'.$url['lastmod'].'</lastmod>';
                }
                $lines[] = '    <changefreq>'.$url['changefreq'].'</changefreq>';
                $lines[] = '    <priority>'.$url['priority'].'</priority>';
                $lines[] = '  </url>';
            }
            $lines[] = '</urlset>';

            return implode("\n", $lines);
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    /**
     * Serve robots.txt.
     * GET /robots.txt
     */
    public function robots(): Response
    {
        $base = rtrim((string) config('app.url'), '/');
        $content = implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /api/v1/auth',
            '',
            "Sitemap: {$base}/sitemap.xml",
        ]);

        return response($content, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
