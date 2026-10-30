<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Request, Response};
use App\Multisite\TenantContext;
use App\Repositories\{LandImageRepository, LocationRepository, SettingsRepository};

/** Builds the PDF sheet of a land from its own tenant's data (photos, logo, contact settings). */
final class LandSheet
{
    public function __construct(
        private FichePdf $pdf, private LandImageRepository $images, private LocationRepository $locations,
        private SettingsRepository $settings, private TenantContext $tenant
    ) {
    }

    public function response(array $land, Request $req): Response
    {
        $public = dirname(__DIR__, 2) . '/public';
        $photos = array_map(fn($i) => "$public/uploads/{$this->tenant->id()}/" . basename($i['filename']), $this->images->forOwner((int) $land['id']));
        $settings = $this->settings->all();
        $logo = null;
        if (!empty($settings['logo_url'])) {
            $real = realpath($public . $settings['logo_url']);
            $logo = $real && str_starts_with($real, realpath($public) . DIRECTORY_SEPARATOR) ? $real : null; // never read outside public/
        }
        $land['location_label'] = $this->locations->label($land['location_id'] ? (int) $land['location_id'] : null);
        $bytes = $this->pdf->render($land, $photos, $settings, $this->tenant->site()['name'], ($req->secure ? 'https' : 'http') . '://' . $req->host . '/terrains/' . $land['slug'], $logo);
        return new Response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="fiche-' . preg_replace('/[^A-Za-z0-9_-]/', '_', $land['reference']) . '.pdf"',
        ]);
    }
}
