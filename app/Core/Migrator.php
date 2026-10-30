<?php
declare(strict_types=1);

namespace App\Core;

final class Migrator
{
    public function __construct(private Database $db, private string $dir)
    {
    }

    /** @return list<string> applied migration names */
    public function run(): array
    {
        $this->db->run('CREATE TABLE IF NOT EXISTS migrations (name VARCHAR(190) PRIMARY KEY, applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)');
        $done = array_column($this->db->all('SELECT name FROM migrations'), 'name');
        $applied = [];
        $files = glob($this->dir . '/*.sql');
        sort($files);
        foreach ($files as $file) {
            $name = basename($file);
            if (in_array($name, $done, true)) {
                continue;
            }
            $sql = preg_replace('/^\s*--.*$/m', '', file_get_contents($file));
            foreach (array_filter(array_map('trim', explode(";\n", $sql . "\n"))) as $stmt) {
                $this->db->pdo()->exec($stmt); // DDL auto-commits in MySQL: no transaction here
            }
            $this->db->run('INSERT INTO migrations (name) VALUES (?)', [$name]);
            $applied[] = $name;
        }
        return $applied;
    }
}
