// Enhanced timer fix with proper resume and time tracking
document.addEventListener('DOMContentLoaded', function() {
    // Global timer storage
    window.taskTimers = window.taskTimers || {};
    window.taskStates = window.taskStates || {};
    
    // Fix all existing countdown displays
    document.querySelectorAll('.countdown-display').forEach(function(el) {
        if (el.textContent.includes('1970') || el.textContent === '' || el.textContent === 'NaN') {
            el.textContent = '00:15:00'; // Default 15 minutes
            el.className = 'countdown-display';
        }
    });
    
    // Add time used display to each task
    document.querySelectorAll('.task-card').forEach(function(taskCard) {
        const taskId = taskCard.dataset.taskId;
        const timingDiv = taskCard.querySelector('.task-card__timing');
        if (timingDiv && !timingDiv.querySelector('.time-used-info')) {
            const timeUsedDiv = document.createElement('div');
            timeUsedDiv.className = 'timing-info time-used-info';
            timeUsedDiv.innerHTML = `
                <div class="time-used-display" id="time-used-${taskId}">00:00:00</div>
                <div class="time-used-label">Time Used</div>
            `;
            timingDiv.appendChild(timeUsedDiv);
        }
    });
    
    // Override the problematic functions with simple versions
    window.startTask = function(taskId) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        
        fetch('/ergon/api/daily_planner_workflow.php?action=start', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                task_id: parseInt(taskId),
                csrf_token: csrfToken
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateTaskUI(taskId, 'in_progress');
                
                // Initialize task state and fetch existing pause data
                fetchTaskPauseData(taskId).then(pauseData => {
                    window.taskStates[taskId] = {
                        slaTime: 900, // 15 minutes
                        timeUsed: pauseData.timeUsed || 0,
                        pauseTime: pauseData.pauseTime || 0,
                        startTime: Date.now(),
                        lastResumeTime: Date.now(),
                        status: 'in_progress'
                    };
                    
                    // Update displays with existing data
                    const pauseTimeEl = document.querySelector(`#pause-time-${taskId}`);
                    if (pauseTimeEl) {
                        pauseTimeEl.textContent = formatTime(pauseData.pauseTime || 0);
                    }
                });
                
                startSLATimer(taskId);
                alert('Task started successfully');
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            alert('Error starting task: ' + error.message);
        });
    };
    
    window.pauseTask = function(taskId) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        
        // Update task state before pausing
        if (window.taskStates[taskId]) {
            const now = Date.now();
            const sessionTime = Math.floor((now - window.taskStates[taskId].lastResumeTime) / 1000);
            window.taskStates[taskId].timeUsed += sessionTime;
            window.taskStates[taskId].status = 'on_break';
            window.taskStates[taskId].pauseStartTime = now;
        }
        
        // Stop the SLA timer
        if (window.taskTimers[taskId]) {
            clearInterval(window.taskTimers[taskId]);
        }
        
        fetch('/ergon/api/daily_planner_workflow.php?action=pause', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                task_id: parseInt(taskId),
                pause_duration: window.taskStates[taskId]?.pauseTime || 0,
                csrf_token: csrfToken
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateTaskUI(taskId, 'on_break');
                startPauseTimer(taskId);
                alert('Task paused successfully');
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            alert('Error pausing task: ' + error.message);
        });
    };
    
    window.resumeTask = function(taskId) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        
        // Calculate final pause duration
        let totalPauseDuration = 0;
        if (window.taskStates[taskId] && window.taskStates[taskId].pauseStartTime) {
            const now = Date.now();
            const currentPauseDuration = Math.floor((now - window.taskStates[taskId].pauseStartTime) / 1000);
            totalPauseDuration = window.taskStates[taskId].pauseTime + currentPauseDuration;
            window.taskStates[taskId].pauseTime = totalPauseDuration;
            window.taskStates[taskId].status = 'in_progress';
            window.taskStates[taskId].lastResumeTime = now;
            delete window.taskStates[taskId].pauseStartTime;
        }
        
        // Stop pause timer
        if (window.taskTimers[taskId]) {
            clearInterval(window.taskTimers[taskId]);
        }
        
        fetch('/ergon/api/daily_planner_workflow.php?action=resume', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                task_id: parseInt(taskId),
                total_pause_duration: totalPauseDuration,
                csrf_token: csrfToken
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateTaskUI(taskId, 'in_progress');
                startSLATimer(taskId);
                alert('Task resumed successfully');
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            alert('Error resuming task: ' + error.message);
        });
    };
    
    // Start SLA countdown timer
    function startSLATimer(taskId) {
        if (window.taskTimers[taskId]) {
            clearInterval(window.taskTimers[taskId]);
        }
        
        window.taskTimers[taskId] = setInterval(() => {
            const state = window.taskStates[taskId];
            if (!state || state.status !== 'in_progress') return;
            
            const now = Date.now();
            const sessionTime = Math.floor((now - state.lastResumeTime) / 1000);
            const totalTimeUsed = state.timeUsed + sessionTime;
            const remainingTime = Math.max(0, state.slaTime - totalTimeUsed);
            
            // Update displays
            updateTimeDisplays(taskId, remainingTime, totalTimeUsed, state.pauseTime);
            
            // Check for overdue
            if (remainingTime <= 0) {
                clearInterval(window.taskTimers[taskId]);
                markTaskOverdue(taskId, totalTimeUsed);
            }
        }, 1000);
    }
    
    // Start pause timer with live updates
    function startPauseTimer(taskId) {
        if (window.taskTimers[taskId]) {
            clearInterval(window.taskTimers[taskId]);
        }
        
        const state = window.taskStates[taskId];
        if (!state || !state.pauseStartTime) return;
        
        window.taskTimers[taskId] = setInterval(() => {
            const now = Date.now();
            const currentPauseDuration = Math.floor((now - state.pauseStartTime) / 1000);
            const totalPauseTime = state.pauseTime + currentPauseDuration;
            const remainingTime = Math.max(0, state.slaTime - state.timeUsed);
            
            updatePauseDisplays(taskId, remainingTime, state.timeUsed, totalPauseTime, currentPauseDuration);
            
            // Save pause duration to server every 30 seconds
            if (currentPauseDuration > 0 && currentPauseDuration % 30 === 0) {
                savePauseDuration(taskId, totalPauseTime);
            }
        }, 1000);
    }
    
    // Save pause duration to server
    function savePauseDuration(taskId, totalPauseDuration) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        
        fetch('/ergon/api/daily_planner_workflow.php?action=update-pause-duration', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                task_id: parseInt(taskId),
                pause_duration: totalPauseDuration,
                csrf_token: csrfToken
            })
        })
        .catch(error => {
            console.log('Pause duration save failed:', error.message);
        });
    }
    
    // Update time displays
    function updateTimeDisplays(taskId, remainingTime, timeUsed, pauseTime) {
        const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
        if (!taskCard) return;
        
        const countdownEl = taskCard.querySelector(`#countdown-${taskId} .countdown-display`);
        const timeUsedEl = taskCard.querySelector(`#time-used-${taskId}`);
        
        if (countdownEl) {
            countdownEl.textContent = formatTime(remainingTime);
            countdownEl.className = remainingTime < 300 ? 'countdown-display countdown-display--warning' : 'countdown-display';
        }
        
        if (timeUsedEl) {
            timeUsedEl.textContent = formatTime(timeUsed);
        }
    }
    
    // Update pause displays
    function updatePauseDisplays(taskId, remainingTime, timeUsed, totalPauseTime, currentPauseDuration) {
        const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
        if (!taskCard) return;
        
        const countdownEl = taskCard.querySelector(`#countdown-${taskId} .countdown-display`);
        const timeUsedEl = taskCard.querySelector(`#time-used-${taskId}`);
        
        if (countdownEl) {
            countdownEl.textContent = `Paused (${formatTime(remainingTime)} left)`;
            countdownEl.className = 'countdown-display countdown-display--paused';
        }
        
        if (timeUsedEl) {
            timeUsedEl.textContent = formatTime(timeUsed);
        }
    }
    
    // Mark task as overdue
    function markTaskOverdue(taskId, timeUsed) {
        const taskCard = document.querySelector(`[data-task-id="${taskId}"]`);
        if (!taskCard) return;
        
        const countdownEl = taskCard.querySelector(`#countdown-${taskId} .countdown-display`);
        if (countdownEl) {
            countdownEl.textContent = 'OVERDUE';
            countdownEl.className = 'countdown-display countdown-display--overdue';
        }
        
        // Start overdue timer
        const state = window.taskStates[taskId];
        state.overdueStartTime = Date.now();
        
        window.taskTimers[taskId] = setInterval(() => {
            const now = Date.now();
            const sessionTime = Math.floor((now - state.lastResumeTime) / 1000);
            const totalTimeUsed = state.timeUsed + sessionTime;
            const overdueTime = totalTimeUsed - state.slaTime;
            
            const timeUsedEl = taskCard.querySelector(`#time-used-${taskId}`);
            if (timeUsedEl) {
                timeUsedEl.textContent = formatTime(totalTimeUsed);
            }
            
            if (countdownEl) {
                countdownEl.textContent = `OVERDUE +${formatTime(overdueTime)}`;
            }
        }, 1000);
    }
    
    // Fetch existing pause data from server
    function fetchTaskPauseData(taskId) {
        return fetch(`/ergon/api/daily_planner_workflow.php?action=timer&task_id=${taskId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    return {
                        timeUsed: data.active_seconds || 0,
                        pauseTime: data.pause_duration || 0
                    };
                }
                return { timeUsed: 0, pauseTime: 0 };
            })
            .catch(() => ({ timeUsed: 0, pauseTime: 0 }));
    }
    
    // Format time helper
    function formatTime(seconds) {
        if (!seconds || seconds < 0) return '00:00:00';
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
});