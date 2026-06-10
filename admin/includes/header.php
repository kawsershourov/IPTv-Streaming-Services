<?php
/**
 * Admin shell — top. Each admin page must call require_admin() before including this.
 * Expects optional $adminTitle and $activeNav (slug of the current section).
 */
$activeNav = $activeNav ?? '';
$nav = [
    ''           => ['Dashboard',  'index.php',     'staff'],
    'categories' => ['Categories', 'categories.php', 'staff'],
    'channels'   => ['Channels',   'channels.php',  'staff'],
    'media'      => ['Media',      'media.php',     'staff'],
    'users'      => ['Users',      'users.php',     'admin'],
    'plans'      => ['Plans',      'plans.php',     'admin'],
    'player'     => ['Player',     'player.php',    'admin'],
    'access'     => ['Access',     'access.php',    'admin'],
    'settings'   => ['Settings',   'settings.php',  'admin'],
];
$isAdminUser = is_admin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($adminTitle ?? 'Admin') . ' — SunPlex Admin') ?></title>
    <?= favicon_tag() ?>
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
            <?php foreach ($nav as $slug => [$label, $file, $level]): ?>
                <?php if ($level === 'admin' && !$isAdminUser) { continue; } ?>
                <a href="<?= e(url('admin/' . $file)) ?>" class="<?= $activeNav === $slug ? 'active' : '' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
    </aside>
    <main class="admin-main">
        <?= flash_render() ?>
