<?php
require __DIR__ . '/../app/bootstrap.php';
require_staff();
require_once APP_DIR . '/spreadsheet.php';

$columns = ['name', 'category', 'stream_url', 'stream_type', 'is_live', 'is_premium', 'logo', 'sort_order', 'status'];

// ---- CSV template download ----
if (isset($_GET['template'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="channels-template.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, $columns);
    fputcsv($out, ['T-Sports', 'Sports', 'https://example.com/tsports.m3u8', 'hls', 'yes', 'no', '', '1', 'active']);
    fputcsv($out, ['ESPN', 'Sports', 'https://example.com/espn.m3u8', 'hls', 'yes', 'yes', '', '2', 'active']);
    fclose($out);
    exit;
}

/** Truthy parser for yes/no-ish cells. */
function csv_bool(string $v, int $default): int
{
    $v = strtolower(trim($v));
    if ($v === '') {
        return $default;
    }
    return in_array($v, ['1', 'yes', 'y', 'true', 'on'], true) ? 1 : 0;
}

/** Resolve a category by slug/name, creating it if missing. Returns [id, createdBool]. */
function import_category(string $nameOrSlug): array
{
    $nameOrSlug = trim($nameOrSlug);
    $slug = slugify($nameOrSlug);
    if ($cat = Category::findBySlug($slug)) {
        return [(int) $cat['id'], false];
    }
    if ($cat = db_one('SELECT * FROM categories WHERE LOWER(name) = LOWER(?)', [$nameOrSlug])) {
        return [(int) $cat['id'], false];
    }
    $id = Category::create(['name' => $nameOrSlug, 'slug' => $slug, 'is_active' => 1, 'sort_order' => 0]);
    return [$id, true];
}

$summary = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (empty($_FILES['file']['name']) || ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        flash('error', 'Please choose a CSV or XLSX file to import.');
        redirect('admin/channels_import.php');
    }

    $ext  = strtolower(pathinfo((string) $_FILES['file']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['csv', 'txt', 'xlsx'], true)) {
        flash('error', 'Unsupported file type. Use .csv or .xlsx (Excel “Save As CSV/XLSX”).');
        redirect('admin/channels_import.php');
    }

    $rows = read_spreadsheet($_FILES['file']['tmp_name'], $ext);
    if (count($rows) < 2) {
        flash('error', 'The file has no data rows (a header row plus at least one channel are required).');
        redirect('admin/channels_import.php');
    }

    // Map header names -> column index.
    $header = array_map(static fn ($h) => strtolower(trim(str_replace([' ', '-'], '_', (string) $h))), $rows[0]);
    $map = [];
    foreach ($columns as $col) {
        $i = array_search($col, $header, true);
        if ($i !== false) {
            $map[$col] = $i;
        }
    }
    if (!isset($map['name'], $map['stream_url'])) {
        flash('error', 'Header row must include at least "name" and "stream_url" columns.');
        redirect('admin/channels_import.php');
    }

    $added = $updated = $skipped = $catsCreated = 0;
    $errors = [];
    $cell = static fn (array $row, string $col): string => isset($map[$col]) ? trim((string) ($row[$map[$col]] ?? '')) : '';

    for ($r = 1; $r < count($rows); $r++) {
        $row  = $rows[$r];
        $name = $cell($row, 'name');
        $url  = $cell($row, 'stream_url');
        $catName = $cell($row, 'category');

        if ($name === '' || $url === '') {
            $skipped++;
            $errors[] = "Row " . ($r + 1) . ": missing name or stream_url.";
            continue;
        }
        if ($catName === '') {
            $catName = 'Uncategorized';
        }

        [$catId, $created] = import_category($catName);
        if ($created) {
            $catsCreated++;
        }

        $type = strtolower($cell($row, 'stream_type')) ?: 'hls';
        if (!in_array($type, ['hls', 'dash', 'mp4', 'youtube'], true)) {
            $type = 'hls';
        }
        $status = strtolower($cell($row, 'status')) === 'inactive' ? 'inactive' : 'active';

        $data = [
            'category_id' => $catId,
            'name'        => $name,
            'slug'        => slugify($name),
            'logo'        => $cell($row, 'logo') ?: null,
            'stream_url'  => $url,
            'stream_type' => $type,
            'is_live'     => csv_bool($cell($row, 'is_live'), 1),
            'is_premium'  => csv_bool($cell($row, 'is_premium'), 0),
            'sort_order'  => (int) ($cell($row, 'sort_order') ?: 0),
            'status'      => $status,
        ];

        try {
            if ($existing = Channel::findBySlug($data['slug'])) {
                Channel::update((int) $existing['id'], $data);
                $updated++;
            } else {
                Channel::create($data);
                $added++;
            }
        } catch (PDOException $ex) {
            $skipped++;
            $errors[] = "Row " . ($r + 1) . " ({$name}): could not save.";
        }
    }

    $summary = compact('added', 'updated', 'skipped', 'catsCreated', 'errors');
    flash('success', "Import complete: {$added} added, {$updated} updated, {$skipped} skipped.");
}

$adminTitle = 'Import channels';
$activeNav  = 'channels';
require __DIR__ . '/includes/header.php';
?>
<div class="toolbar">
    <h1 style="margin:0;">Import channels</h1>
    <a href="<?= e(url('admin/channels.php')) ?>" class="btn btn-outline btn-sm">&lsaquo; Back to channels</a>
</div>

<?php if ($summary): ?>
    <div class="flash flash-info">
        Added <strong><?= (int) $summary['added'] ?></strong>,
        updated <strong><?= (int) $summary['updated'] ?></strong>,
        skipped <strong><?= (int) $summary['skipped'] ?></strong>,
        new categories <strong><?= (int) $summary['catsCreated'] ?></strong>.
    </div>
    <?php if ($summary['errors']): ?>
        <div class="table-wrap" style="margin-bottom:18px;"><table class="data"><thead><tr><th>Skipped rows</th></tr></thead><tbody>
            <?php foreach (array_slice($summary['errors'], 0, 50) as $err): ?>
                <tr><td><?= e($err) ?></td></tr>
            <?php endforeach; ?>
        </tbody></table></div>
    <?php endif; ?>
<?php endif; ?>

<div class="admin-form">
    <form method="post" action="<?= e(url('admin/channels_import.php')) ?>" enctype="multipart/form-data" class="form">
        <?= csrf_field() ?>
        <label>Channels file (.csv or .xlsx) <input type="file" name="file" accept=".csv,.xlsx,text/csv" required></label>
        <p class="muted" style="font-size:13px;margin:-6px 0 14px;">
            First row must be a header. Existing channels (matched by name) are updated; new ones are added.
            Missing categories are created automatically.
        </p>
        <div class="form-actions">
            <button class="btn btn-primary">Upload &amp; import</button>
            <a href="<?= e(url('admin/channels_import.php?template=1')) ?>" class="btn btn-outline">Download CSV template</a>
        </div>
    </form>

    <h2 style="font-size:15px;margin:20px 0 8px;">Columns</h2>
    <p class="muted" style="font-size:13px;">
        <strong>name</strong>*, <strong>category</strong> (created if new), <strong>stream_url</strong>*,
        stream_type (hls/dash/mp4/youtube), is_live (yes/no), is_premium (yes/no), logo (URL),
        sort_order, status (active/inactive). * required.
    </p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
