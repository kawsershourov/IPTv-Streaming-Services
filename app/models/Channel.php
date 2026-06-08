<?php
declare(strict_types=1);

/** Thin data access for channels. */
class Channel
{
    /** Active channels for a category id. */
    public static function activeByCategory(int $categoryId): array
    {
        return db_all(
            'SELECT * FROM channels WHERE category_id = ? AND status = "active" ORDER BY sort_order, name',
            [$categoryId]
        );
    }

    /** Active channels grouped under each active category: [['category'=>..,'channels'=>[..]], ..]. */
    public static function groupedByCategory(): array
    {
        $groups = [];
        foreach (Category::active() as $cat) {
            $groups[] = [
                'category' => $cat,
                'channels' => self::activeByCategory((int) $cat['id']),
            ];
        }
        return $groups;
    }

    public static function find(int $id): ?array
    {
        return db_one('SELECT * FROM channels WHERE id = ?', [$id]);
    }

    public static function findBySlug(string $slug): ?array
    {
        return db_one('SELECT * FROM channels WHERE slug = ?', [$slug]);
    }

    /** All channels with category name (admin listing). */
    public static function allWithCategory(): array
    {
        return db_all(
            'SELECT ch.*, c.name AS category_name
               FROM channels ch
               JOIN categories c ON c.id = ch.category_id
           ORDER BY c.sort_order, ch.sort_order, ch.name'
        );
    }

    public static function create(array $d): int
    {
        db_run(
            'INSERT INTO channels
                (category_id, name, slug, logo, stream_url, stream_type, is_live, is_premium, sort_order, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                (int) $d['category_id'], $d['name'], $d['slug'], $d['logo'] ?? null,
                $d['stream_url'], $d['stream_type'] ?? 'hls',
                (int) ($d['is_live'] ?? 1), (int) ($d['is_premium'] ?? 0),
                (int) ($d['sort_order'] ?? 0), $d['status'] ?? 'active',
            ]
        );
        return (int) db()->lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        db_run(
            'UPDATE channels SET
                category_id = ?, name = ?, slug = ?, logo = ?, stream_url = ?, stream_type = ?,
                is_live = ?, is_premium = ?, sort_order = ?, status = ?
             WHERE id = ?',
            [
                (int) $d['category_id'], $d['name'], $d['slug'], $d['logo'] ?? null,
                $d['stream_url'], $d['stream_type'] ?? 'hls',
                (int) ($d['is_live'] ?? 1), (int) ($d['is_premium'] ?? 0),
                (int) ($d['sort_order'] ?? 0), $d['status'] ?? 'active', $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        db_run('DELETE FROM channels WHERE id = ?', [$id]);
    }

    public static function count(): int
    {
        return (int) (db_one('SELECT COUNT(*) AS c FROM channels')['c'] ?? 0);
    }
}
