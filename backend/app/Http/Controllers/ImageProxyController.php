<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Response;

class ImageProxyController extends Controller
{
    /**
     * Proxy external images to avoid CORS issues
     */
    public function proxy(Request $request)
    {
        $url = $request->query('url');

        if (!$url) {
            return response()->json(['error' => 'URL parameter is required'], 400);
        }

        // Validate that the URL is from allowed domains
        $allowedDomains = ['2kratings.com', 'cdn.nba.com'];
        $host = parse_url($url, PHP_URL_HOST);

        $isAllowed = false;
        foreach ($allowedDomains as $domain) {
            if (str_ends_with($host, $domain)) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed) {
            return response()->json(['error' => 'Domain not allowed'], 403);
        }

        try {
            // Fetch the image with browser-like headers to avoid blocking
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Referer' => 'https://www.2kratings.com/',
            ])
                ->timeout(10)
                ->get($url);

            if ($response->successful()) {
                // Get the content type from the response
                $contentType = $response->header('Content-Type') ?? 'image/png';

                // Return the image with proper headers
                return response($response->body())
                    ->header('Content-Type', $contentType)
                    ->header('Cache-Control', 'public, max-age=86400') // Cache for 24 hours
                    ->header('Access-Control-Allow-Origin', '*');
            }

            return response()->json(['error' => 'Failed to fetch image', 'status' => $response->status()], $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch image: ' . $e->getMessage()], 500);
        }
    }
}
