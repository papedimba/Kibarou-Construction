<?php
declare(strict_types=1);

namespace App\Services;

use chillerlan\QRCode\{QRCode, QROptions};
use chillerlan\QRCode\Output\{QRGdImagePNG, QRMarkupSVG};

final class QrService
{
    /** @return array{0:string,1:string} [bytes, mime] */
    public function render(string $url, string $format): array
    {
        if ($format === 'png') {
            $o = new QROptions(['outputInterface' => QRGdImagePNG::class, 'outputBase64' => false, 'scale' => 12, 'quietzoneSize' => 4]);
            return [(new QRCode($o))->render($url), 'image/png'];
        }
        $o = new QROptions(['outputInterface' => QRMarkupSVG::class, 'outputBase64' => false, 'quietzoneSize' => 4]);
        return [(new QRCode($o))->render($url), 'image/svg+xml'];
    }
}
