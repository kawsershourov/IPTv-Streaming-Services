<?php
/**
 * Admin shell — top. Each admin page must call require_admin() before including this.
 * Expects optional $adminTitle and $activeNav (slug of the current section).
 */
$activeNav = $activeNav ?? '';
$nav = [
    ''           => ['Dashboard',  'index.php'],
    'categories' => ['Categories', 'categories.php'],
    'channels'   => ['Channels',   'channels.php'],
    'users'      => ['Users',      'users.php'],
    'plans'      => ['Plans',      'plans.php'],
    'player'     => ['Player',     'player.php'],
    'settings'   => ['Settings',   'settings.php'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($adminTitle ?? 'Admin') . ' — SunPlex Admin') ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="admin">
<header class="admin-top">
    <a class="brand" href="<?= e(url('admin/')) ?>"><span class="brand-sun">Sun</span><span class="brand-plex">Plex</span> Admin</a>
    <div class="admin-top-right">
        <a href="<?= e(url('')) ?>" target="_blank" class="btn btn-ghost">View site &nearr;</a>
        <a href="<?= e(url('admin/logout.php')) ?>" class="btn btn-outline">Logout</a>
    </div>
</header>
<div class="admin-body">
    <aside class="admin-side">
        <nav>
            <?php foreach ($nav as $slug => [$label, $file]): ?>
                <a href="<?= e(url('admin/' . $file)) ?>" class="<?= $activeNav === $slug ? 'active' : '' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
    </aside>
    <main class="admin-main">
        <?php foreach (flash_take() as $f): ?>
            <div class="flash flash-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
        <?php endforeach; ?>
