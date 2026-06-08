<?php
/**
 * Front-end page shell — top. Expects $pageTitle (optional) and a prior bootstrap include.
 */
$siteName = Setting::get('site_name', config('site.name', 'SunPlex'));
$navCategories = Category::active();
$me = current_user();
$fullTitle = isset($pageTitle) && $pageTitle !== '' ? "$pageTitle — $siteName" : $siteName;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($fullTitle) ?></title>
    <?= favicon_tag() ?>
    <link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
    <?= $headExtra ?? '' ?>
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<header class="site-header">
    <div class="wrap header-inner">
        <?php $siteLogo = Setting::get('site_logo', ''); ?>
        <a class="brand" href="<?= e(url('')) ?>">
            <?php if ($siteLogo): ?>
                <img class="brand-logo" src="<?= e($siteLogo) ?>" alt="<?= e($siteName) ?>">
            <?php else: ?>
                <span class="brand-sun">Sun</span><span class="brand-plex">Plex</span>
            <?php endif; ?>
        </a>
        <nav class="main-nav">
            <a href="<?= e(url('')) ?>">Home</a>
            <?php foreach ($navCategories as $cat): ?>
                <a href="<?= e(url('category.php?cat=' . urlencode($cat['slug']))) ?>"><?= e($cat['name']) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="header-auth">
            <?php if ($me): ?>
                <a href="<?= e(url('account.php')) ?>" class="btn btn-ghost"><?= e($me['name']) ?></a>
                <?php if ($me['role'] === 'admin'): ?>
                    <a href="<?= e(url('admin/')) ?>" class="btn btn-ghost">Admin</a>
                <?php endif; ?>
                <a href="<?= e(url('logout.php')) ?>" class="btn btn-outline">Logout</a>
            <?php else: ?>
                <a href="<?= e(url('login.php')) ?>" class="btn btn-ghost">Login</a>
                <a href="<?= e(url('register.php')) ?>" class="btn btn-primary">Sign Up</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="wrap site-main">
<?php foreach (flash_take() as $f): ?>
    <div class="flash flash-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
<?php endforeach; ?>
