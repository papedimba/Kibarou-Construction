<?php
declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Core\{Page, Request, Response};
use App\Repositories\{LandRepository, LeadRepository, ServiceRepository};
use App\Services\{AntiSpam, LeadService};

/** Public request form: contact, land information/visit/callback, quote. Context comes from slugs, resolved inside the current tenant only. */
final class LeadController
{
    public function __construct(private Page $page, private LandRepository $lands, private ServiceRepository $services, private AntiSpam $spam, private LeadService $leads)
    {
    }

    public function contact(Request $req, array $p): Response
    {
        return $this->form($req, ['type' => (string) $req->input('type', '')], []);
    }

    public function submit(Request $req, array $p): Response
    {
        if ($err = $this->spam->check($req)) {
            return $this->form($req, $req->post, ['_form' => $err], 429);
        }
        [$land, $service] = $this->context($req);
        $in = $req->post;
        $e = [];
        $first = trim((string) ($in['first_name'] ?? ''));
        $last = trim((string) ($in['last_name'] ?? ''));
        $phone = trim((string) ($in['phone'] ?? ''));
        $email = trim((string) ($in['email'] ?? ''));
        $message = trim((string) ($in['message'] ?? ''));
        $type = (string) ($in['type'] ?? '');
        $date = trim((string) ($in['preferred_date'] ?? ''));
        if ((string) $req->input('terrain', '') !== '' && !$land || (string) $req->input('service', '') !== '' && !$service) {
            $e['_form'] = 'Le terrain ou le service demandé n\'est plus disponible.';
        }
        if (!isset($this->types($land, $service)[$type])) {
            $e['type'] = 'Choisissez le type de demande.';
        }
        ($first === '' || mb_strlen($first) > 100) and $e['first_name'] = 'Prénom requis.';
        ($last === '' || mb_strlen($last) > 100) and $e['last_name'] = 'Nom requis.';
        preg_match('/^\+?[0-9 .()-]{8,20}$/', $phone) or $e['phone'] = 'Numéro de téléphone invalide.';
        ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190)) and $e['email'] = 'Email invalide.';
        mb_strlen($message) <= 2000 or $e['message'] = 'Message trop long (2000 caractères max).';
        ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) and $e['preferred_date'] = 'Date invalide.';
        empty($in['consent']) and $e['consent'] = 'Votre consentement est nécessaire pour traiter votre demande.';
        if ($e) {
            return $this->form($req, $in, $e, 422);
        }
        $r = $this->leads->create([
            'type' => $type, 'first_name' => $first, 'last_name' => $last, 'phone' => $phone, 'email' => $email ?: null, 'message' => $message ?: null,
            'preferred_date' => $date ?: null, 'subject' => ($land['title'] ?? $service['title'] ?? null),
        ], $land, $service, $req->ip);
        $this->page->flash("Merci, votre demande {$r['reference']} a bien été enregistrée. Nous vous recontactons rapidement.");
        return Response::redirect('/demandes/merci');
    }

    public function thanks(Request $req, array $p): Response
    {
        return $this->page->render('frontend/thanks', ['title' => 'Demande envoyée']);
    }

    /** @return array{0:?array,1:?array} */
    private function context(Request $req): array
    {
        $t = (string) $req->input('terrain', '');
        $s = (string) $req->input('service', '');
        return [$t !== '' ? $this->lands->findBySlug($t) : null, $s !== '' ? $this->services->findBySlug($s) : null];
    }

    private function types(?array $land, ?array $service): array
    {
        $all = LeadRepository::TYPES;
        $keys = $land ? ['info', 'visit', 'callback'] : ($service ? ['quote'] : ['info', 'partnership', 'other']);
        return array_intersect_key($all, array_flip($keys));
    }

    private function form(Request $req, array $old, array $errors, int $status = 200): Response
    {
        [$land, $service] = $this->context($req);
        return $this->page->render('frontend/contact', [
            'land' => $land, 'service' => $service, 'types' => $this->types($land, $service), 'old' => $old, 'errors' => $errors,
            'spam' => $this->spam->fields(), 'title' => 'Contact', 'heading' => $land ? 'Ce terrain vous intéresse' : ($service ? 'Demander un devis' : 'Nous contacter'),
        ], $status);
    }
}
