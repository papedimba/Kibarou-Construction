<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\{Database, Session};

final class AuthService
{
    private const MAX_FAILS_PER_ACCOUNT = 5;
    private const MAX_FAILS_PER_IP = 20;
    private const WINDOW = '15 MINUTE';

    public function __construct(private Database $db, private Session $session)
    {
    }

    public function throttled(string $email, string $ip): bool
    {
        $w = self::WINDOW;
        $byEmail = (int) $this->db->value("SELECT COUNT(*) FROM login_attempts WHERE email = ? AND created_at > NOW() - INTERVAL $w", [$email]);
        $byIp = (int) $this->db->value("SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND created_at > NOW() - INTERVAL $w", [$ip]);
        return $byEmail >= self::MAX_FAILS_PER_ACCOUNT || $byIp >= self::MAX_FAILS_PER_IP;
    }

    /** @return string|null error message, null on success */
    public function attempt(string $email, string $password, string $ip): ?string
    {
        $email = strtolower(trim($email));
        if ($this->throttled($email, $ip)) {
            return 'Trop de tentatives. Réessayez dans quelques minutes.';
        }
        $user = $this->db->one("SELECT * FROM users WHERE email = ? AND status = 'active'", [$email]);
        // Always run a hash check so response time does not reveal whether the account exists.
        $ok = password_verify($password, $user['password_hash'] ?? '$2y$10$usesomesillystringforsalt.Tq9bQ7yI3q0bX2n9hEw6vJf6y6e1u');
        if (!$user || !$ok) {
            $this->db->insert('login_attempts', ['email' => $email, 'ip' => $ip]);
            return 'Identifiants incorrects.';
        }
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $this->db->run('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }
        $this->db->run('DELETE FROM login_attempts WHERE email = ?', [$email]);
        $this->session->regenerate(); // prevents session fixation
        $this->session->set('user_id', (int) $user['id']);
        return null;
    }

    /** @return string|null error message, null when the password was changed. Wrong "current password" attempts count toward the login throttle. */
    public function changePassword(array $user, string $current, string $new, string $confirm, string $ip): ?string
    {
        $row = $this->db->one('SELECT email, password_hash FROM users WHERE id = ?', [$user['id']]);
        if ($this->throttled($row['email'], $ip)) {
            return 'Trop de tentatives. Réessayez dans quelques minutes.';
        }
        if (!password_verify($current, $row['password_hash'])) {
            $this->db->insert('login_attempts', ['email' => $row['email'], 'ip' => $ip]);
            return 'Mot de passe actuel incorrect.';
        }
        if (mb_strlen($new) < 12) {
            return 'Le nouveau mot de passe doit contenir 12 caractères minimum.';
        }
        if ($new !== $confirm) {
            return 'La confirmation ne correspond pas.';
        }
        if ($new === $current) {
            return 'Choisissez un mot de passe différent de l\'actuel.';
        }
        $this->db->run('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $user['id']]);
        $this->db->run('DELETE FROM login_attempts WHERE email = ?', [$row['email']]);
        $this->session->regenerate();
        return null;
    }

    public function user(): ?array
    {
        $id = $this->session->get('user_id');
        return $id ? $this->db->one("SELECT id, email, name, is_super_admin FROM users WHERE id = ? AND status = 'active'", [$id]) : null;
    }

    public function logout(): void
    {
        $this->session->destroy();
    }
}
