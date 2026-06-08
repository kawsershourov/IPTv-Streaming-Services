<?php
require __DIR__ . '/app/bootstrap.php';

$groups = Channel::groupedByCategory();

$pageTitle = '';
require __DIR__ . '/app/includes/header.php';
?>
<section class="hero">
    <div class="hero-inner">
        <h1>Live TV, Sports &amp; Entertainment</h1>
        <p>Stream hundreds of live channels — sports, news, movies and local favourites — in one place.</p>
        <?php if (!current_user()): ?>
            <a href="<?= e(url('register.php')) ?>" class="btn btn-primary btn-lg">Start Watching</a>
            <?php if (subscriptions_enabled()): ?>
                <a href="<?= e(url('plans.php')) ?>" class="btn btn-outline btn-lg">View Plans</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php foreach ($groups as $group): ?>
    <?php if (empty($group['channels'])) { continue; } ?>
    <section class="channel-row">
        <div class="row-head">
            <h2><?= e($group['category']['name']) ?></h2>
            <a class="row-more" href="<?= e(url('category.php?cat=' . urlencode($group['category']['slug']))) ?>">
                See all &rsaquo;
            </a>
        </div>
        <div class="card-scroller">
            <?php foreach ($group['channels'] as $channel): ?>
                <?php require __DIR__ . '/app/includes/channel_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endforeach; ?>

<?php if (!array_filter($groups, fn ($g) => !empty($g['channels']))): ?>
    <p class="empty">No channels available yet. Check back soon.</p>
<?php endif; ?>

<?php require __DIR__ . '/app/includes/footer.php'; ?>
