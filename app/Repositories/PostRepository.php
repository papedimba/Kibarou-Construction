<?php
declare(strict_types=1);

namespace App\Repositories;

final class PostRepository extends ContentRepository
{
    protected string $table = 'posts';
}
