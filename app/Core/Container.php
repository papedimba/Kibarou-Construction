<?php
declare(strict_types=1);

namespace App\Core;

/** Minimal autowiring container; every service is a singleton. */
final class Container
{
    private array $instances = [];

    public function set(string $id, object $instance): void
    {
        $this->instances[$id] = $instance;
    }

    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }
        $ref = new \ReflectionClass($id);
        $args = [];
        foreach ($ref->getConstructor()?->getParameters() ?? [] as $p) {
            $t = $p->getType();
            if ($p->isDefaultValueAvailable()) {
                $args[] = $p->getDefaultValue();
            } elseif ($t instanceof \ReflectionNamedType && !$t->isBuiltin()) {
                $args[] = $this->get($t->getName());
            } else {
                throw new \LogicException("Cannot autowire \${$p->getName()} of $id");
            }
        }
        return $this->instances[$id] = $ref->newInstanceArgs($args);
    }
}
