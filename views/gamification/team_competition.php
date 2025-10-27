<?php
$title = 'Team Competition Dashboard';
$active_page = 'team-competition';

ob_start();
?>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🏆</div>
            <div class="kpi-card__trend">↗ #2</div>
        </div>
        <div class="kpi-card__value">2,180</div>
        <div class="kpi-card__label">Team Points</div>
        <div class="kpi-card__status">Ranking</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">👥</div>
            <div class="kpi-card__trend">↗ +15</div>
        </div>
        <div class="kpi-card__value">156</div>
        <div class="kpi-card__label">Tasks Completed</div>
        <div class="kpi-card__status">This Week</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🏅</div>
            <div class="kpi-card__trend">↗ 4</div>
        </div>
        <div class="kpi-card__value">2</div>
        <div class="kpi-card__label">Achievements</div>
        <div class="kpi-card__status">Earned</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📈</div>
            <div class="kpi-card__trend">↗ +12%</div>
        </div>
        <div class="kpi-card__value">95%</div>
        <div class="kpi-card__label">Performance</div>
        <div class="kpi-card__status">Score</div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">🏆 Team Leaderboard</h2>
        </div>
        <div class="card__body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Team</th>
                            <th>Points</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="alert alert--warning" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">🥇 1st</span></td>
                            <td>Development Team</td>
                            <td>2,450</td>
                            <td><span class="alert alert--success" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">Leading</span></td>
                        </tr>
                        <tr>
                            <td><span class="alert alert--info" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">🥈 2nd</span></td>
                            <td>Marketing Team</td>
                            <td>2,180</td>
                            <td><span class="alert alert--info" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">Active</span></td>
                        </tr>
                        <tr>
                            <td><span class="alert alert--secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">🥉 3rd</span></td>
                            <td>Sales Team</td>
                            <td>1,920</td>
                            <td><span class="alert alert--warning" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">Catching Up</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">🏅 Team Achievements</h2>
        </div>
        <div class="card__body">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <button class="btn btn--success" style="pointer-events: none;">
                    🚀 Sprint Master
                </button>
                <button class="btn btn--success" style="pointer-events: none;">
                    ⚡ Speed Demon
                </button>
                <button class="btn btn--secondary" style="pointer-events: none; opacity: 0.5;">
                    🎯 Perfect Score
                </button>
                <button class="btn btn--secondary" style="pointer-events: none; opacity: 0.5;">
                    🔥 Hot Streak
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>