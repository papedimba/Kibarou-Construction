<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\{Page, Request, Response};
use App\Repositories\LandRepository;

final class DashboardController
{
    public function __construct(private Page $page, private LandRepository $lands)
    {
    }

    public function index(Request $req, array $p): Response
    {
        $c = $this->lands->countsByStatus();
        return $this->page->render('admin/dashboard', ['counts' => $c, 'total' => array_sum($c)]);
    }
}
