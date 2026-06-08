<?php
require __DIR__ . '/app/bootstrap.php';
require_login();

// Subscriptions feature switched off => no plan actions at all.
if (!subscriptions_enabled()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('plans.php');
}
csrf_verify();

$me = current_user();
$planId = (int) ($_POST['plan_id'] ?? 0);
$plan = Plan::find($planId);

if (!$plan || (int) $plan['is_active'] === 0) {
    flash('error', 'That plan is not available.');
    redirect('plans.php');
}

if ((float) $plan['price'] <= 0) {
    // Free tier needs no subscription row — access is by being signed in.
    flash('info', 'The free tier is already included with your account.');
    redirect('account.php');
}

// No payment gateway in this build: activate immediately (demo, no charge).
Subscription::grant((int) $me['id'], $planId);
flash('success', sprintf('Your %s plan is now active. Enjoy premium channels!', $plan['name']));
redirect('account.php');
