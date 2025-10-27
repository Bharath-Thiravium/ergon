<?php
require_once __DIR__ . '/../../app/config/database.php';

$db = Database::connect();
$currentUserId = $_SESSION['user_id'] ?? 0;

// Define daily/weekly challenges
$challenges = [
    [
        'id' => 'daily_streak',
        'title' => '🔥 Daily Streak',
        'description' => 'Complete all planned tasks today',
        'type' => 'daily',
        'reward' => '20 bonus points',
        'progress_query' => "
            SELECT 
                COUNT(*) as total_tasks,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks
            FROM daily_plans 
            WHERE user_id = ? AND plan_date = CURDATE()
        "
    ],
    [
        'id' => 'productivity_master',
        'title' => '🚀 Productivity Master',
        'description' => 'Achieve 90%+ productivity score',
        'type' => 'daily',
        'reward' => '15 bonus points',
        'progress_query' => "
            SELECT productivity_score
            FROM daily_workflow_status 
            WHERE user_id = ? AND workflow_date = CURDATE()
        "
    ],
    [
        'id' => 'early_bird',
        'title' => '🌅 Early Bird',
        'description' => 'Submit morning plan before 9 AM',
        'type' => 'daily',
        'reward' => '10 bonus points',
        'progress_query' => "
            SELECT COUNT(*) as submitted
            FROM daily_workflow_status 
            WHERE user_id = ? AND workflow_date = CURDATE() 
            AND morning_submitted_at IS NOT NULL 
            AND TIME(morning_submitted_at) < '09:00:00'
        "
    ],
    [
        'id' => 'weekly_warrior',
        'title' => '⚔️ Weekly Warrior',
        'description' => 'Complete 25+ tasks this week',
        'type' => 'weekly',
        'reward' => '50 bonus points',
        'progress_query' => "
            SELECT COUNT(*) as completed_tasks
            FROM daily_plans 
            WHERE user_id = ? 
            AND status = 'completed'
            AND plan_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        "
    ]
];

// Calculate progress for each challenge
foreach ($challenges as &$challenge) {
    $stmt = $db->prepare($challenge['progress_query']);
    $stmt->execute([$currentUserId]);
    $result = $stmt->fetch();
    
    switch ($challenge['id']) {
        case 'daily_streak':
            $challenge['progress'] = $result['total_tasks'] > 0 ? 
                round(($result['completed_tasks'] / $result['total_tasks']) * 100) : 0;
            $challenge['progress_text'] = "{$result['completed_tasks']}/{$result['total_tasks']} tasks";
            $challenge['completed'] = $result['total_tasks'] > 0 && $result['completed_tasks'] == $result['total_tasks'];
            break;
            
        case 'productivity_master':
            $challenge['progress'] = min(100, round(($result['productivity_score'] ?? 0) / 90 * 100));
            $challenge['progress_text'] = round($result['productivity_score'] ?? 0, 1) . "% productivity";
            $challenge['completed'] = ($result['productivity_score'] ?? 0) >= 90;
            break;
            
        case 'early_bird':
            $challenge['progress'] = $result['submitted'] > 0 ? 100 : 0;
            $challenge['progress_text'] = $result['submitted'] > 0 ? "Submitted early!" : "Not submitted yet";
            $challenge['completed'] = $result['submitted'] > 0;
            break;
            
        case 'weekly_warrior':
            $challenge['progress'] = min(100, round(($result['completed_tasks'] / 25) * 100));
            $challenge['progress_text'] = "{$result['completed_tasks']}/25 tasks";
            $challenge['completed'] = $result['completed_tasks'] >= 25;
            break;
    }
}
?>

<div class="motivational-challenges">
    <div class="challenges-header">
        <h3>🎯 Daily Challenges</h3>
        <div class="challenges-subtitle">Complete challenges to earn bonus points!</div>
    </div>

    <div class="challenges-grid">
        <?php foreach ($challenges as $challenge): ?>
        <div class="challenge-card <?= $challenge['completed'] ? 'completed' : '' ?>">
            <div class="challenge-header">
                <div class="challenge-title"><?= $challenge['title'] ?></div>
                <div class="challenge-type"><?= strtoupper($challenge['type']) ?></div>
            </div>
            
            <div class="challenge-description">
                <?= $challenge['description'] ?>
            </div>
            
            <div class="challenge-progress">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $challenge['progress'] ?>%"></div>
                </div>
                <div class="progress-text"><?= $challenge['progress_text'] ?></div>
            </div>
            
            <div class="challenge-reward">
                <span class="reward-icon">🎁</span>
                <span class="reward-text"><?= $challenge['reward'] ?></span>
            </div>
            
            <?php if ($challenge['completed']): ?>
            <div class="completion-badge">
                <span class="badge-icon">✅</span>
                <span class="badge-text">COMPLETED</span>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="challenges-footer">
        <div class="motivation-quote">
            <?php
            $quotes = [
                "🌟 Every task completed is a step towards excellence!",
                "💪 Your consistency today builds tomorrow's success!",
                "🚀 Great things happen when you push your limits!",
                "⭐ You're not just completing tasks, you're building habits!",
                "🔥 Your dedication inspires the entire team!"
            ];
            echo $quotes[array_rand($quotes)];
            ?>
        </div>
    </div>
</div>

<style>
.motivational-challenges {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-lg);
    padding: var(--space-5);
    margin: var(--space-5) 0;
    color: var(--text-primary);
    box-shadow: var(--shadow);
    position: relative;
    overflow: hidden;
}

.motivational-challenges::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 50%, var(--success) 100%);
}

.challenges-header {
    text-align: center;
    margin-bottom: 20px;
}

.challenges-header h3 {
    margin: 0 0 var(--space-2) 0;
    font-size: var(--font-size-2xl);
    font-weight: 600;
    color: var(--text-primary);
}

.challenges-subtitle {
    font-size: var(--font-size-base);
    color: var(--text-secondary);
    font-weight: 500;
}

.challenges-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.challenge-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: var(--space-4);
    position: relative;
    transition: var(--transition);
    box-shadow: var(--shadow-sm);
}

.challenge-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow);
}

.challenge-card.completed {
    border-color: var(--success);
    background: rgba(5, 150, 105, 0.05);
    box-shadow: 0 0 0 1px rgba(5, 150, 105, 0.1);
}

.challenge-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.challenge-title {
    font-weight: 600;
    font-size: var(--font-size-base);
    color: var(--text-primary);
}

.challenge-type {
    background: var(--primary);
    color: var(--text-inverse);
    padding: var(--space-1) var(--space-2);
    border-radius: 12px;
    font-size: var(--font-size-xs);
    font-weight: 600;
}

.challenge-description {
    font-size: var(--font-size-sm);
    color: var(--text-secondary);
    margin-bottom: var(--space-3);
    line-height: 1.4;
}

.challenge-progress {
    margin-bottom: 12px;
}

.progress-bar {
    height: 6px;
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: var(--space-2);
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary), var(--success));
    transition: width 0.3s ease;
}

.progress-text {
    font-size: var(--font-size-sm);
    color: var(--text-secondary);
    font-weight: 500;
}

.challenge-reward {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.85em;
    color: #2d3748;
    font-weight: 500;
}

.reward-icon {
    font-size: 1.1em;
}

.completion-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: var(--success);
    color: var(--text-inverse);
    padding: var(--space-1) var(--space-2);
    border-radius: 12px;
    font-size: var(--font-size-xs);
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: var(--space-1);
    box-shadow: var(--shadow-sm);
    border: 1px solid rgba(255,255,255,0.2);
}

.challenges-footer {
    text-align: center;
    padding: var(--space-4);
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    margin-top: var(--space-4);
}

.motivation-quote {
    font-style: italic;
    color: var(--text-primary);
    font-weight: 500;
    font-size: var(--font-size-base);
}

@media (max-width: 768px) {
    .challenges-grid {
        grid-template-columns: 1fr;
    }
    
    .challenge-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .challenge-type {
        align-self: flex-end;
    }
}
</style>