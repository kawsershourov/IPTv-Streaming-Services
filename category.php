<?php
/**
 * Category page = same live-TV player as the home page, but the playlist shows
 * only this category's channels.
 */
require __DIR__ . '/app/bootstrap.php';

$slug = trim((string) ($_GET['cat'] ?? ''));
$category = $slug !== '' ? Category::findBySlug($slug) : null;

if (!$category || (int) $category['is_active'] === 0) {
    http_response_code(404);
    $pageTitle = 'Not found';
    require __DIR__ . '/app/includes/header.php';
    echo '<p class="empty">Category not found.</p>';
    require __DIR__ . '/app/includes/footer.php';
    exit;
}

$channels = Channel::activeByCategory((int) $category['id']);

$pageTitle = $category['name'];
$bodyClass = 'page-home';
$headExtra = player_head_assets();
require __DIR__ . '/app/includes/header.php';

if ($channels) {
    $pp_channels = $channels;
    $pp_groups   = [];                 // single playlist = this category only
    $pp_name     = $category['name'];
    $pp_instance = 'sunplexCat';
    require __DIR__ . '/app/includes/channel_player.php';
} else {
    echo '<p class="empty">No channels in this category yet.</p>';
}

require __DIR__ . '/app/includes/footer.php';
