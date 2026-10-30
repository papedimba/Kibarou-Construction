<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\{Page, Request, Response};
use App\Repositories\{MenuRepository, RedirectRepository};

/** Menus and 301 redirects: small navigation-related lists, managed on one screen. */
final class MenuController
{
    public function __construct(private Page $page, private MenuRepository $menus, private RedirectRepository $redirects)
    {
    }

    public function index(Request $req, array $p): Response
    {
        $lists = [];
        foreach (MenuRepository::LOCATIONS as $loc => $label) {
            $lists[$loc] = ['label' => $label, 'items' => $this->menus->items($loc)];
        }
        return $this->page->render('admin/menus', ['lists' => $lists, 'redirects' => $this->redirects->all(), 'title' => 'Menus']);
    }

    public function addItem(Request $req, array $p): Response
    {
        $loc = (string) $req->input('location');
        $label = trim((string) $req->input('label'));
        $url = trim((string) $req->input('url'));
        if (!isset(MenuRepository::LOCATIONS[$loc]) || $label === '' || mb_strlen($label) > 100 || !MenuRepository::validUrl($url)) {
            $this->page->flash('Libellé ou adresse invalide (chemin "/…" ou http(s)://).');
        } else {
            $this->menus->add($loc, $label, $url);
            $this->page->flash('Lien ajouté.');
        }
        return Response::redirect('/admin/menus');
    }

    public function deleteItem(Request $req, array $p): Response
    {
        $this->menus->delete((int) $p['id']);
        return Response::redirect('/admin/menus');
    }

    public function addRedirect(Request $req, array $p): Response
    {
        $from = trim((string) $req->input('from_path'));
        $to = trim((string) $req->input('to_url'));
        if (!preg_match('~^/[^\s?#]*$~', $from) || !MenuRepository::validUrl($to) || $from === $to) {
            $this->page->flash('Redirection invalide.');
        } else {
            try {
                $this->redirects->insert(['from_path' => $from, 'to_url' => $to]);
                $this->page->flash('Redirection ajoutée.');
            } catch (\PDOException $e) {
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
                $this->page->flash('Cette redirection existe déjà.');
            }
        }
        return Response::redirect('/admin/menus');
    }

    public function deleteRedirect(Request $req, array $p): Response
    {
        $this->redirects->delete((int) $p['id']);
        return Response::redirect('/admin/menus');
    }
}
