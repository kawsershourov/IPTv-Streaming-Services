<?php
declare(strict_types=1);

/** Thin data access for users. */
class User
{
    public static function find(int $id): ?array
    {
        return db_one('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public static function findByEmail(string $email): ?array
    {
        return db_one('SELECT * FROM users WHERE email = ?', [$email]);
    }

    public static function create(string $name, string $email, string $passwordHash, string $role = 'user'): int
    {
        db_run(
            'INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)',
            [$name, $email, $passwordHash, $role]
        );
        return (int) db()->lastInsertId();
    }

    public static function all(): array
    {
        return db_all('SELECT * FROM users ORDER BY created_at DESC');
    }

    /** Paginated search across name / email / role. */
    public static function searchPaged(string $q, int $limit, int $offset): array
    {
        $limit  = max(1, $limit);
        $offset = max(0, $offset);
        if ($q === '') {
            return db_all("SELECT * FROM users ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
        }
        $like = '%' . $q . '%';
        return db_all(
            "SELECT * FROM users WHERE name LIKE ? OR email LIKE ? OR role LIKE ?
             ORDER BY created_at DESC LIMIT $limit OFFSET $offset",
            [$like, $like, $like]
        );
    }

    public static function searchCount(string $q): int
    {
        if ($q === '') {
            return self::count();
        }
        $like = '%' . $q . '%';
        return (int) (db_one(
            'SELECT COUNT(*) AS c FROM users WHERE name LIKE ? OR email LIKE ? OR role LIKE ?',
            [$like, $like, $like]
        )['c'] ?? 0);
    }

    public static function updatePassword(int $id, string $passwordHash): void
    {
        db_run('UPDATE users SET password_hash = ? WHERE id = ?', [$passwordHash, $id]);
    }

    public static function setStatus(int $id, string $status): void
    {
        db_run('UPDATE users SET status = ? WHERE id = ?', [$status, $id]);
    }

    public static function setRole(int $id, string $role): void
    {
        db_run('UPDATE users SET role = ? WHERE id = ?', [$role, $id]);
    }

    public static function count(): int
    {
        return (int) (db_one('SELECT COUNT(*) AS c FROM users')['c'] ?? 0);
    }

    public static function countAdmins(): int
    {
        return (int) (db_one("SELECT COUNT(*) AS c FROM users WHERE role = 'admin'")['c'] ?? 0);
    }

    public static function delete(int $id): void
    {
        db_run('DELETE FROM users WHERE id = ?', [$id]);
    }
}
