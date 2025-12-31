// Essential task functions for daily planner
console.log('Task functions loaded');

// Global function definitions
window.pauseTask = function(taskId) {
    alert('Pause task ' + taskId + ' - Function loaded successfully!');
};

window.resumeTask = function(taskId) {
    alert('Resume task ' + taskId + ' - Function loaded successfully!');
};

window.openProgressModal = function(taskId, progress, status) {
    alert('Progress modal for task ' + taskId + ' (progress: ' + progress + '%, status: ' + status + ')');
};

window.postponeTask = function(taskId) {
    const newDate = prompt('Enter new date (YYYY-MM-DD):');
    if (newDate) {
        alert('Postpone task ' + taskId + ' to ' + newDate);
    }
};

window.startTask = function(taskId) {
    alert('Start task ' + taskId + ' - Function loaded successfully!');
};

// Also define as regular functions for onclick compatibility
function pauseTask(taskId) { return window.pauseTask(taskId); }
function resumeTask(taskId) { return window.resumeTask(taskId); }
function openProgressModal(taskId, progress, status) { return window.openProgressModal(taskId, progress, status); }
function postponeTask(taskId) { return window.postponeTask(taskId); }
function startTask(taskId) { return window.startTask(taskId); }

console.log('All task functions defined globally');
