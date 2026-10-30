<?php
declare(strict_types=1);

namespace App\Services;

/** One-page commercial sheet for a land. FPDF is Latin-1 only, hence the cp1252 conversion. */
final class FichePdf
{
    private const BLUE = [18, 63, 120];

    /**
     * @param list<string> $imagePaths absolute paths (jpeg/png/gif/webp) of up to 3 photos
     * @param array<string,string> $settings site settings (phone, email, address, logo_url)
     */
    public function render(array $land, array $imagePaths, array $settings, string $siteName, string $url, ?string $logoPath): string
    {
        $pdf = new \FPDF('P', 'mm', 'A4');
        $pdf->SetTitle($this->t("Fiche terrain {$land['reference']}"));
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(true, 15);
        $y = 12;
        if ($logoPath && ($img = $this->usable($logoPath))) {
            $pdf->Image($img, 15, $y, 0, 22);
        }
        $pdf->SetXY(15, $y + 24);
        $pdf->SetFont('Helvetica', 'B', 18);
        $pdf->SetTextColor(...self::BLUE);
        $pdf->MultiCell(180, 8, $this->t($land['title']));
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(90, 90, 90);
        $pdf->Cell(0, 6, $this->t('Référence : ' . $land['reference'] . (!empty($land['category_name']) ? '  |  ' . $land['category_name'] : '')), 0, 1);
        $pdf->Ln(2);

        $x = 15;
        foreach (array_slice($imagePaths, 0, 3) as $p) {
            if ($img = $this->usable($p)) {
                $pdf->Image($img, $x, $pdf->GetY(), 58, 40);
                $x += 61;
            }
        }
        if ($x > 15) {
            $pdf->SetY($pdf->GetY() + 44);
        }

        $this->row($pdf, 'Superficie', number_format((int) $land['area_m2'], 0, ',', ' ') . ' m2');
        $this->row($pdf, 'Prix', number_format((int) $land['price_xof'], 0, ',', ' ') . ' FCFA');
        if ((int) $land['area_m2'] > 0) {
            $this->row($pdf, 'Prix au m2', number_format((int) round($land['price_xof'] / $land['area_m2']), 0, ',', ' ') . ' FCFA');
        }
        if (!empty($land['location_label'])) {
            $this->row($pdf, 'Localisation', $land['location_label']);
        }
        $this->row($pdf, 'Disponibilité', ['available' => 'Disponible', 'reserving' => 'En cours de réservation', 'reserved' => 'Réservé', 'sold' => 'Vendu', 'unavailable' => 'Indisponible'][$land['status']] ?? $land['status']);
        if (!empty($land['description'])) {
            $pdf->Ln(3);
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->SetTextColor(30, 30, 30);
            $pdf->MultiCell(180, 5, $this->t($land['description']));
        }

        $pdf->SetY(-40);
        $pdf->SetDrawColor(...self::BLUE);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->SetTextColor(...self::BLUE);
        $pdf->Cell(0, 7, $this->t($siteName), 0, 1);
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetTextColor(60, 60, 60);
        foreach (['phone' => 'Tél. ', 'email' => '', 'address' => ''] as $k => $prefix) {
            if (!empty($settings[$k])) {
                $pdf->Cell(0, 5, $this->t($prefix . $settings[$k]), 0, 1);
            }
        }
        $pdf->Cell(0, 5, $this->t($url), 0, 1);
        return $pdf->Output('S');
    }

    private function row(\FPDF $pdf, string $label, string $value): void
    {
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->SetTextColor(...self::BLUE);
        $pdf->Cell(45, 7, $this->t($label), 0, 0);
        $pdf->SetFont('Helvetica', '', 11);
        $pdf->SetTextColor(30, 30, 30);
        $pdf->Cell(0, 7, $this->t($value), 0, 1);
    }

    private function t(string $s): string
    {
        return iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $s) ?: '';
    }

    /** FPDF reads jpeg/png/gif only: WebP is converted through GD; unreadable files are skipped. */
    private function usable(string $path): ?string
    {
        $info = @getimagesize($path);
        if (!$info) {
            return null;
        }
        if ($info[2] === IMAGETYPE_WEBP && function_exists('imagecreatefromwebp')) {
            $im = @imagecreatefromwebp($path);
            $tmp = tempnam(sys_get_temp_dir(), 'fiche') . '.jpg';
            if (!$im || !imagejpeg($im, $tmp, 88)) {
                return null;
            }
            return $tmp;
        }
        return in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF], true) ? $path : null;
    }
}
