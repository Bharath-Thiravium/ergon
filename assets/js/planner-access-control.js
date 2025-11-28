// Planner Access Control JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Disable buttons in historical view
    if (document.querySelector('.historical-view')) {
        const buttons = document.querySelectorAll('.historical-view .btn:not(.btn--secondary):not(.btn--info)');
        buttons.forEach(btn => {
            btn.disabled = true;
            btn.style.cursor = 'not-allowed';
        });
    }
    
    // Add visual indicators for different modes
    const plannerGrid = document.querySelector('.planner-grid');
    if (plannerGrid) {
        if (plannerGrid.classList.contains('historical-view')) {
            console.log('Historical view mode active');
        } else if (plannerGrid.classList.contains('planning-mode')) {
            console.log('Planning mode active');
        } else if (plannerGrid.classList.contains('execution-mode')) {
            console.log('Execution mode active');
        }
    }
});

// Show history info modal
function showHistoryInfo() {
    alert('Historical View: This shows completed tasks from past dates. Task execution is disabled in this view.');
}

// Change date function
function changeDate(date) {
    const baseUrl = window.dailyPlannerBaseUrl || '/ergon/workflow/daily-planner/';
    window.location.href = baseUrl + date;
}