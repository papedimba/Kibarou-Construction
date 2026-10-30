<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Blocks\{BlockRegistry, BlockRenderer, BlockSanitizer, BlockStorage, Icons};
use App\Core\{Page, Request, Response};
use App\Repositories\{MediaRepository, PageRepository, PageRevisionRepository};
use App\Services\AuthService;

/** Visual block editor for pages. Saving writes the draft; only publishing changes what visitors see. */
final class EditorController
{
    public function __construct(
        private Page $page, private PageRepository $pages, private PageRevisionRepository $revisions, private BlockSanitizer $sanitizer,
        private BlockRenderer $renderer, private MediaRepository $media, private AuthService $auth
    ) {
    }

    public function editor(Request $req, array $p): Response
    {
        $row = $this->pages->find((int) $p['id']);
        if (!$row) {
            return $this->notFound($req);
        }
        $id = (int) $row['id'];
        $data = [
            'registry' => BlockRegistry::toClient(), 'categories' => BlockRegistry::CATEGORIES, 'icons' => Icons::names(),
            'blocks' => BlockStorage::decode($row['draft_blocks'] ?: $row['blocks']),
            'media' => array_map(fn($m) => ['id' => (int) $m['id'], 'label' => ($m['alt'] ?: $m['original_name']), 'url' => '/uploads/' . (int) $row['site_id'] . '/' . $m['filename']], $this->media->all()),
            'urls' => ['save' => "/admin/pages/$id/blocs", 'publish' => "/admin/pages/$id/publier", 'render' => '/admin/editeur/rendu', 'preview' => "/admin/pages/$id/apercu"],
        ];
        return $this->page->render('admin/editor', [
            'row' => $row, 'editorData' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP),
            'revisions' => $this->revisions->forPage($id), 'hasDraft' => $row['draft_blocks'] !== null && $row['draft_blocks'] !== $row['blocks'],
            'title' => 'Éditeur — ' . $row['title'], 'bodyClass' => 'editor-page', 'extraCss' => ['/assets/css/editor.css'],
        ]);
    }

    public function save(Request $req, array $p): Response
    {
        $row = $this->pages->find((int) $p['id']);
        if (!$row) {
            return Response::json(['ok' => false, 'error' => 'Page introuvable.'], 404);
        }
        // A missing/garbled "blocks" must never be read as "empty page": that would silently wipe the draft.
        if (!is_array($req->post['blocks'] ?? null)) {
            return Response::json(['ok' => false, 'error' => 'Données invalides : rien n\'a été enregistré.'], 400);
        }
        $blocks = $this->sanitizer->sanitize($req->post['blocks']);
        $this->pages->update((int) $row['id'], ['draft_blocks' => BlockStorage::encode($blocks)]);
        return Response::json(['ok' => true, 'blocks' => $blocks]);
    }

    public function publish(Request $req, array $p): Response
    {
        $row = $this->pages->find((int) $p['id']);
        if (!$row) {
            return Response::json(['ok' => false, 'error' => 'Page introuvable.'], 404);
        }
        if (!is_array($req->post['blocks'] ?? null)) {
            return Response::json(['ok' => false, 'error' => 'Données invalides : rien n\'a été publié.'], 400);
        }
        $json = BlockStorage::encode($blocks = $this->sanitizer->sanitize($req->post['blocks']));
        $this->revisions->insert(['page_id' => $row['id'], 'title' => $row['title'], 'body' => $row['body'], 'blocks' => $row['blocks'], 'created_by' => $this->auth->user()['id'] ?? null]);
        $this->pages->update((int) $row['id'], ['draft_blocks' => $json, 'blocks' => $json, 'status' => 'published', 'published_at' => $row['published_at'] ?: date('Y-m-d H:i:s')]);
        return Response::json(['ok' => true, 'blocks' => $blocks]);
    }

    /** HTML of the canvas: the real public rendering, with selection wrappers. */
    public function render(Request $req, array $p): Response
    {
        if (!is_array($req->post['blocks'] ?? null)) {
            return Response::json(['ok' => false, 'error' => 'Données invalides.'], 400);
        }
        return Response::json(['ok' => true, 'html' => $this->renderer->render($this->sanitizer->sanitize($req->post['blocks']), true)]);
    }

    public function preview(Request $req, array $p): Response
    {
        $row = $this->pages->find((int) $p['id']);
        if (!$row) {
            return $this->notFound($req);
        }
        $html = '<div class="blocks">' . $this->renderer->render(BlockStorage::decode($row['draft_blocks'] ?: $row['blocks'])) . '</div>';
        return $this->page->render('frontend/content', ['item' => $row, 'blocksHtml' => $html, 'title' => $row['title'], 'preview' => true]);
    }

    private function notFound(Request $req): Response
    {
        return $this->page->render('errors/message', ['title' => 'Introuvable', 'message' => 'Cette page n\'existe pas.'], 404);
    }
}
