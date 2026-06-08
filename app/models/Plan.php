<?php
declare(strict_types=1);

/** Thin data access for subscription plans. */
class Plan
{
    public static function active(): array
    {
        return db_all('SELECT * FROM plans WHERE is_active = 1 ORDER BY sort_order, price');
    }

    public static function all(): array
    {
        return db_all('SELECT * FROM plans ORDER BY sort_order, price');
    }

    public static function find(int $id): ?array
    {
        return db_one('SELECT * FROM plans WHERE id = ?', [$id]);
    }

    public static function create(array $d): int
    {
        db_run(
            'INSERT INTO plans (name, price, duration_days, description, is_active, sort_order)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $d['name'], (float) $d['price'], (int) $d['duration_days'],
                $d['description'] ?? null, (int) ($d['is_active'] ?? 1), (int) ($d['sort_order'] ?? 0),
            ]
        );
        return (int) db()->lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        db_run(
            'UPDATE plans SET name = ?, price = ?, duration_days = ?, description = ?, is_active = ?, sort_order = ?
             WHERE id = ?',
            [
                $d['name'], (float) $d['price'], (int) $d['duration_days'],
                $d['description'] ?? null, (int) ($d['is_active'] ?? 1), (int) ($d['sort_order'] ?? 0), $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        db_run('DELETE FROM plans WHERE id = ?', [$id]);
    }
}
