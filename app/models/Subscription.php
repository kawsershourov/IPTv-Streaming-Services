<?php
declare(strict_types=1);

/** Thin data access for user subscriptions. */
class Subscription
{
    /** The current active, non-expired subscription for a user (with plan name), or null. */
    public static function activeForUser(int $userId): ?array
    {
        return db_one(
            'SELECT s.*, p.name AS plan_name, p.price AS plan_price
               FROM user_subscriptions s
               JOIN plans p ON p.id = s.plan_id
              WHERE s.user_id = ? AND s.status = "active" AND s.ends_at > NOW()
           ORDER BY s.ends_at DESC
              LIMIT 1',
            [$userId]
        );
    }

    public static function historyForUser(int $userId): array
    {
        return db_all(
            'SELECT s.*, p.name AS plan_name
               FROM user_subscriptions s
               JOIN plans p ON p.id = s.plan_id
              WHERE s.user_id = ?
           ORDER BY s.created_at DESC',
            [$userId]
        );
    }

    /**
     * Grant a plan to a user: marks any current active rows expired, then inserts a fresh
     * active row running for the plan's duration. Returns the new subscription id.
     */
    public static function grant(int $userId, int $planId): int
    {
        $plan = Plan::find($planId);
        if (!$plan) {
            throw new InvalidArgumentException('Plan not found.');
        }
        $days = (int) $plan['duration_days'];

        db_run('UPDATE user_subscriptions SET status = "expired" WHERE user_id = ? AND status = "active"', [$userId]);
        db_run(
            'INSERT INTO user_subscriptions (user_id, plan_id, starts_at, ends_at, status)
             VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? DAY), "active")',
            [$userId, $planId, $days]
        );
        return (int) db()->lastInsertId();
    }

    public static function cancel(int $userId): void
    {
        db_run('UPDATE user_subscriptions SET status = "cancelled" WHERE user_id = ? AND status = "active"', [$userId]);
    }

    /** Count of users with a currently-active subscription. */
    public static function activeCount(): int
    {
        return (int) (db_one(
            'SELECT COUNT(DISTINCT user_id) AS c FROM user_subscriptions WHERE status = "active" AND ends_at > NOW()'
        )['c'] ?? 0);
    }
}
