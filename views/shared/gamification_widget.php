<?php
// Temporarily disable gamification until tables are created
try {
    require_once __DIR__ . '/../../app/models/Gamification.php';
    $gamification = new Gamification();
    $userPoints = $gamification->getTotalPoints($_SESSION['user_id']);
    $userRank = $gamification->getUserRank($_SESSION['user_id']);
    $userBadges = $gamification->getUserBadges($_SESSION['user_id']);
    $leaderboard = $gamification->getLeaderboard(5);
} catch (Exception $e) {
    // Fallback values when gamification tables don't exist
    $userPoints = 0;
    $userRank = 1;
    $userBadges = [];
    $leaderboard = [];
}
?>

<div class="gamification-widget">
    <div class="points-display">
        <span class="points-icon">💎</span>
        <span class="points-value"><?= $userPoints ?></span>
        <span class="points-label">Points</span>
        <span class="rank-badge">#<?= $userRank ?></span>
    </div>
    
    <?php if (!empty($userBadges)): ?>
    <div class="badges-display">
        <?php foreach (array_slice($userBadges, 0, 3) as $badge): ?>
        <span class="badge-icon" title="<?= htmlspecialchars($badge['name']) ?>: <?= htmlspecialchars($badge['description']) ?>">
            <?= $badge['icon'] ?>
        </span>
        <?php endforeach; ?>
        <?php if (count($userBadges) > 3): ?>
        <span class="badge-more">+<?= count($userBadges) - 3 ?></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<style>
.gamification-widget {
    display: flex;
    align-items: center;
    gap: var(--space-4);
    padding: var(--space-3) var(--space-4);
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    border-radius: var(--border-radius);
    color: var(--text-inverse);
    font-size: var(--font-size-sm);
    box-shadow: var(--shadow-sm);
    border: 1px solid rgba(255,255,255,0.1);
}

.points-display {
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

.points-value {
    font-weight: 600;
    font-size: var(--font-size-lg);
    color: var(--text-inverse);
}

.rank-badge {
    background: rgba(255,255,255,0.2);
    padding: var(--space-1) var(--space-3);
    border-radius: 12px;
    font-size: var(--font-size-xs);
    font-weight: 500;
    backdrop-filter: blur(10px);
}

.badges-display {
    display: flex;
    gap: var(--space-2);
}

.badge-icon {
    font-size: 1.2em;
    cursor: help;
    transition: var(--transition);
}

.badge-icon:hover {
    transform: scale(1.1);
}

.badge-more {
    background: rgba(255,255,255,0.2);
    padding: var(--space-1) var(--space-2);
    border-radius: var(--border-radius);
    font-size: var(--font-size-xs);
    font-weight: 500;
}
</style>