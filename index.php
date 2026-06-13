<?php
/**
 * Home page = full FWD UVP player with an "All Channels" right-side playlist
 * (and, if the categories dropdown is enabled in admin, per-category playlists).
 */
require __DIR__ . '/app/bootstrap.php';

$categories = Category::active();

// Per-category playlists (category dropdown) — grouped by category order.
$catChannels = [];
foreach ($categories as $cat) {
    $chs = Channel::activeByCategory((int) $cat['id']);
    if ($chs) {
        $catChannels[(int) $cat['id']] = ['cat' => $cat, 'channels' => $chs];
    }
}
// Main "All Channels" playlist — flat global order set by channel drag-reorder.
$allChannels = Channel::playlistActive();

$pageTitle = '';
$bodyClass = 'page-home';
$headExtra = player_head_assets();
require __DIR__ . '/app/includes/header.php';

if ($allChannels) {
    $pp_channels = $allChannels;
    $pp_groups   = $catChannels;
    $pp_name     = 'All Channels';
    $pp_instance = 'sunplexHome';
    require __DIR__ . '/app/includes/channel_player.php';
} else {
    echo '<p class="empty">No channels available yet. Check back soon.</p>';
}

require __DIR__ . '/app/includes/footer.php';
