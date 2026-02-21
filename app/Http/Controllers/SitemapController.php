<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index()
    {
        $baseUrl = config('app.url');
        $urls = [
            [
                'loc' => $baseUrl . '/',
                'changefreq' => 'daily',
                'priority' => '1.0',
            ],
            [
                'loc' => $baseUrl . '/marketplace',
                'changefreq' => 'daily',
                'priority' => '0.8',
            ],
        ];

        User::query()
            ->whereNotNull('slug')
            ->get(['slug', 'updated_at'])
            ->each(function (User $user) use (&$urls, $baseUrl) {
                $urls[] = [
                    'loc' => $baseUrl . '/user/' . $user->slug,
                    'lastmod' => optional($user->updated_at)->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.6',
                ];
            });

        $xml = view('public.sitemap', compact('urls'))->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    public function robots()
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Sitemap: ' . config('app.url') . '/sitemap.xml',
        ];

        return response(implode("\n", $lines), 200)->header('Content-Type', 'text/plain');
    }
}
