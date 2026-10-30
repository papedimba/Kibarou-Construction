<?php
declare(strict_types=1);

namespace App\Controllers\Frontend;

use App\Core\{Page, Request, Response};
use App\Repositories\LandRepository;

final class HomeController
{
    public function __construct(private Page $page, private LandRepository $lands)
    {
    }

    public function index(Request $req, array $p): Response
    {
        return $this->page->render('frontend/home', ['featured' => $this->lands->search(['sort' => 'recent'], true, 1, 3)['items']]);
    }
}
