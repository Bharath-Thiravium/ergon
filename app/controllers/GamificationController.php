<?php
require_once __DIR__ . '/../core/Controller.php';

class GamificationController extends Controller {
    
    public function teamCompetition() {
        $this->requireAuth();
        
        try {
            require_once __DIR__ . '/../models/Gamification.php';
            require_once __DIR__ . '/../models/User.php';
            require_once __DIR__ . '/../models/Task.php';
            
            $gamification = new Gamification();
            $userModel = new User();
            $taskModel = new Task();
            
            // Get all users and their stats with error handling
            $allUsers = [];
            $leaderboard = [];
            $userStats = [];
            
            try {
                $allUsers = $userModel->getAllUsers();
                $leaderboard = $gamification->getLeaderboard(20);
                
                // Get detailed user stats
                foreach ($allUsers as $user) {
                    $totalPoints = 0;
                    $userBadges = [];
                    $userTasks = [];
                    
                    try {
                        $totalPoints = $gamification->getTotalPoints($user['id']);
                        $userBadges = $gamification->getUserBadges($user['id']);
                        $userTasks = $taskModel->getByUserId($user['id']);
                    } catch (Exception $e) {
                        error_log('User stats error for user ' . $user['id'] . ': ' . $e->getMessage());
                    }
                    
                    $userStats[] = [
                        'id' => $user['id'],
                        'name' => $user['name'],
                        'role' => $user['role'],
                        'department' => $user['department'] ?? 'General',
                        'total_points' => $totalPoints,
                        'badges' => $userBadges,
                        'tasks' => $userTasks,
                        'completed_tasks' => count(array_filter($userTasks, function($task) {
                            return isset($task['status']) && $task['status'] === 'completed';
                        }))
                    ];
                }
            } catch (Exception $e) {
                error_log('Team competition data error: ' . $e->getMessage());
                // Use default values
            }
            
            $data = [
                'title' => 'Team Competition Dashboard',
                'active_page' => 'team-competition',
                'leaderboard' => $leaderboard,
                'user_stats' => $userStats,
                'team_stats' => [
                    'total_points' => array_sum(array_column($userStats, 'total_points')),
                    'total_tasks' => array_sum(array_column($userStats, 'completed_tasks')),
                    'total_badges' => array_sum(array_map(function($user) { return count($user['badges']); }, $userStats))
                ]
            ];
            
            $this->view('gamification/team_competition', $data);
        } catch (Exception $e) {
            error_log('Team competition error: ' . $e->getMessage());
            echo '<h2>Team Competition Dashboard</h2><p>Gamification system is being set up. Please check back later.</p>';
        }
    }
    
    public function individual() {
        $this->requireAuth();
        
        try {
            require_once __DIR__ . '/../models/Gamification.php';
            require_once __DIR__ . '/../models/User.php';
            
            $gamification = new Gamification();
            $userModel = new User();
            $userId = $_SESSION['user_id'];
            
            // Get user stats with error handling
            $totalPoints = 0;
            $userRank = 1;
            $userBadges = [];
            $leaderboard = [];
            $allUsers = [];
            
            try {
                $totalPoints = $gamification->getTotalPoints($userId);
                $userRank = $gamification->getUserRank($userId);
                $userBadges = $gamification->getUserBadges($userId);
                $leaderboard = $gamification->getLeaderboard(10);
                $allUsers = $userModel->getAllUsers();
            } catch (Exception $e) {
                error_log('Gamification data error: ' . $e->getMessage());
                // Use default values
            }
            
            $data = [
                'title' => 'Individual Achievements',
                'active_page' => 'gamification-individual',
                'user_stats' => [
                    'total_points' => $totalPoints,
                    'rank' => $userRank,
                    'badges' => $userBadges
                ],
                'leaderboard' => $leaderboard,
                'all_users' => $allUsers
            ];
            
            $this->view('gamification/individual', $data);
        } catch (Exception $e) {
            error_log('Individual gamification error: ' . $e->getMessage());
            echo '<h2>Individual Achievements</h2><p>Gamification system is being set up. Please check back later.</p>';
        }
    }
}
