<?php
declare(strict_types=1);

namespace App\Multisite;

/** The one source of truth for "which site is this request for". Never populated from client input. */
final class TenantContext
{
    private ?array $site = null;

    public function set(array $site): void
    {
        $this->site = $site;
    }

    public function has(): bool
    {
        return $this->site !== null;
    }

    public function site(): array
    {
        return $this->site ?? throw new \LogicException('No tenant in context');
    }

    public function id(): int
    {
        return (int) $this->site()['id'];
    }
}
