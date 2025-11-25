// Initialize timers on page load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.task-card').forEach(taskCard => {
        const taskId = taskCard.dataset.taskId;
        const status = taskCard.dataset.status;
        
        // Ensure mutual exclusivity - only one timer per task
        window.taskTimer.stop(taskId);
        window.taskTimer.stopPause(taskId);
        
        if (status === 'in_progress') {
            const slaDuration = parseInt(taskCard.dataset.slaDuration) || 900;
            const startTime = parseInt(taskCard.dataset.startTime) || Math.floor(Date.now() / 1000);
            window.taskTimer.start(taskId, slaDuration, startTime);
        } else if (status === 'on_break') {
            const pauseStartTime = taskCard.dataset.pauseStartTime ? 
                Math.floor(new Date(taskCard.dataset.pauseStartTime).getTime() / 1000) : 
                Math.floor(Date.now() / 1000);
            window.taskTimer.startPause(taskId, pauseStartTime);
        }
    });
});