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
    gap: 1rem;
    padding: 0.5rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 8px;
    color: white;
    font-size: 0.875rem;
}

.points-display {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.points-value {
    font-weight: bold;
    font-size: 1.1em;
}

.rank-badge {
    background: rgba(255,255,255,0.2);
    padding: 0.125rem 0.375rem;
    border-radius: 12px;
    font-size: 0.75rem;
}

.badges-display {
    display: flex;
    gap: 0.25rem;
}

.badge-icon {
    font-size: 1.2em;
    cursor: help;
}

.badge-more {
    background: rgba(255,255,255,0.2);
    padding: 0.125rem 0.25rem;
    border-radius: 8px;
    font-size: 0.75rem;
}
</style>