<?php

namespace App\Libraries;

use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * DocumentUpload — reusable library for handling document uploads
 * (PDF / Word) into writable/uploads/.
 *
 * Mirrors the ImageUpload library's shape so callers use the same API:
 *   $uploader = new \App\Libraries\DocumentUpload();
 *   $path = $uploader->upload($file, 'templates');
 *   // returns a relative path like "templates/abc123.pdf" or null on failure
 *
 * Note: unlike ImageUpload, files live under writable/uploads/ (served via the
 * Uploads controller) rather than public/uploads/.
 */
class DocumentUpload
{
    /** Base upload directory inside `writable/uploads/`. */
    protected string $basePath;

    /** Maximum file size in bytes (10 MB). */
    protected int $maxSize = 10485760;

    /** Allowed MIME types. */
    protected array $allowedTypes = [
        'application/pdf',
        'application/msword',                                                          // .doc
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',     // .docx
    ];

    /** Allowed file extensions, used as a secondary check on the client name. */
    protected array $allowedExtensions = ['pdf', 'doc', 'docx'];

    /** Last error message. */
    protected string $error = '';

    public function __construct()
    {
        $this->basePath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR;
    }

    /**
     * Upload a document to the given subfolder under writable/uploads/.
     *
     * @param  UploadedFile|null $file          The uploaded file instance.
     * @param  string            $subfolder     e.g. "templates"
     * @param  int|null          $maxSizeBytes  Optional per-upload size cap in bytes.
     * @return string|null                      Relative path from writable/uploads/ or null on failure.
     */
    public function upload(?UploadedFile $file, string $subfolder = 'templates', ?int $maxSizeBytes = null): ?string
    {
        if ($file === null || ! $file->isValid() || $file->hasMoved()) {
            $this->error = 'No valid file was uploaded.';
            log_message('error', '[DocumentUpload] ' . $this->error);
            return null;
        }

        // Resolve MIME once before move() because temp upload path is removed after moving.
        $mimeType = $file->getMimeType() ?: $file->getClientMimeType();

        // Validate MIME
        if (! in_array($mimeType, $this->allowedTypes, true)) {
            $this->error = 'Invalid file type (' . $mimeType . '). Allowed: PDF, DOC, DOCX.';
            log_message('error', '[DocumentUpload] ' . $this->error);
            return null;
        }

        // Secondary extension check on the client-supplied name.
        $clientExt = strtolower((string) $file->getClientExtension());
        if ($clientExt !== '' && ! in_array($clientExt, $this->allowedExtensions, true)) {
            $this->error = 'Invalid file extension (.' . $clientExt . '). Allowed: PDF, DOC, DOCX.';
            log_message('error', '[DocumentUpload] ' . $this->error);
            return null;
        }

        // Validate size (compare raw bytes to avoid number_format string bug)
        $fileSizeBytes = $file->getSize();
        $sizeLimitBytes = $maxSizeBytes ?? $this->maxSize;
        if ($fileSizeBytes > $sizeLimitBytes) {
            $maxMB = round($sizeLimitBytes / 1048576, 1);
            $fileMB = round($fileSizeBytes / 1048576, 1);
            $this->error = "File ({$fileMB} MB) exceeds the maximum size of {$maxMB} MB.";
            log_message('error', '[DocumentUpload] ' . $this->error);
            return null;
        }

        $destination = $this->basePath . $subfolder;

        // Ensure destination directory exists and is writable
        if (! is_dir($destination)) {
            if (! mkdir($destination, 0755, true)) {
                $this->error = 'Could not create upload directory: ' . $destination;
                log_message('error', '[DocumentUpload] ' . $this->error);
                return null;
            }
        }

        if (! is_writable($destination)) {
            $this->error = 'Upload directory is not writable: ' . $destination;
            log_message('error', '[DocumentUpload] ' . $this->error);
            return null;
        }

        // Sanitize the original client name to create a safe, readable filename.
        $originalName = $file->getClientName();
        $safeName = $this->sanitizeFileName($originalName);

        // If sanitization produced nothing usable, fall back to a random name.
        if ($safeName === '') {
            $safeName = $file->getRandomName();
        }

        // Avoid collisions: if a file with the same name already exists, append a counter.
        $targetPath = $destination . DIRECTORY_SEPARATOR . $safeName;
        $counter = 1;
        $baseName = pathinfo($safeName, PATHINFO_FILENAME);
        $extension = pathinfo($safeName, PATHINFO_EXTENSION);
        while (is_file($targetPath)) {
            $safeName = $baseName . '-' . $counter . ($extension !== '' ? '.' . $extension : '');
            $targetPath = $destination . DIRECTORY_SEPARATOR . $safeName;
            $counter++;
        }

        $newName = $safeName;

        try {
            $file->move($destination, $newName);
        } catch (\Throwable $e) {
            $this->error = 'Failed to move uploaded file: ' . $e->getMessage();
            log_message('error', '[DocumentUpload] ' . $this->error);
            return null;
        }

        // Verify the file was actually written
        $finalPath = $destination . DIRECTORY_SEPARATOR . $newName;
        if (! is_file($finalPath)) {
            $this->error = 'File was moved but not found at destination.';
            log_message('error', '[DocumentUpload] ' . $this->error . ' Expected: ' . $finalPath);
            return null;
        }

        $relativePath = $subfolder . '/' . $newName;

        log_message('info', '[DocumentUpload] Success: ' . $relativePath . ' (' . filesize($finalPath) . ' bytes)');

        // Return path relative to writable/uploads/
        return $relativePath;
    }

    /**
     * Delete an uploaded document.
     *
     * @param  string|null $relativePath Path relative to writable/uploads/.
     */
    public function delete(?string $relativePath): bool
    {
        if (empty($relativePath)) {
            return false;
        }

        $fullPath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);

        // Guard against path traversal outside writable/uploads/.
        $realBase = realpath(WRITEPATH . 'uploads');
        $realFile = realpath($fullPath);

        if ($realBase === false || $realFile === false || strpos($realFile, $realBase) !== 0) {
            return false;
        }

        if (is_file($realFile)) {
            return unlink($realFile);
        }

        return false;
    }

    /**
     * Return the last error message.
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * Resolve a stored relative path to a public-facing URL via the Uploads controller.
     */
    public function publicUrl(?string $relativePath): string
    {
        $relativePath = trim((string) $relativePath);

        return $relativePath !== ''
            ? site_url('uploads/' . str_replace('\\', '/', $relativePath))
            : '';
    }

    /**
     * Sanitize an uploaded file's client name into a safe filesystem name.
     *
     * Strips special characters, replaces spaces with hyphens, lowercases,
     * and ensures the extension is kept.
     */
    private function sanitizeFileName(string $name): string
    {
        $name = trim($name);

        if ($name === '' || $name === '.') {
            return '';
        }

        // Extract extension first.
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $base = pathinfo($name, PATHINFO_FILENAME);

        if ($base === '') {
            return '';
        }

        // Replace anything that isn't alphanumeric, hyphen, underscore, or dot with hyphen.
        $base = preg_replace('/[^a-zA-Z0-9_\-]/', '-', $base);
        // Collapse multiple hyphens.
        $base = preg_replace('/-+/', '-', $base);
        $base = trim($base, '-');

        if ($base === '') {
            return '';
        }

        return $base . ($extension !== '' ? '.' . $extension : '');
    }
}
