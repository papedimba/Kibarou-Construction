<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\{Page, Request, Response};
use App\Repositories\{ContentRepository, PageRepository, PageRevisionRepository, PostRepository};
use App\Services\AuthService;

/** Pages (/admin/pages, with revisions) and articles (/admin/articles) share this controller. */
final class ContentController
{
    public function __construct(private Page $page, private PageRepository $pages, private PostRepository $posts, private PageRevisionRepository $revisions, private AuthService $auth)
    {
    }

    public function index(Request $req, array $p): Response
    {
        [$repo, $kind] = $this->ctx($req);
        return $this->page->render('admin/content_list', ['r' => $repo->paginate(false, (int) $req->input('page', 1)), 'kind' => $kind, 'title' => $this->label($kind)]);
    }

    public function create(Request $req, array $p): Response
    {
        return $this->form($req, ['status' => 'draft'], [], null);
    }

    public function edit(Request $req, array $p): Response
    {
        [$repo] = $this->ctx($req);
        $row = $repo->find((int) $p['id']);
        return $row ? $this->form($req, $row, [], (int) $row['id']) : $this->notFound();
    }

    public function store(Request $req, array $p): Response
    {
        [$repo, $kind] = $this->ctx($req);
        [$data, $errors] = $this->collect($req, $repo, null);
        if ($errors) {
            return $this->form($req, $req->post, $errors, null);
        }
        $repo->insert($data);
        $this->page->flash('Enregistré.');
        return Response::redirect("/admin/$kind");
    }

    public function update(Request $req, array $p): Response
    {
        [$repo, $kind] = $this->ctx($req);
        $id = (int) $p['id'];
        $old = $repo->find($id);
        if (!$old) {
            return $this->notFound();
        }
        [$data, $errors] = $this->collect($req, $repo, $id, $old);
        if ($errors) {
            return $this->form($req, $req->post, $errors, $id);
        }
        $this->snapshot($kind, $old);
        $repo->update($id, $data);
        $this->page->flash('Enregistré.');
        return Response::redirect("/admin/$kind");
    }

    public function destroy(Request $req, array $p): Response
    {
        [$repo, $kind] = $this->ctx($req);
        if (!$repo->delete((int) $p['id'])) {
            return $this->notFound();
        }
        $this->page->flash('Supprimé.');
        return Response::redirect("/admin/$kind");
    }

    public function restore(Request $req, array $p): Response
    {
        $old = $this->pages->find((int) $p['id']);
        $rev = $this->revisions->find((int) $p['rev']);
        if (!$old || !$rev || (int) $rev['page_id'] !== (int) $old['id']) {
            return $this->notFound();
        }
        $this->snapshot('pages', $old); // restoring is itself undoable
        // blocks go back to the *draft*: restoring never changes what visitors see until it is published again
        $this->pages->update((int) $old['id'], ['title' => $rev['title'], 'body' => $rev['body']] + ($rev['blocks'] !== null ? ['draft_blocks' => $rev['blocks']] : []));
        $this->page->flash('Version restaurée dans le brouillon.');
        $to = $req->input('return') === 'editor' ? 'editeur' : 'edit';
        return Response::redirect('/admin/pages/' . (int) $old['id'] . "/$to");
    }

    /** @return array{0:ContentRepository,1:string} */
    private function ctx(Request $req): array
    {
        return str_starts_with($req->path, '/admin/articles') ? [$this->posts, 'articles'] : [$this->pages, 'pages'];
    }

    private function label(string $kind): string
    {
        return $kind === 'pages' ? 'Pages' : 'Articles';
    }

    private function snapshot(string $kind, array $old): void
    {
        if ($kind === 'pages') {
            $this->revisions->insert(['page_id' => $old['id'], 'title' => $old['title'], 'body' => $old['body'], 'blocks' => $old['blocks'] ?? null, 'created_by' => $this->auth->user()['id'] ?? null]);
        }
    }

    private function form(Request $req, array $row, array $errors, ?int $id): Response
    {
        [, $kind] = $this->ctx($req);
        return $this->page->render('admin/content_form', [
            'row' => $row, 'errors' => $errors, 'id' => $id, 'kind' => $kind, 'title' => $this->label($kind),
            'revisions' => $id && $kind === 'pages' ? $this->revisions->forPage($id) : [],
        ], $errors ? 422 : 200);
    }

    private function notFound(): Response
    {
        return $this->page->render('errors/message', ['title' => 'Introuvable', 'message' => 'Ce contenu n\'existe pas.'], 404);
    }

    /** @return array{0:array,1:array<string,string>} */
    private function collect(Request $req, ContentRepository $repo, ?int $id, ?array $old = null): array
    {
        $in = $req->post;
        $e = [];
        $title = trim((string) ($in['title'] ?? ''));
        $slug = $repo->slugFor(trim((string) ($in['slug'] ?? '')), $title);
        $status = $in['status'] ?? '';
        if ($title === '' || mb_strlen($title) > 190) {
            $e['title'] = 'Titre requis (190 caractères max).';
        }
        if (in_array($slug, ContentRepository::RESERVED, true) && $repo instanceof PageRepository) {
            $e['slug'] = 'Cette adresse est réservée.';
        } elseif ($repo->slugTaken($slug, $id)) {
            $e['slug'] = 'Cette adresse est déjà utilisée.';
        }
        in_array($status, ['draft', 'published'], true) or $e['status'] = 'Statut invalide.';
        mb_strlen((string) ($in['body'] ?? '')) <= 100000 or $e['body'] = 'Contenu trop long.';
        mb_strlen((string) ($in['meta_title'] ?? '')) <= 190 or $e['meta_title'] = 'Titre SEO trop long.';
        mb_strlen((string) ($in['meta_description'] ?? '')) <= 300 or $e['meta_description'] = 'Description SEO trop longue (300 max).';
        $publishedAt = $old['published_at'] ?? null;
        if ($status === 'published' && !$publishedAt) {
            $publishedAt = date('Y-m-d H:i:s');
        }
        return [[
            'slug' => $slug, 'title' => $title, 'body' => (string) ($in['body'] ?? ''), 'status' => $status, 'published_at' => $publishedAt,
            'meta_title' => trim((string) ($in['meta_title'] ?? '')) ?: null, 'meta_description' => trim((string) ($in['meta_description'] ?? '')) ?: null,
        ], $e];
    }
}
