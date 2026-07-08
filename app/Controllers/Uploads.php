<?php

namespace App\Controllers;

/**
 * Serves uploaded files from `writable/uploads/` so they can be displayed on
 * the public site via `/uploads/<subfolder>/filename`.
 *
 * Whitelisted subfolders:
 *   - applications/  Applicant-submitted documents (Lean Canvas, etc.)
 *   - templates/     Admin-managed templates (e.g. the Lean Canvas template).
 *
 * Post images live in `public/uploads/posts/` and are served directly by the
 * web server — they do NOT go through this controller.
 */

class Uploads extends BaseController
{
    /**
     * Subfolders under writable/uploads/ that are publicly servable.
     */
    private const ALLOWED_SUBFOLDERS = ['applications', 'templates'];

    public function serve(string ...$segments)
    {
        if ($segments === []) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $subfolder = array_shift($segments);

        if (! in_array($subfolder, self::ALLOWED_SUBFOLDERS, true)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $relativePath = $subfolder . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segments);
        $fullPath     = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . $relativePath;

        // Guard against path traversal outside writable/uploads/.
        $realBase = realpath(WRITEPATH . 'uploads');
        $realFile = realpath($fullPath);

        if ($realBase === false || $realFile === false || ! is_file($realFile) || strpos($realFile, $realBase) !== 0) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // Detect MIME from file content using CI's File class
        $file = new \CodeIgniter\Files\File($realFile);
        $mime = $file->getMimeType() ?: 'application/octet-stream';

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Cache-Control', 'public, max-age=86400')
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setBody(file_get_contents($realFile));
    }

    /**
     * Handle CORS preflight requests for the Office Online viewer.
     */
    public function options(string ...$segments)
    {
        return $this->response
            ->setStatusCode(204)
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->setHeader('Access-Control-Max-Age', '86400');
    }
}
