<?php
require __DIR__ . '/app/bootstrap.php';

// Subscriptions feature switched off => no plans page.
if (!subscriptions_enabled()) {
    flash('info', 'All channels are currently free for signed-in members.');
    redirect('index.php');
}

$plans = Plan::active();
$me = current_user();
$currentSub = $me ? Subscription::activeForUser((int) $me['id']) : null;
$currentPlanId = $currentSub ? (int) $currentSub['plan_id'] : null;

$pageTitle = 'Plans';
require __DIR__ . '/app/includes/header.php';
?>
<section class="plans">
    <div class="row-head"><h1>Choose your plan</h1></div>
    <p class="muted plans-note">Pick a plan to unlock premium live channels. Free channels are available to all signed-in users.</p>

    <div class="plan-grid">
        <?php foreach ($plans as $plan): ?>
            <?php $isPaid = (float) $plan['price'] > 0; $isCurrent = $currentPlanId === (int) $plan['id']; ?>
            <div class="plan-card <?= $isPaid ? 'plan-paid' : '' ?> <?= $isCurrent ? 'plan-current' : '' ?>">
                <?php if ($isCurrent): ?><span class="plan-flag">Current plan</span><?php endif; ?>
                <h2><?= e($plan['name']) ?></h2>
                <p class="plan-price">
                    <?php if ($isPaid): ?>
                        <span class="amount">$<?= e(number_format((float) $plan['price'], 2)) ?></span>
                        <span class="per">/ <?= (int) $plan['duration_days'] ?> days</span>
                    <?php else: ?>
                        <span class="amount">Free</span>
                    <?php endif; ?>
                </p>
                <?php if (!empty($plan['description'])): ?>
                    <p class="plan-desc"><?= e($plan['description']) ?></p>
                <?php endif; ?>

                <?php if ($isCurrent): ?>
                    <button class="btn btn-outline btn-block" disabled>Active</button>
                <?php elseif (!$isPaid): ?>
                    <a href="<?= e(url('register.php')) ?>" class="btn btn-outline btn-block">
                        <?= $me ? 'Included' : 'Sign up free' ?>
                    </a>
                <?php else: ?>
                    <form method="post" action="<?= e(url('subscribe.php')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="plan_id" value="<?= (int) $plan['id'] ?>">
                        <button type="submit" class="btn btn-primary btn-block">Choose <?= e($plan['name']) ?></button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <p class="muted plans-disclaimer">
        Demo activation: no payment gateway is connected in this build, so choosing a paid plan
        activates it immediately without charge. Payment integration is planned separately.
    </p>
</section>
<?php require __DIR__ . '/app/includes/footer.php'; ?>
