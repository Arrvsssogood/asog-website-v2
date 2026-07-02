<?php

/**
 * image_helper — responsive <img> / <picture> generators.
 *
 * Naming convention for sidecar variants produced by ImageUpload:
 *   posts      : {stem}-240w.webp  (240×160 crop)
 *   incubatees : {stem}-160w.webp  (160×160 contain)
 *
 * Static asset variants are pre-generated with ImageMagick at known paths.
 *
 * Usage:
 *   echo responsiveUploadImg($relativePath, 'posts', 'Post title');
 *   echo responsiveUploadImg($relativePath, 'incubatees', 'Company name');
 *   echo responsiveStaticImg('assets/img/team/Odsinada', 'team-landing', 'Name');
 *   echo responsiveNavLogo();
 *   echo responsiveIncubateesHero();
 */

// ─── Uploaded dynamic images ──────────────────────────────────────────────────

/**
 * Build a srcset <img> for an uploaded image (posts or incubatees logo).
 *
 * For posts the thumb is 240w (crop 3:2), full is served at 400w.
 * For incubatees the thumb is 160w (contain square).
 *
 * @param string $relativePath  e.g. "uploads/posts/abc.webp"
 * @param string $type          'posts' | 'incubatees'
 * @param string $alt
 * @param string $extraClass    additional CSS classes
 * @return string
 */
function responsiveUploadImg(string $relativePath, string $type, string $alt = '', string $extraClass = '', bool $lazy = false): string
{
    $stem = pathinfo($relativePath, PATHINFO_FILENAME);
    $dir  = rtrim(dirname($relativePath), '/\\') . '/';

    $classAttr = $extraClass !== '' ? ' class="' . esc($extraClass, 'attr') . '"' : '';
    $lazyAttr  = $lazy ? ' loading="lazy"' : '';

    $intrinsic = static function (string $relativeFile): array {
        $fsPath = FCPATH . ltrim($relativeFile, '/');
        if (! is_file($fsPath)) {
            return [null, null];
        }

        $size = @getimagesize($fsPath);
        if (! is_array($size) || empty($size[0]) || empty($size[1])) {
            return [null, null];
        }

        return [(int) $size[0], (int) $size[1]];
    };

    $dimsAttr = static function (?int $width, ?int $height): string {
        if (! $width || ! $height) {
            return '';
        }

        return ' width="' . $width . '" height="' . $height . '"';
    };

    switch ($type) {
        case 'posts':
            // Sidebar card: 100px wide; featured: ~580px wide (1.3fr of 1200px max)
            // Prefer 100w/400w/800w sidecars when available so the browser can avoid the full upload.
            $thumb100 = $dir . $stem . '-100w.webp';
            $thumb180 = $dir . $stem . '-180w.webp';
            $mid      = $dir . $stem . '-400w.webp';
            $full  = $relativePath;
            if (is_file(FCPATH . $mid)) {
                [$midW, $midH] = $intrinsic($mid);
                $srcsetParts = [];
                if (is_file(FCPATH . $thumb100)) {
                    $srcsetParts[] = base_url(esc($thumb100)) . ' 100w';
                }
                $srcsetParts[] = base_url(esc($mid)) . ' 400w';
                $srcsetParts[] = base_url(esc($full)) . ' 800w';

                return '<img src="' . base_url(esc($mid)) . '"'
                    . ' srcset="' . implode(', ', $srcsetParts) . '"'
                    . ' sizes="(min-width: 1024px) min(580px, 54vw), 100vw"'
                    . $dimsAttr($midW, $midH)
                    . ' alt="' . esc($alt) . '"' . $classAttr . $lazyAttr . '>';
            }

            if (is_file(FCPATH . $thumb180)) {
                [$thumbW, $thumbH] = $intrinsic($thumb180);
                $srcsetParts = [base_url(esc($thumb180)) . ' 180w'];
                if (is_file(FCPATH . $full)) {
                    $srcsetParts[] = base_url(esc($full)) . ' 800w';
                }

                return '<img src="' . base_url(esc($thumb180)) . '"'
                    . ' srcset="' . implode(', ', $srcsetParts) . '"'
                    . ' sizes="(min-width: 1024px) min(580px, 54vw), 100vw"'
                    . $dimsAttr($thumbW, $thumbH)
                    . ' alt="' . esc($alt) . '"' . $classAttr . $lazyAttr . '>';
            }

            [$fullW, $fullH] = $intrinsic($full);
            return '<img src="' . base_url(esc($full)) . '"' . $dimsAttr($fullW, $fullH) . ' alt="' . esc($alt) . '"' . $classAttr . $lazyAttr . '>';

        case 'posts-thumb':
            // Sidebar thumbnail: fixed 100–180px container
            $thumb = $dir . $stem . '-100w.webp';
            $thumb180 = $dir . $stem . '-180w.webp';
            $full  = $relativePath;
            if (is_file(FCPATH . $thumb)) {
                [$thumbW, $thumbH] = $intrinsic($thumb);
                $srcsetParts = [base_url(esc($thumb)) . ' 100w'];
                if (is_file(FCPATH . $thumb180)) {
                    $srcsetParts[] = base_url(esc($thumb180)) . ' 180w';
                }

                return '<img src="' . base_url(esc($thumb)) . '"'
                    . ' srcset="' . implode(', ', $srcsetParts) . '"'
                    . ' sizes="100px"'
                    . $dimsAttr($thumbW, $thumbH)
                    . ' alt="' . esc($alt) . '"' . $classAttr . $lazyAttr . '>';
            }
            if (is_file(FCPATH . $thumb180)) {
                [$thumbW, $thumbH] = $intrinsic($thumb180);
                return '<img src="' . base_url(esc($thumb180)) . '"'
                    . ' srcset="' . base_url(esc($thumb180)) . ' 180w"'
                    . ' sizes="100px"'
                    . $dimsAttr($thumbW, $thumbH)
                    . ' alt="' . esc($alt) . '"' . $classAttr . $lazyAttr . '>';
            }
            [$fullW, $fullH] = $intrinsic($full);
            return '<img src="' . base_url(esc($full)) . '"' . $dimsAttr($fullW, $fullH) . ' alt="' . esc($alt) . '"' . $classAttr . $lazyAttr . '>';

        case 'incubatees':
            // Logo carousel: up to 110px display
            $thumb = $dir . $stem . '-160w.webp';
            $full  = $relativePath;
            if (is_file(FCPATH . $thumb)) {
                [$thumbW, $thumbH] = $intrinsic($thumb);
                return '<img src="' . base_url(esc($thumb)) . '"'
                    . ' srcset="' . base_url(esc($thumb)) . ' 160w"'
                    . ' sizes="110px"'
                    . $dimsAttr($thumbW, $thumbH)
                    . ' alt="' . esc($alt) . '"' . $classAttr . $lazyAttr . '>';
            }
            [$fullW, $fullH] = $intrinsic($full);
            return '<img src="' . base_url(esc($full)) . '"' . $dimsAttr($fullW, $fullH) . ' alt="' . esc($alt) . '"' . $classAttr . $lazyAttr . '>';

        default:
            return '<img src="' . base_url(esc($relativePath)) . '" alt="' . esc($alt) . '"' . $classAttr . $lazyAttr . '>';
    }
}

// ─── Static image helpers ─────────────────────────────────────────────────────

/**
 * Build a srcset <img> for static team / partner / other pre-resized assets.
 *
 * @param string $basePath  Path without extension, e.g. "assets/img/team/Odsinada"
 * @param string $type      'team-landing' | 'team-org' | 'partner' | 'nav-logo'
 * @param string $alt
 * @param string $extraClass
 * @return string
 */
function responsiveStaticImg(string $basePathFromDb, string $type, string $alt = '', string $extraClass = '', bool $lazy = false): string
{
    // Normalize: remove any -300w/-500w suffix and strip extension if present
    $base = preg_replace('/(-\d+w)?\.(webp|png|jpe?g)$/i', '', $basePathFromDb);
    $classAttr = $extraClass !== '' ? ' class="' . esc($extraClass, 'attr') . '"' : '';
    $lazyAttr  = $lazy ? ' loading="lazy"' : '';

    $intrinsic = static function (string $relativeFile): array {
        $fsPath = FCPATH . ltrim($relativeFile, '/');
        if (! is_file($fsPath)) {
            return [null, null];
        }

        $size = @getimagesize($fsPath);
        if (! is_array($size) || empty($size[0]) || empty($size[1])) {
            return [null, null];
        }

        return [(int) $size[0], (int) $size[1]];
    };

    $dimsAttr = static function (?int $width, ?int $height): string {
        if (! $width || ! $height) {
            return '';
        }

        return ' width="' . $width . '" height="' . $height . '"';
    };

    // Candidate files (webp preferred, then originals)
    $candidates = [
        // retina-friendly small variants (1x / 2x)
        '44'  => $base . '-44w.webp',
        '88'  => $base . '-88w.webp',
        '80'  => $base . '-80w.webp',
        '160' => $base . '-160w.webp',
        '180' => $base . '-180w.webp',
        '300' => $base . '-300w.webp',
        '500' => $base . '-500w.webp',
        'full_webp' => $base . '.webp',
        'full_png'  => $base . '.png',
        'full_jpg'  => $base . '.jpg',
        'full_jpeg' => $base . '.jpeg',
    ];

    // Resolve which files actually exist
    $found = [];
    foreach ($candidates as $k => $rel) {
        $fsPath = FCPATH . ltrim($rel, '/');
        if (is_file($fsPath)) {
            $found[$k] = base_url($rel);
        }
    }

    // Helper to build srcset string from available entries
    $buildSrcset = function(array $pairs) {
        return implode(', ', array_map(fn($p) => $p['url'] . ' ' . $p['w'], $pairs));
    };

    switch ($type) {
        case 'team-landing':
            // prefer 300w and full webp
            if (isset($found['80']) && isset($found['160'])) {
                // prefer 1x/2x variants when available
                [$w, $h] = $intrinsic(parse_url($found['80'], PHP_URL_PATH) ?: $found['80']);
                return '<img src="' . esc($found['80']) . '" srcset="' . esc($found['80']) . ' 80w, ' . esc($found['160']) . ' 160w" sizes="165px" alt="' . esc($alt) . '"' . $classAttr . $dimsAttr($w, $h) . $lazyAttr . '>';
            }
            if (isset($found['300']) && (isset($found['full_webp']) || isset($found['full_png']) || isset($found['full_jpg']))) {
                $full = $found['full_webp'] ?? $found['full_png'] ?? $found['full_jpg'] ?? $found['full_jpeg'];
                return '<img src="' . esc($found['300']) . '" srcset="' . esc($found['300']) . ' 300w, ' . esc($full) . ' 900w" sizes="165px" alt="' . esc($alt) . '"' . $classAttr . $lazyAttr . '>';
            }
            // fallback to any full file
            $fallback = $found['full_webp'] ?? $found['full_png'] ?? $found['full_jpg'] ?? null;
            if ($fallback) {
                return '<img src="' . esc($fallback) . '" alt="' . esc($alt) . '"' . $classAttr . $lazyAttr . '>';
            }
            break;

        case 'team-org':
            // prefer picture with webp source if available
            // pick 1x/2x if present for small circular thumbnails
            $src44 = $found['44'] ?? null;
            $src88 = $found['88'] ?? null;
            $src300 = $found['300'] ?? null;
            $src500 = $found['500'] ?? null;
            $full   = $found['full_webp'] ?? $found['full_png'] ?? $found['full_jpg'] ?? null;

            if ($src44 || $src88) {
                $fallbackSrc = $src44 ?? $src88;
                $srcset = trim(($src44 ? esc($src44) . ' 44w' : '') . ($src44 && $src88 ? ', ' : '') . ($src88 ? esc($src88) . ' 88w' : ''));
                [$thumbW, $thumbH] = $intrinsic(parse_url($fallbackSrc, PHP_URL_PATH) ?: $fallbackSrc);
                return '<img src="' . esc($fallbackSrc) . '" srcset="' . $srcset . '" sizes="44px"' . $dimsAttr($thumbW, $thumbH) . ' alt="' . esc($alt) . '"' . $classAttr . $lazyAttr . '>';
            }
            if ($src300 || $src500 || $full) {
                $srcsetParts = [];
                if ($src300) $srcsetParts[] = ['url' => $src300, 'w' => '300w'];
                if ($src500) $srcsetParts[] = ['url' => $src500, 'w' => '500w'];
                if ($found['full_webp']) $srcsetParts[] = ['url' => $found['full_webp'], 'w' => '900w'];

                $srcset = implode(', ', array_map(fn($p) => $p['url'] . ' ' . $p['w'], $srcsetParts));
                $fallbackSrc = $found['full_webp'] ?? $found['full_png'] ?? $found['full_jpg'] ?? ($src500 ?? $src300);

                return '<img src="' . esc($fallbackSrc) . '" srcset="' . esc($srcset) . '" sizes="220px" alt="' . esc($alt) . '"' . $classAttr . $lazyAttr . '>';
            }
            break;

        case 'partner':
            // prefer small 44/88 variants for partner logos when available
            $thumb = $found['44'] ?? $found['88'] ?? $found['180'] ?? $found['300'] ?? null;
            $full  = $found['full_webp'] ?? $found['full_png'] ?? $found['full_jpg'] ?? null;
            if ($thumb) {
                $thumbWidth = isset($found['44']) ? '44w' : (isset($found['88']) ? '88w' : (isset($found['180']) ? '180w' : '300w'));
                [$thumbW, $thumbH] = $intrinsic(parse_url($thumb, PHP_URL_PATH) ?: $thumb);
                $srcsetParts = [];
                if (isset($found['44'])) $srcsetParts[] = ['url' => $found['44'], 'w' => '44w'];
                if (isset($found['88'])) $srcsetParts[] = ['url' => $found['88'], 'w' => '88w'];
                if (isset($found['180'])) $srcsetParts[] = ['url' => $found['180'], 'w' => '180w'];
                $srcsetParts[] = ['url' => $full ?? $thumb, 'w' => '900w'];
                $srcset = $buildSrcset($srcsetParts);

                return '<img src="' . esc($thumb) . '" srcset="' . esc($srcset) . '" sizes="(min-width: 768px) 88px, (min-width: 640px) 72px, 64px"' . $dimsAttr($thumbW, $thumbH) . ' alt="' . esc($alt) . '"' . $classAttr . $lazyAttr . '>';
            }
            if ($full) {
                [$fullW, $fullH] = $intrinsic(parse_url($full, PHP_URL_PATH) ?: $full);
                return '<img src="' . esc($full) . '"' . $dimsAttr($fullW, $fullH) . ' alt="' . esc($alt) . '"' . $classAttr . $lazyAttr . '>';
            }
            break;

        case 'footer-logo':
            $thumb = $found['300'] ?? $found['180'] ?? null;
            $full  = $found['full_webp'] ?? $found['full_png'] ?? $found['full_jpg'] ?? null;
            if ($thumb) {
                $thumbWidth = isset($found['300']) ? '300w' : '180w';
                [$thumbW, $thumbH] = $intrinsic(parse_url($thumb, PHP_URL_PATH) ?: $thumb);
                return '<img src="' . esc($thumb) . '" srcset="' . esc($thumb) . ' ' . $thumbWidth . ', ' . esc($full ?? $thumb) . ' 900w" sizes="84px"' . $dimsAttr($thumbW, $thumbH) . ' alt="' . esc($alt) . '"' . $classAttr . $lazyAttr . '>';
            }
            if ($full) {
                [$fullW, $fullH] = $intrinsic(parse_url($full, PHP_URL_PATH) ?: $full);
                return '<img src="' . esc($full) . '"' . $dimsAttr($fullW, $fullH) . ' alt="' . esc($alt) . '"' . $classAttr . $lazyAttr . '>';
            }
            break;

        default:
            $fallback = $found['full_webp'] ?? $found['full_png'] ?? $found['full_jpg'] ?? null;
            if ($fallback) {
                [$fullW, $fullH] = $intrinsic(parse_url($fallback, PHP_URL_PATH) ?: $fallback);
                return '<img src="' . esc($fallback) . '"' . $dimsAttr($fullW, $fullH) . ' alt="' . esc($alt) . '"' . $classAttr . $lazyAttr . '>';
            }
    }

    // Final fallback placeholder
    return '<img src="' . base_url('assets/img/placeholder.png') . '" width="1" height="1" alt="' . esc($alt) . '"' . $classAttr . $lazyAttr . '>';
}


/**
 * Responsive nav logo <picture> element.
 * Stacked (portrait) logo: displayed 56–76px wide.
 * Landscape logo: displayed ~176px wide when scrolled.
 */
function responsiveNavLogo(): string
{
    $base = 'assets/img/ASOG TBI/WebP/ASOG-TBI-stacked-v2';
    [$stackedW, $stackedH] = @getimagesize(FCPATH . $base . '.webp') ?: [null, null];
    return '<picture>'
        . '<source type="image/webp"'
        . ' srcset="' . base_url($base . '-160w.webp') . ' 160w, ' . base_url($base . '-360w.webp') . ' 360w"'
        . ' sizes="(min-width: 64rem) 76px, (min-width: 48rem) 72px, (min-width: 40rem) 64px, 56px">'
        . '<img src="' . base_url($base . '.webp') . '"' . (($stackedW && $stackedH) ? ' width="' . (int) $stackedW . '" height="' . (int) $stackedH . '"' : '') . ' alt="ASOG TBI" id="navImg" class="h-auto">'
        . '</picture>';
}

/**
 * Landscape version of the nav logo (shown when scrolled).
 */
function responsiveNavLogoLandscape(): string
{
    $base = 'assets/img/ASOG TBI/WebP/ASOG-TBI-stacked-v2';
    [$landscapeW, $landscapeH] = @getimagesize(FCPATH . $base . '.webp') ?: [null, null];
    return '<picture>'
        . '<source type="image/webp"'
        . ' srcset="' . base_url($base . '-360w.webp') . ' 360w"'
        . ' sizes="176px">'
        . '<img src="' . base_url($base . '.webp') . '"' . (($landscapeW && $landscapeH) ? ' width="' . (int) $landscapeW . '" height="' . (int) $landscapeH . '"' : '') . ' alt="ASOG TBI" id="navImgLandscape" class="object-contain">'
        . '</picture>';
}

if (! function_exists('org_photo_url')) {
    /**
     * Resolve a member photo path (assets or uploads) to a full URL.
     *
     * Accepts:
     *  - a web-relative stem like "assets/img/team/Name"
     *  - a web-relative filename like "assets/img/team/Name.webp"
     *  - an absolute URL like "https://cdn.example.com/..."
     *
     * Returns an absolute URL or empty string.
     */
    function org_photo_url(?string $path): string
    {
        $p = trim((string) $path);
        if ($p === '') {
            return '';
        }

        // If already an absolute URL, return as-is
        if (preg_match('#^https?://#i', $p)) {
            return $p;
        }

        // Otherwise treat as web-relative and build with base_url
        return base_url(ltrim($p, '/'));
    }
}

