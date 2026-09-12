<?php
// core/Watermark.php
// Stamps the shop logo onto product images at upload time to deter image theft.
// Requires the GD extension (present on production). Degrades gracefully:
// if GD is unavailable or a file can't be processed, the original is kept as-is.

class Watermark {

    /** Settings toggle key (admin can flip in Admin → Settings). */
    const SETTING_KEY = 'product_watermark_enabled';

    /**
     * Whether watermarking is switched on (default: yes).
     */
    public static function enabled() {
        try {
            $db = db();
            $stmt = $db->prepare("SELECT value FROM settings WHERE `key` = ?");
            $stmt->execute([self::SETTING_KEY]);
            $row = $stmt->fetch();
            if ($row && $row['value'] === '0') return false;
        } catch (Throwable $e) {
            // If settings can't be read, default to enabled.
        }
        return true;
    }

    /**
     * Watermark every file in a list of stored upload paths (relative to project root,
     * e.g. 'public/uploads/products/p_123_abc_0.jpg'). Safe to call with an empty list.
     * Returns the number of images successfully watermarked.
     */
    public static function applyToPaths(array $relativePaths): int {
        if (!self::enabled() || !function_exists('imagecreatetruecolor')) {
            return 0;
        }
        $done = 0;
        foreach ($relativePaths as $relPath) {
            if (self::applyToFile($relPath)) $done++;
        }
        return $done;
    }

    /**
     * Watermark a single stored upload path (project-root relative).
     */
    public static function applyToFile(string $relPath): bool {
        if (!self::enabled() || !function_exists('imagecreatetruecolor')) {
            return false;
        }
        // Only process files that live inside our products upload dir.
        $prefix = 'public/uploads/products/';
        if (strpos($relPath, $prefix) !== 0 || strpos($relPath, '..') !== false) {
            return false;
        }
        $absPath = __DIR__ . '/../' . $relPath;
        if (!is_file($absPath) || !is_writable($absPath)) {
            return false;
        }
        $ext = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return false;
        }
        try {
            return self::stamp($absPath, $ext);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Core GD routine: open image, overlay the logo near the bottom-right, save in place.
     */
    private static function stamp(string $absPath, string $ext): bool {
        $logoPath = self::logoFile();
        if (!$logoPath) return false;

        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                if (!function_exists('imagecreatefromjpeg')) return false;
                $img = @imagecreatefromjpeg($absPath);
                $save = function ($im) use ($absPath) { return imagejpeg($im, $absPath, 88); };
                break;
            case 'png':
                if (!function_exists('imagecreatefrompng')) return false;
                $img = @imagecreatefrompng($absPath);
                $save = function ($im) use ($absPath) { return imagepng($im, $absPath, 6); };
                break;
            case 'webp':
                if (!function_exists('imagecreatefromwebp')) return false;
                $img = @imagecreatefromwebp($absPath);
                $save = function ($im) use ($absPath) { return imagewebp($im, $absPath, 88); };
                break;
            default:
                return false;
        }
        if (!$img) return false;

        $w = imagesx($img);
        $h = imagesy($img);

        $logo = self::loadLogo($logoPath);
        if (!$logo) { imagedestroy($img); return false; }
        $lw = imagesx($logo);
        $lh = imagesy($logo);

        // Logo width: 26% of the image width, clamped so it stays legible but never
        // dominates small thumbnails; keep the logo's aspect ratio.
        $targetW = (int)max(48, min($w * 0.26, 260));
        $targetH = (int)round($targetW * $lh / max(1, $lw));
        if ($targetH >= $h * 0.9 || $targetW >= $w * 0.9) {
            // Image is tiny compared to the logo — skip rather than cover it.
            imagedestroy($img); imagedestroy($logo);
            return true;
        }

        // Semi-transparent white plate behind the logo so it reads on busy/dark photos.
        $pad = (int)round($targetW * 0.12);
        $plateW = $targetW + $pad * 2;
        $plateH = $targetH + $pad * 2;
        $margin = (int)round($w * 0.03);
        $dstX = max(0, $w - $plateW - $margin);
        $dstY = max(0, $h - $plateH - $margin);

        $plate = imagecreatetruecolor($plateW, $plateH);
        imagefill($plate, 0, 0, imagecolorallocatealpha($plate, 255, 255, 255, 60)); // ~76% white
        imagecopy($img, $plate, $dstX, $dstY, 0, 0, $plateW, $plateH);
        imagedestroy($plate);

        // Blend the (possibly transparent PNG) logo onto the plate.
        imagecopyresampled($img, $logo, $dstX + $pad, $dstY + $pad, 0, 0, $targetW, $targetH, $lw, $lh);
        imagedestroy($logo);

        $ok = $save($img);
        imagedestroy($img);
        return (bool)$ok;
    }

    /**
     * Prefer the site-configured logo; fall back to the bundled assets.
     */
    private static function logoFile(): ?string {
        $candidates = [];
        try {
            $db = db();
            $stmt = $db->prepare("SELECT value FROM settings WHERE `key` = 'site_logo' LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch();
            if ($row && $row['value'] !== '') {
                // Settings may store a web path like 'public/assets/img/logo.png'.
                $rel = ltrim(str_replace([APP_URL, 'https://www.avazonia.com'], '', (string)$row['value']), '/');
                $candidates[] = __DIR__ . '/../' . $rel;
            }
        } catch (Throwable $e) {
            // fall through to defaults
        }
        $candidates[] = __DIR__ . '/../public/assets/img/logo2-rounded.png';
        $candidates[] = __DIR__ . '/../public/assets/img/logo.png';
        foreach ($candidates as $c) {
            if ($c && is_file($c) && is_readable($c)) return $c;
        }
        return null;
    }

    /**
     * Load the logo as GD, preserving alpha; JPEG logos get no alpha but still work.
     */
    private static function loadLogo(string $path) {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $img = null;
        switch ($ext) {
            case 'png':
                if (function_exists('imagecreatefrompng')) {
                    $img = @imagecreatefrompng($path);
                    if ($img) {
                        imagealphablending($img, true);
                        imagesavealpha($img, true);
                    }
                }
                break;
            case 'webp':
                if (function_exists('imagecreatefromwebp')) $img = @imagecreatefromwebp($path);
                break;
            case 'jpg':
            case 'jpeg':
                if (function_exists('imagecreatefromjpeg')) $img = @imagecreatefromjpeg($path);
                break;
            case 'svg':
            default:
                return null; // GD cannot rasterise SVG; use a PNG fallback.
        }
        return $img;
    }
}
