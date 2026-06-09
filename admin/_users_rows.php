<?php
/** Users table rows — shared by the full page and the AJAX search response.
 *  Expects $users, $plans, $me, $q in scope. */
?>
<?php foreach ($users as $u): ?>
    <?php $sub = Subscription::activeForUser((int) $u['id']); $self = (int) $u['id'] === (int) $me['id']; ?>
    <tr>
        <td><?php if (!$self): ?><input type="checkbox" class="user-check" name="ids[]" value="<?= (int) $u['id'] ?>" form="bulkUserForm"><?php endif; ?></td>
        <td><?= e($u['name']) ?><?= $self ? ' <span class="muted">(you)</span>' : '' ?></td>
        <td class="muted"><?= e($u['email']) ?></td>
        <td>
            <?php if ($self): ?>
                <span class="tag tag-prem"><?= e($u['role']) ?></span>
            <?php else: ?>
                <form method="post" action="<?= e(url('admin/users.php')) ?>" style="display:flex;gap:6px;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="op" value="role">
                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                    <select name="role" class="mini-select" onchange="this.form.submit()">
                        <?php foreach (['user', 'editor', 'admin'] as $r): ?>
                            <option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= $r ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php endif; ?>
        </td>
        <td><?= $u['status'] === 'active' ? '<span class="tag tag-on">active</span>' : '<span class="tag tag-off">suspended</span>' ?></td>
        <td>
            <?php if ($sub): ?>
                <?= e($sub['plan_name']) ?> <span class="muted">· ends <?= e(fmt_date($sub['ends_at'])) ?></span>
            <?php else: ?><span class="muted">—</span><?php endif; ?>
        </td>
        <td>
            <form method="post" action="<?= e(url('admin/users.php')) ?>" style="display:flex;gap:6px;">
                <?= csrf_field() ?>
                <input type="hidden" name="op" value="grant">
                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                <select name="plan_id" class="mini-select" required>
                    <option value="">plan…</option>
                    <?php foreach ($plans as $p): ?>
                        <option value="<?= (int) $p['id'] ?>"><?= e($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-primary btn-sm">Grant</button>
            </form>
        </td>
        <td><div class="row-actions">
            <?php if (!$self): ?>
                <form method="post" action="<?= e(url('admin/users.php')) ?>">
                    <?= csrf_field() ?><input type="hidden" name="op" value="status"><input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                    <button class="btn btn-outline btn-sm"><?= $u['status'] === 'active' ? 'Suspend' : 'Activate' ?></button>
                </form>
                <form method="post" action="<?= e(url('admin/users.php')) ?>" data-confirm="Delete &quot;<?= e($u['name']) ?>&quot;? This cannot be undone.">
                    <?= csrf_field() ?><input type="hidden" name="op" value="delete"><input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                    <button class="btn btn-danger btn-sm">Delete</button>
                </form>
            <?php else: ?><span class="muted">—</span><?php endif; ?>
        </div></td>
    </tr>
<?php endforeach; ?>
<?php if (!$users): ?><tr><td colspan="8" class="muted"><?= $q !== '' ? 'No users match “' . e($q) . '”.' : 'No users yet.' ?></td></tr><?php endif; ?>
