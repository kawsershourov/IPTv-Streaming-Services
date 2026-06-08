<?php
declare(strict_types=1);

/**
 * Subscription / content access rules.
 */

/** Does the user currently hold an active, non-expired subscription? */
function has_active_subscription(int $userId): bool
{
    return Subscription::activeForUser($userId) !== null;
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
        return false;
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
        return 'login';
    }
    if ((int) $channel['is_premium'] === 1 && !has_active_subscription((int) $user['id'])) {
        return 'subscribe';
    }
    return null;
}
