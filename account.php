<?php
require __DIR__ . '/app/bootstrap.php';
require_login();

$me  = current_user();
$sub = Subscription::activeForUser((int) $me['id']);
$isPremium = has_active_subscription((int) $me['id']);

$pageTitle = 'My Account';
require __DIR__ . '/app/includes/header.php';
?>
<section class="account">
    <h1>My Account</h1>

    <div class="account-grid">
        <div class="card">
            <h2>Profile</h2>
            <p><strong>Name:</strong> <?= e($me['name']) ?></p>
            <p><strong>Email:</strong> <?= e($me['email']) ?></p>
            <p><strong>Member since:</strong> <?= e(fmt_date($me['created_at'])) ?></p>
        </div>

        <?php if (subscriptions_enabled()): ?>
        <div class="card">
            <h2>Subscription</h2>
            <?php if ($sub): ?>
                <p><strong>Plan:</strong> <?= e($sub['plan_name']) ?>
                    <?php if ($isPremium): ?>
                        <span class="badge badge-premium">Premium</span>
                    <?php endif; ?>
                </p>
                <p><strong>Renews / expires:</strong> <?= e(fmt_date($sub['ends_at'], 'M j, Y')) ?></p>
            <?php else: ?>
                <p>You are on the <strong>Free</strong> tier — premium channels are locked.</p>
            <?php endif; ?>
            <a href="<?= e(url('plans.php')) ?>" class="btn btn-primary">
                <?= $isPremium ? 'Change plan' : 'Upgrade to Premium' ?>
            </a>
        </div>
        <?php endif; ?>
    </div>

    <p class="account-actions"><a href="<?= e(url('logout.php')) ?>" class="btn btn-outline">Log out</a></p>
</section>
<?php require __DIR__ . '/app/includes/footer.php'; ?>
