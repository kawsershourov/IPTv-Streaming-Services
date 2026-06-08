<?php
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
require __DIR__ . '/app/includes/header.php';
?>
<section class="category-page">
    <div class="row-head">
        <h1><?= e($category['name']) ?></h1>
        <a class="row-more" href="<?= e(url('')) ?>">&lsaquo; Home</a>
    </div>

    <?php if ($channels): ?>
        <div class="card-grid">
            <?php foreach ($channels as $channel): ?>
                <?php require __DIR__ . '/app/includes/channel_card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="empty">No channels in this category yet.</p>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/app/includes/footer.php'; ?>
