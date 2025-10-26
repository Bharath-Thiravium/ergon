<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Advance.php';

class AdvanceController extends Controller {
    private $advanceModel;
    
    public function __construct() {
        $this->advanceModel = new Advance();
    }
    
    public function index() {
        $this->requireAuth();
        $advances = $this->advanceModel->getAll();
        $this->view('advances/index', ['advances' => $advances]);
    }
    
    public function create() {
        $this->requireAuth();
        $this->view('advances/create');
    }
    
    public function store() {
        $this->requireAuth();
        
        $data = [
            'user_id' => $_SESSION['user_id'],
            'amount' => $_POST['amount'] ?? 0,
            'reason' => $_POST['reason'] ?? '',
            'requested_date' => date('Y-m-d'),
            'status' => 'pending'
        ];
        
        if ($this->advanceModel->create($data)) {
            $this->json(['success' => true, 'message' => 'Advance request submitted']);
        } else {
            $this->json(['error' => 'Failed to submit advance request'], 400);
        }
    }
}
?>
