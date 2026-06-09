<?php
declare(strict_types=1);

/** Thin data access for the admin media library. */
class Media
{
    public static function create(array $d): int
    {
        db_run(
            'INSERT INTO media (filename, url, mime, size, uploaded_by) VALUES (?, ?, ?, ?, ?)',
            [$d['filename'], $d['url'], $d['mime'], (int) ($d['size'] ?? 0), $d['uploaded_by'] ?? null]
        );
        return (int) db()->lastInsertId();
    }

    public static function paged(int $limit, int $offset): array
    {
        $limit  = max(1, $limit);
        $offset = max(0, $offset);
        return db_all("SELECT * FROM media ORDER BY created_at DESC, id DESC LIMIT $limit OFFSET $offset");
    }

    public static function count(): int
    {
        return (int) (db_one('SELECT COUNT(*) AS c FROM media')['c'] ?? 0);
    }

    public static function find(int $id): ?array
    {
        return db_one('SELECT * FROM media WHERE id = ?', [$id]);
    }

    /** Delete the DB row and the file on disk. */
    public static function delete(int $id): void
    {
        $row = self::find($id);
        if ($row) {
            $path = BASE_DIR . '/uploads/media/' . basename($row['filename']);
            if (is_file($path)) {
                @unlink($path);
            }
            db_run('DELETE FROM media WHERE id = ?', [$id]);
        }
    }
}
