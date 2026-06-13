<?php
/** Channels table rows — shared by the full page and the AJAX search response.
 *  Expects $channels and $q in scope. */
?>
<?php foreach ($channels as $c): ?>
    <tr class="ch-row" draggable="true" data-id="<?= (int) $c['id'] ?>" data-cat="<?= (int) $c['category_id'] ?>">
        <td class="drag-handle" title="Drag to reorder">⠿</td>
        <td><input type="checkbox" class="ch-check" name="ids[]" value="<?= (int) $c['id'] ?>" form="bulkChForm"></td>
        <td><?= e($c['name']) ?></td>
        <td class="muted"><?= e($c['category_name']) ?></td>
        <td><?= e(strtoupper($c['stream_type'])) ?></td>
        <td><?= (int) $c['is_live'] ? 'Yes' : 'No' ?></td>
        <td><?= (int) $c['is_premium'] ? '<span class="tag tag-prem">Premium</span>' : '—' ?></td>
        <td><?= $c['status'] === 'active' ? '<span class="tag tag-on">active</span>' : '<span class="tag tag-off">inactive</span>' ?></td>
        <td><div class="row-actions">
            <a href="<?= e(url('admin/channels.php?action=edit&id=' . $c['id'])) ?>" class="btn btn-outline btn-sm">Edit</a>
            <form method="post" action="<?= e(url('admin/channels.php')) ?>" data-confirm="Delete &quot;<?= e($c['name']) ?>&quot;? This cannot be undone.">
                <?= csrf_field() ?>
                <input type="hidden" name="op" value="delete">
                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                <button class="btn btn-danger btn-sm">Delete</button>
            </form>
        </div></td>
    </tr>
<?php endforeach; ?>
<?php if (!$channels): ?><tr><td colspan="9" class="muted"><?= $q !== '' ? 'No channels match “' . e($q) . '”.' : 'No channels yet.' ?></td></tr><?php endif; ?>
