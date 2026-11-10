<?php

namespace App\Services\Media;

/**
 * Removes privacy-sensitive metadata (EXIF, GPS, XMP, IPTC, comments) from JPEG, PNG and WebP
 * files WITHOUT re-encoding the pixels, so originals keep every diagnostic detail bit for bit.
 *
 * Colour information (ICC profile, sRGB/gamma chunks, Adobe colour transform) is kept, because
 * removing it would change how the image looks. Anything that cannot be parsed returns null and
 * the caller falls back to a high-quality re-encode.
 */
class ImageMetadata
{
    /** EXIF orientation (1–8) of a JPEG, or 1 when absent. */
    public static function jpegOrientation(string $bytes): int
    {
        $pos = 2;
        $len = strlen($bytes);
        while ($pos + 4 <= $len && $bytes[$pos] === "\xFF") {
            $marker = ord($bytes[$pos + 1]);
            if ($marker === 0xDA || $marker === 0xD9) {
                break;
            }
            $size = unpack('n', substr($bytes, $pos + 2, 2))[1];
            if ($marker === 0xE1 && substr($bytes, $pos + 4, 6) === "Exif\0\0") {
                return self::tiffOrientation(substr($bytes, $pos + 10, $size - 8));
            }
            $pos += 2 + $size;
        }

        return 1;
    }

    /** Strip metadata from the file in place. Returns false when the format could not be handled. */
    public static function stripFile(string $path, string $mime): bool
    {
        $bytes = @file_get_contents($path);
        if ($bytes === false) {
            return false;
        }
        $clean = match ($mime) {
            'image/jpeg' => self::stripJpeg($bytes),
            'image/png' => self::stripPng($bytes),
            'image/webp' => self::stripWebp($bytes),
            default => null,
        };
        if ($clean === null) {
            return false;
        }
        if ($clean !== $bytes) {
            file_put_contents($path, $clean, LOCK_EX);
        }

        return true;
    }

    public static function stripJpeg(string $b): ?string
    {
        $len = strlen($b);
        if ($len < 4 || substr($b, 0, 2) !== "\xFF\xD8") {
            return null;
        }
        $out = "\xFF\xD8";
        $pos = 2;
        $inScan = false;

        while ($pos < $len) {
            if ($inScan) {
                // Entropy-coded data: copy up to the next real marker (not FF00 stuffing, RSTn or fill bytes).
                $start = $pos;
                while (true) {
                    $ff = strpos($b, "\xFF", $pos);
                    if ($ff === false || $ff + 1 >= $len) {
                        return null; // no end-of-image marker: truncated file
                    }
                    $next = ord($b[$ff + 1]);
                    if ($next === 0x00 || $next === 0xFF || ($next >= 0xD0 && $next <= 0xD7)) {
                        $pos = $ff + ($next === 0xFF ? 1 : 2);

                        continue;
                    }
                    break;
                }
                $out .= substr($b, $start, $ff - $start);
                $pos = $ff;
                $inScan = false;
            }

            if ($b[$pos] !== "\xFF") {
                return null;
            }
            while ($pos + 1 < $len && $b[$pos + 1] === "\xFF") {
                $pos++; // fill bytes
            }
            if ($pos + 1 >= $len) {
                return null;
            }
            $marker = ord($b[$pos + 1]);
            if ($marker === 0xD9) {
                return $out."\xFF\xD9"; // end of image: anything appended after it (e.g. MPF previews with EXIF) is dropped
            }
            if ($marker === 0x01 || ($marker >= 0xD0 && $marker <= 0xD7)) {
                $out .= substr($b, $pos, 2);
                $pos += 2;

                continue;
            }
            if ($pos + 4 > $len) {
                return null;
            }
            $size = unpack('n', substr($b, $pos + 2, 2))[1];
            if ($size < 2 || $pos + 2 + $size > $len) {
                return null;
            }
            $segment = substr($b, $pos, 2 + $size);
            if (self::keepJpegSegment($marker, substr($segment, 4, 14))) {
                $out .= $segment;
            }
            $pos += 2 + $size;
            if ($marker === 0xDA) {
                $inScan = true;
            }
        }

        return null;
    }

    protected static function keepJpegSegment(int $marker, string $head): bool
    {
        if ($marker === 0xFE) {
            return false; // comment
        }
        if ($marker < 0xE0 || $marker > 0xEF) {
            return true; // image data: tables, frame and scan headers
        }

        return match ($marker) {
            0xE0 => str_starts_with($head, "JFIF\0") || str_starts_with($head, "JFXX\0"),
            0xE2 => str_starts_with($head, "ICC_PROFILE\0"),
            0xEE => str_starts_with($head, 'Adobe'), // colour transform, needed to decode CMYK/YCCK correctly
            default => false, // APP1 EXIF/XMP, APP13 IPTC/Photoshop and vendor segments
        };
    }

    public static function stripPng(string $b): ?string
    {
        if (substr($b, 0, 8) !== "\x89PNG\r\n\x1a\n") {
            return null;
        }
        $drop = ['tEXt', 'zTXt', 'iTXt', 'eXIf', 'tIME'];
        $out = substr($b, 0, 8);
        $pos = 8;
        $len = strlen($b);
        while ($pos + 12 <= $len) {
            $size = unpack('N', substr($b, $pos, 4))[1];
            $type = substr($b, $pos + 4, 4);
            if ($pos + 12 + $size > $len) {
                return null;
            }
            if (! in_array($type, $drop, true)) {
                $out .= substr($b, $pos, 12 + $size);
            }
            $pos += 12 + $size;
            if ($type === 'IEND') {
                return $out;
            }
        }

        return null;
    }

    public static function stripWebp(string $b): ?string
    {
        if (strlen($b) < 20 || substr($b, 0, 4) !== 'RIFF' || substr($b, 8, 4) !== 'WEBP') {
            return null;
        }
        $chunks = [];
        $pos = 12;
        $end = min(strlen($b), 8 + unpack('V', substr($b, 4, 4))[1]);
        while ($pos + 8 <= $end) {
            $fourcc = substr($b, $pos, 4);
            $size = unpack('V', substr($b, $pos + 4, 4))[1];
            $total = 8 + $size + ($size & 1);
            if ($pos + 8 + $size > $end) {
                return null;
            }
            if ($fourcc !== 'EXIF' && $fourcc !== 'XMP ') {
                $chunks[] = substr($b, $pos, min($total, $end - $pos));
            }
            $pos += $total;
        }
        if (! $chunks) {
            return null;
        }
        if (str_starts_with($chunks[0], 'VP8X')) {
            $flags = ord($chunks[0][8]) & ~(0x08 | 0x04); // clear the EXIF and XMP flags
            $chunks[0][8] = chr($flags);
        }
        $body = 'WEBP'.implode('', $chunks);

        return 'RIFF'.pack('V', strlen($body)).$body;
    }

    protected static function tiffOrientation(string $tiff): int
    {
        if (strlen($tiff) < 8) {
            return 1;
        }
        $le = substr($tiff, 0, 2) === 'II';
        $u16 = fn (int $o) => $o + 2 <= strlen($tiff) ? unpack($le ? 'v' : 'n', substr($tiff, $o, 2))[1] : 0;
        $u32 = fn (int $o) => $o + 4 <= strlen($tiff) ? unpack($le ? 'V' : 'N', substr($tiff, $o, 4))[1] : 0;
        $ifd = $u32(4);
        $count = $u16($ifd);
        for ($i = 0; $i < $count; $i++) {
            $entry = $ifd + 2 + $i * 12;
            if ($u16($entry) === 0x0112) {
                $value = $u16($entry + 8);

                return $value >= 1 && $value <= 8 ? $value : 1;
            }
        }

        return 1;
    }
}
