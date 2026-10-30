<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\{Page, Request, Response};
use App\Repositories\SettingsRepository;

final class SettingsController
{
    public function __construct(private Page $page, private SettingsRepository $settings)
    {
    }

    public function form(Request $req, array $p): Response
    {
        return $this->page->render('admin/settings', ['values' => $this->settings->all(), 'errors' => [], 'title' => 'Réglages']);
    }

    public function save(Request $req, array $p): Response
    {
        $in = $req->post;
        $v = [];
        foreach (SettingsRepository::KEYS as $k) {
            $v[$k] = trim((string) ($in[$k] ?? ''));
        }
        $e = [];
        if ($v['email'] !== '' && !filter_var($v['email'], FILTER_VALIDATE_EMAIL)) {
            $e['email'] = 'Email invalide.';
        }
        if ($v['whatsapp'] !== '' && !preg_match('/^\+?[0-9 ]{8,20}$/', $v['whatsapp'])) {
            $e['whatsapp'] = 'Numéro WhatsApp invalide (chiffres, indicatif inclus).';
        }
        if ($v['theme'] !== '' && !isset(SettingsRepository::THEMES[$v['theme']])) {
            $e['theme'] = 'Thème inconnu.';
        }
        if ($v['logo_url'] !== '' && !preg_match('#^/(uploads|assets)/[A-Za-z0-9_./-]+$#', $v['logo_url'])) {
            $e['logo_url'] = 'Logo : chemin interne attendu (/uploads/… ou /assets/…).';
        }
        if (strlen($v['meta_description']) > 300 || strlen($v['address']) > 300 || strlen($v['tagline']) > 190 || strlen($v['phone']) > 40) {
            $e['meta_description'] = 'Un champ est trop long.';
        }
        if ($e) {
            return $this->page->render('admin/settings', ['values' => $v, 'errors' => $e, 'title' => 'Réglages'], 422);
        }
        foreach ($v as $k => $val) {
            $this->settings->set($k, $val);
        }
        $this->page->flash('Réglages enregistrés.');
        return Response::redirect('/admin/settings');
    }
}
