<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AirlineLogoService
{
    protected const LOGO_SIZE = 50;

    protected const LOGO_SOURCE = 'https://images.daisycon.io/airline/?width=50&height=50&iata=%s';

    protected const NAME_TO_IATA = [
        'turkish airlines' => 'TK',
        'thy' => 'TK',
        'pegasus' => 'PC',
        'pegasus airlines' => 'PC',
        'sunexpress' => 'XQ',
        'sun express' => 'XQ',
        'ajet' => 'VF',
        'anadolujet' => 'VF',
        'freebird' => 'FH',
        'freebird airlines' => 'FH',
        'corendon' => 'CAI',
        'corendon airlines' => 'CAI',
        'wizz' => 'W6',
        'wizz air' => 'W6',
        'ryanair' => 'FR',
        'easyjet' => 'U2',
        'easy jet' => 'U2',
        'lufthansa' => 'LH',
        'emirates' => 'EK',
        'qatar' => 'QR',
        'qatar airways' => 'QR',
        'flydubai' => 'FZ',
        'atlas' => 'KK',
        'atlasjet' => 'KK',
        'atlasglobal' => 'KK',
        'british airways' => 'BA',
        'klm' => 'KL',
        'air france' => 'AF',
        'alitalia' => 'AZ',
        'singapore airlines' => 'SQ',
        'etihad' => 'EY',
        'saudia' => 'SV',
        'gulf air' => 'GF',
        'oman air' => 'WY',
        'aeroflot' => 'SU',
        'lot' => 'LO',
    ];

    protected static array $resolvedLogos = [];

    protected string $relativeDirectory = 'airline-logos';

    public function resolve(?string $airline): array
    {
        $displayName = trim((string) $airline);
        $cacheKey = mb_strtolower($displayName !== '' ? $displayName : 'default');

        if (isset(self::$resolvedLogos[$cacheKey])) {
            return self::$resolvedLogos[$cacheKey];
        }

        $this->ensureDefaultLogo();

        $iata = $this->resolveIataCode($displayName);
        $relativePath = $this->defaultRelativePath();

        if ($iata && $this->ensureLogoExists($iata)) {
            $relativePath = $this->relativeDirectory.'/'.$iata.'.png';
        }

        return self::$resolvedLogos[$cacheKey] = [
            'iata' => $iata,
            'display_name' => $displayName !== '' ? $displayName : 'Havayolu',
            'path' => asset($relativePath),
            'relative_path' => $relativePath,
            'has_logo' => basename($relativePath) !== 'default.png',
        ];
    }

    protected function ensureLogoExists(string $iata): bool
    {
        $iata = strtoupper(trim($iata));

        if ($iata === '' || ! $this->ensureDirectory()) {
            return false;
        }

        $targetPath = $this->logoPath($iata);

        if (File::exists($targetPath)) {
            return true;
        }

        try {
            $response = Http::timeout(8)
                ->retry(1, 250)
                ->get(sprintf(self::LOGO_SOURCE, $iata));
        } catch (\Throwable $e) {
            return false;
        }

        if (! $response->successful()) {
            return false;
        }

        $binary = $response->body();

        return $binary !== '' && $this->normalizeAndSavePng($binary, $targetPath);
    }

    protected function resolveIataCode(string $airline): ?string
    {
        $airline = trim($airline);

        if ($airline === '') {
            return null;
        }

        if (preg_match('/^\s*([A-Z0-9]{2})\b/u', strtoupper($airline), $matches)) {
            return strtoupper($matches[1]);
        }

        $normalized = $this->normalizeAirlineName($airline);

        return self::NAME_TO_IATA[$normalized] ?? null;
    }

    protected function normalizeAirlineName(string $airline): string
    {
        return Str::of($airline)
            ->ascii()
            ->lower()
            ->replace(['-', '_'], ' ')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->value();
    }

    protected function normalizeAndSavePng(string $binary, string $targetPath): bool
    {
        if (! $this->canProcessImages()) {
            return false;
        }

        $sourceImage = @imagecreatefromstring($binary);

        if (! $sourceImage) {
            return false;
        }

        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);

        if ($sourceWidth < 1 || $sourceHeight < 1) {
            imagedestroy($sourceImage);

            return false;
        }

        $sourceImage = $this->makeWhitePixelsTransparent($sourceImage);

        $canvas = imagecreatetruecolor(self::LOGO_SIZE, self::LOGO_SIZE);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);

        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefill($canvas, 0, 0, $transparent);

        $usableSize = self::LOGO_SIZE - 6;
        $scale = min($usableSize / $sourceWidth, $usableSize / $sourceHeight);
        $newWidth = max(1, (int) round($sourceWidth * $scale));
        $newHeight = max(1, (int) round($sourceHeight * $scale));
        $dstX = (int) floor((self::LOGO_SIZE - $newWidth) / 2);
        $dstY = (int) floor((self::LOGO_SIZE - $newHeight) / 2);

        imagecopyresampled(
            $canvas,
            $sourceImage,
            $dstX,
            $dstY,
            0,
            0,
            $newWidth,
            $newHeight,
            $sourceWidth,
            $sourceHeight
        );

        $saved = @imagepng($canvas, $targetPath, 6);

        imagedestroy($sourceImage);
        imagedestroy($canvas);

        return $saved;
    }

    protected function makeWhitePixelsTransparent($image)
    {
        if (! $this->canProcessImages()) {
            return $image;
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        $width = imagesx($image);
        $height = imagesy($image);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $rgba = imagecolorat($image, $x, $y);
                $alpha = ($rgba & 0x7F000000) >> 24;
                $red = ($rgba >> 16) & 0xFF;
                $green = ($rgba >> 8) & 0xFF;
                $blue = $rgba & 0xFF;

                if ($red >= 245 && $green >= 245 && $blue >= 245) {
                    $transparent = imagecolorallocatealpha($image, $red, $green, $blue, 127);
                    imagesetpixel($image, $x, $y, $transparent);

                    continue;
                }

                $current = imagecolorallocatealpha($image, $red, $green, $blue, $alpha);
                imagesetpixel($image, $x, $y, $current);
            }
        }

        return $image;
    }

    protected function ensureDefaultLogo(): void
    {
        if (! $this->ensureDirectory()) {
            return;
        }

        $defaultPath = $this->defaultPath();

        if (File::exists($defaultPath) || ! $this->canProcessImages()) {
            return;
        }

        $canvas = imagecreatetruecolor(self::LOGO_SIZE, self::LOGO_SIZE);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);

        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefill($canvas, 0, 0, $transparent);

        $blue = imagecolorallocate($canvas, 34, 78, 160);
        $white = imagecolorallocate($canvas, 255, 255, 255);

        imagefilledellipse($canvas, 25, 25, 42, 42, $blue);
        imagefilledpolygon($canvas, [
            14, 26,
            26, 21,
            38, 13,
            32, 24,
            37, 26,
            32, 28,
            38, 39,
            26, 31,
            14, 26,
        ], 9, $white);

        @imagepng($canvas, $defaultPath, 6);
        imagedestroy($canvas);
    }

    protected function canProcessImages(): bool
    {
        return extension_loaded('gd')
            && function_exists('imagecreatefromstring')
            && function_exists('imagecreatetruecolor')
            && function_exists('imagepng')
            && function_exists('imagesavealpha')
            && function_exists('imagealphablending')
            && function_exists('imagecopyresampled')
            && function_exists('imagesetpixel')
            && function_exists('imagefilledellipse')
            && function_exists('imagefilledpolygon');
    }

    protected function ensureDirectory(): bool
    {
        try {
            if (! File::isDirectory($this->directoryPath())) {
                File::makeDirectory($this->directoryPath(), 0755, true);
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function logoPath(string $iata): string
    {
        return $this->directoryPath().DIRECTORY_SEPARATOR.strtoupper($iata).'.png';
    }

    protected function defaultPath(): string
    {
        return $this->directoryPath().DIRECTORY_SEPARATOR.'default.png';
    }

    protected function defaultRelativePath(): string
    {
        return $this->relativeDirectory.'/default.png';
    }

    protected function directoryPath(): string
    {
        return public_path($this->relativeDirectory);
    }
}
