<?php
declare(strict_types=1);

/**
 * Subscription / content access rules.
 */

/**
 * Is the subscription/plans feature switched on site-wide?
 * When OFF: no plans UI anywhere and every signed-in user can watch all channels
 * (premium flags are ignored). When ON: premium channels require a paid plan.
 */
function subscriptions_enabled(): bool
{
    return Setting::get('subscriptions_enabled', '1') === '1';
}

/**
 * Can guests (not logged in) watch channels?
 * When ON: visitors can watch without an account (free channels if subscriptions
 * are also on; everything if subscriptions are off). When OFF: login is required.
 */
function guest_access_enabled(): bool
{
    return Setting::get('guest_access', '0') === '1';
}

/**
 * Does the user currently hold an active, non-expired *paid* subscription?
 * A free (price 0) plan does NOT unlock premium content.
 */
function has_active_subscription(int $userId): bool
{
    $sub = Subscription::activeForUser($userId);
    return $sub !== null && (float) $sub['plan_price'] > 0;
}

/**
 * Can the given user watch the given channel?
 * - Must be logged in.
 * - Free channels: any logged-in user.
 * - Premium channels: require an active subscription.
 */
function can_watch(array $channel, ?array $user): bool
{
    if ($user === null) {
        // Guests: only when guest access is enabled.
        if (!guest_access_enabled()) {
            return false;
        }
        // With subscriptions off everything is open; with subs on, free channels only.
        return !subscriptions_enabled() || (int) $channel['is_premium'] === 0;
    }
    // Subscriptions disabled => everything is open to signed-in users.
    if (!subscriptions_enabled()) {
        return true;
    }
    if ((int) $channel['is_premium'] === 0) {
        return true;
    }
    return has_active_subscription((int) $user['id']);
}

/**
 * Why a user cannot watch — drives the on-page message.
 * Returns 'login' | 'subscribe' | null (null = allowed).
 */
function watch_block_reason(array $channel, ?array $user): ?string
{
    if ($user === null) {
        // Guests must log in unless guest access is on.
        if (!guest_access_enabled()) {
            return 'login';
        }
        // Guest access on: free channels allowed; premium (when subs on) needs login.
        if (subscriptions_enabled() && (int) $channel['is_premium'] === 1) {
            return 'login';
        }
        return null;
    }
    if (!subscriptions_enabled()) {
        return null;
    }
    if ((int) $channel['is_premium'] === 1 && !has_active_subscription((int) $user['id'])) {
        return 'subscribe';
    }
    return null;
}
