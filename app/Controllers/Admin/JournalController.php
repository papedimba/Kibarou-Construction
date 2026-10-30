<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\{Database, Page, Request, Response};
use App\Multisite\TenantContext;

final class JournalController
{
    public function __construct(private Page $page, private Database $db, private TenantContext $tenant)
    {
    }

    public function index(Request $req, array $p): Response
    {
        $rows = $this->db->all(
            'SELECT a.created_at, a.action, a.ip, u.name FROM activity_logs a LEFT JOIN users u ON u.id = a.user_id WHERE a.site_id = ? ORDER BY a.id DESC LIMIT 200',
            [$this->tenant->id()]
        );
        return $this->page->render('admin/journal', ['rows' => $rows, 'title' => 'Journal d\'activité']);
    }
}
