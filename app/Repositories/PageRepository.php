<?php
declare(strict_types=1);

namespace App\Repositories;

final class PageRepository extends ContentRepository
{
    protected string $table = 'pages';
    protected array $columns = ['slug', 'title', 'body', 'meta_title', 'meta_description', 'status', 'published_at', 'draft_blocks', 'blocks'];
}
