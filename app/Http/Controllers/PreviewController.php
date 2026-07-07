<?php

namespace App\Http\Controllers;

use App\Models\Website;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PreviewController extends Controller
{
    /** Serve a file from the generated (unpublished) site to its owner. */
    public function __invoke(Request $request, Website $website, string $path = 'index.html'): BinaryFileResponse
    {
        abort_unless($website->user_id === $request->user()->id, 403);
        abort_unless($website->isGenerated(), 404);

        $root = realpath($website->sitePath());
        abort_if($root === false, 404);

        $file = realpath($root.'/'.$path);

        // Never serve anything outside this site's directory.
        abort_if($file === false || ! str_starts_with($file, $root.DIRECTORY_SEPARATOR), 404);
        abort_unless(is_file($file), 404);

        return response()->file($file, [
            'Content-Type' => $this->mimeType($file),
            'X-Frame-Options' => 'SAMEORIGIN',
        ]);
    }

    private function mimeType(string $file): string
    {
        return match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
            'html', 'htm' => 'text/html; charset=UTF-8',
            'css' => 'text/css; charset=UTF-8',
            'js' => 'text/javascript; charset=UTF-8',
            'json' => 'application/json',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'ico' => 'image/x-icon',
            'txt' => 'text/plain; charset=UTF-8',
            'woff2' => 'font/woff2',
            default => 'application/octet-stream',
        };
    }
}
