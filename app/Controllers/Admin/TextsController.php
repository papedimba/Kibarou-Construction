<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Content\SiteTexts;
use App\Core\{Page, Request, Response};
use App\Repositories\MediaRepository;

/** Texts and photos of the public site, with each text's origin (specification, provisional, customised, empty). */
final class TextsController
{
    public function __construct(private Page $page, private SiteTexts $texts, private MediaRepository $media)
    {
    }

    public function form(Request $req, array $p): Response
    {
        return $this->render(null, 200);
    }

    public function save(Request $req, array $p): Response
    {
        $texts = array_intersect_key(is_array($req->post['txt'] ?? null) ? $req->post['txt'] : [], SiteTexts::TEXTS);
        $photos = array_intersect_key(is_array($req->post['img'] ?? null) ? $req->post['img'] : [], SiteTexts::PHOTOS);
        $err = $this->texts->save($texts, $photos, fn(int $id) => $this->media->find($id) !== null);
        if ($err) {
            return $this->render($err, 422);
        }
        $this->page->flash('Textes et photos enregistrés.');
        return Response::redirect('/admin/textes');
    }

    private function render(?string $error, int $status): Response
    {
        $rows = [];
        foreach (SiteTexts::TEXTS as $k => [$group, $label, , , $max]) {
            [$origin, $value] = $this->texts->state($k);
            $rows[$group][] = compact('k', 'label', 'max', 'origin', 'value');
        }
        $photos = [];
        foreach (SiteTexts::PHOTOS as $k => [$label, $where]) {
            $photos[] = ['k' => $k, 'label' => $label, 'where' => $where, 'current' => $this->texts->mediaId($k), 'preview' => $this->texts->photo($k)];
        }
        return $this->page->render('admin/texts', ['groups' => $rows, 'photos' => $photos, 'library' => $this->media->all(), 'error' => $error, 'title' => 'Textes et photos'], $status);
    }
}
