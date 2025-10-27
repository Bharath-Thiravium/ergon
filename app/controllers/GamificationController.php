<?php
require_once __DIR__ . '/../core/Controller.php';

class GamificationController extends Controller {
    
    public function teamCompetition() {
        $this->requireAuth();
        
        // Data for the view (can be expanded later)
        $data = [
            'stats' => [
                'team_points' => 2180,
                'tasks_completed' => 156,
                'achievements_earned' => 2,
                'performance_score' => 95
            ]
        ];
        
        include __DIR__ . '/../../views/gamification/team_competition.php';
    }
}