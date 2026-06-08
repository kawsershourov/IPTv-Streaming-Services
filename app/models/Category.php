<?php
declare(strict_types=1);

/** Thin data access for categories. */
class Category
{
    /** Active categories ordered for display. */
    public static function active(): array
    {
        return db_all('SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order, name');
    }

    /** All categories (admin). */
    public static function all(): array
    {
        return db_all('SELECT * FROM categories ORDER BY sort_order, name');
    }

    public static function find(int $id): ?array
    {
        return db_one('SELECT * FROM categories WHERE id = ?', [$id]);
    }

    public static function findBySlug(string $slug): ?array
    {
        return db_one('SELECT * FROM categories WHERE slug = ?', [$slug]);
    }

    public static function create(array $d): int
    {
        db_run(
            'INSERT INTO categories (name, slug, icon, sort_order, is_active) VALUES (?, ?, ?, ?, ?)',
            [$d['name'], $d['slug'], $d['icon'] ?? null, (int) ($d['sort_order'] ?? 0), (int) ($d['is_active'] ?? 1)]
        );
        return (int) db()->lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        db_run(
            'UPDATE categories SET name = ?, slug = ?, icon = ?, sort_order = ?, is_active = ? WHERE id = ?',
            [$d['name'], $d['slug'], $d['icon'] ?? null, (int) ($d['sort_order'] ?? 0), (int) ($d['is_active'] ?? 1), $id]
        );
    }

    public static function delete(int $id): void
    {
        db_run('DELETE FROM categories WHERE id = ?', [$id]);
    }

    public static function count(): int
    {
        return (int) (db_one('SELECT COUNT(*) AS c FROM categories')['c'] ?? 0);
    }
}
