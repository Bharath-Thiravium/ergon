// Mobile WebView Integration JavaScript
// Handles communication between web app and Android WebView

class ErgonMobile {
    constructor() {
        this.isNativeApp = typeof window.ErgonNative !== 'undefined';
        this.offlineQueue = {
            attendance: [],
            tasks: []
        };
        
        this.init();
    }
    
    init() {
        // Set up native hooks
        window.__ergon_setToken = (token) => this.handleTokenFromNative(token);
        window.__ergon_onLocation = (lat, lng) => this.handleLocationFromNative(lat, lng);
        window.__ergon_syncQueue = () => this.syncOfflineQueue();
        window.__ergon_registerFCM = (token) => this.registerFCMToken(token);
        
        // Load offline queue from native storage
        this.loadOfflineQueue();
        
        // Auto-sync when online
        window.addEventListener('online', () => this.syncOfflineQueue());
        
        // Check for pending sync every 30 seconds
        setInterval(() => {
            if (navigator.onLine) this.syncOfflineQueue();
        }, 30000);
    }
    
    handleTokenFromNative(token) {
        localStorage.setItem('ergon_jwt', token);
        // Set session cookie for web compatibility
        fetch('/ergon/api/session_from_jwt', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + token,
                'Content-Type': 'application/json'
            }
        }).then(response => {
            if (response.ok) {
                console.log('Session established from JWT');
                window.location.reload();
            }
        });
    }
    
    handleLocationFromNative(lat, lng) {
        this.currentLocation = { latitude: lat, longitude: lng };
        
        // Update UI with location
        const locationElements = document.querySelectorAll('.current-location');
        locationElements.forEach(el => {
            el.textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        });
        
        // Calculate distance from office if geofence data available
        if (window.officeLocation) {
            const distance = this.calculateDistance(lat, lng, window.officeLocation.lat, window.officeLocation.lng);
            const distanceElements = document.querySelectorAll('.distance-indicator');
            distanceElements.forEach(el => {
                el.textContent = `${Math.round(distance)}m from office`;
                el.className = distance <= 200 ? 'distance-indicator valid' : 'distance-indicator invalid';
            });
        }
    }
    
    calculateDistance(lat1, lng1, lat2, lng2) {
        const R = 6371000; // Earth's radius in meters
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLng = (lng2 - lng1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLng/2) * Math.sin(dLng/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }
    
    // Attendance functions
    checkIn() {
        if (!this.currentLocation) {
            this.getLocation(() => this.performCheckIn());
        } else {
            this.performCheckIn();
        }
    }
    
    performCheckIn() {
        const attendanceData = {
            action: 'checkin',
            latitude: this.currentLocation.latitude,
            longitude: this.currentLocation.longitude,
            timestamp: new Date().toISOString()
        };
        
        if (navigator.onLine) {
            this.submitAttendance(attendanceData);
        } else {
            this.queueAttendance(attendanceData);
            this.showToast('Attendance queued for sync when online');
        }
    }
    
    checkOut() {
        const attendanceData = {
            action: 'checkout',
            timestamp: new Date().toISOString()
        };
        
        if (navigator.onLine) {
            this.submitAttendance(attendanceData);
        } else {
            this.queueAttendance(attendanceData);
            this.showToast('Check-out queued for sync when online');
        }
    }
    
    submitAttendance(data) {
        const token = localStorage.getItem('ergon_jwt');
        
        fetch('/ergon/api/attendance', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + token,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                this.showToast('Attendance recorded successfully');
                this.updateAttendanceUI(data.action);
            } else {
                this.showToast('Failed to record attendance: ' + result.message);
            }
        })
        .catch(error => {
            console.error('Attendance error:', error);
            this.queueAttendance(data);
            this.showToast('Attendance queued for sync');
        });
    }
    
    queueAttendance(data) {
        if (this.isNativeApp) {
            window.ErgonNative.queueAttendance(JSON.stringify(data));
        } else {
            this.offlineQueue.attendance.push(data);
            localStorage.setItem('ergon_offline_queue', JSON.stringify(this.offlineQueue));
        }
    }
    
    // Task functions
    updateTaskProgress(taskId, progress, comment) {
        const taskData = {
            task_id: taskId,
            progress: progress,
            comment: comment,
            timestamp: new Date().toISOString()
        };
        
        if (navigator.onLine) {
            this.submitTaskUpdate(taskData);
        } else {
            this.queueTaskUpdate(taskData);
            this.showToast('Task update queued for sync');
        }
    }
    
    submitTaskUpdate(data) {
        const token = localStorage.getItem('ergon_jwt');
        
        fetch('/ergon/api/tasks/update', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + token,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                this.showToast('Task updated successfully');
                this.updateTaskUI(data.task_id, data.progress);
            } else {
                this.showToast('Failed to update task: ' + result.message);
            }
        })
        .catch(error => {
            console.error('Task update error:', error);
            this.queueTaskUpdate(data);
            this.showToast('Task update queued for sync');
        });
    }
    
    queueTaskUpdate(data) {
        if (this.isNativeApp) {
            window.ErgonNative.queueTaskUpdate(JSON.stringify(data));
        } else {
            this.offlineQueue.tasks.push(data);
            localStorage.setItem('ergon_offline_queue', JSON.stringify(this.offlineQueue));
        }
    }
    
    // Sync functions
    loadOfflineQueue() {
        if (this.isNativeApp) {
            try {
                const attendanceQueue = JSON.parse(window.ErgonNative.getQueuedData('attendance') || '[]');
                const taskQueue = JSON.parse(window.ErgonNative.getQueuedData('tasks') || '[]');
                this.offlineQueue = { attendance: attendanceQueue, tasks: taskQueue };
            } catch (e) {
                console.error('Error loading native queue:', e);
            }
        } else {
            const stored = localStorage.getItem('ergon_offline_queue');
            if (stored) {
                try {
                    this.offlineQueue = JSON.parse(stored);
                } catch (e) {
                    this.offlineQueue = { attendance: [], tasks: [] };
                }
            }
        }
        
        this.updateQueueIndicator();
    }
    
    syncOfflineQueue() {
        if (!navigator.onLine) return;
        
        const totalItems = this.offlineQueue.attendance.length + this.offlineQueue.tasks.length;
        if (totalItems === 0) return;
        
        console.log(`Syncing ${totalItems} offline items...`);
        
        const syncData = [];
        
        // Prepare attendance data
        this.offlineQueue.attendance.forEach(item => {
            syncData.push({
                type: 'attendance',
                data: item,
                client_uuid: item.client_uuid || this.generateUUID()
            });
        });
        
        // Prepare task data
        this.offlineQueue.tasks.forEach(item => {
            syncData.push({
                type: 'task_update',
                data: item,
                client_uuid: item.client_uuid || this.generateUUID()
            });
        });
        
        const token = localStorage.getItem('ergon_jwt');
        
        fetch('/ergon/api/sync', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + token,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ queue_data: syncData })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                console.log('Sync completed:', result.results);
                this.clearOfflineQueue();
                this.showToast(`Synced ${totalItems} items successfully`);
            } else {
                console.error('Sync failed:', result);
            }
        })
        .catch(error => {
            console.error('Sync error:', error);
        });
    }
    
    clearOfflineQueue() {
        this.offlineQueue = { attendance: [], tasks: [] };
        
        if (this.isNativeApp) {
            window.ErgonNative.clearQueue('attendance');
            window.ErgonNative.clearQueue('tasks');
        } else {
            localStorage.removeItem('ergon_offline_queue');
        }
        
        this.updateQueueIndicator();
    }
    
    updateQueueIndicator() {
        const totalItems = this.offlineQueue.attendance.length + this.offlineQueue.tasks.length;
        const indicators = document.querySelectorAll('.offline-queue-indicator');
        
        indicators.forEach(indicator => {
            if (totalItems > 0) {
                indicator.textContent = `${totalItems} items pending sync`;
                indicator.style.display = 'block';
            } else {
                indicator.style.display = 'none';
            }
        });
    }
    
    // Utility functions
    getLocation(callback) {
        if (this.isNativeApp) {
            window.ErgonNative.getLocation();
            // Wait for native callback
            setTimeout(() => {
                if (callback && this.currentLocation) callback();
            }, 1000);
        } else {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    position => {
                        this.currentLocation = {
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude
                        };
                        if (callback) callback();
                    },
                    error => {
                        console.error('Geolocation error:', error);
                        this.showToast('Unable to get location');
                    }
                );
            }
        }
    }
    
    registerFCMToken(token) {
        const jwt = localStorage.getItem('ergon_jwt');
        
        fetch('/ergon/api/register_device', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + jwt,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                fcm_token: token,
                device_type: 'android',
                device_info: navigator.userAgent
            })
        })
        .then(response => response.json())
        .then(result => {
            console.log('FCM token registered:', result);
        })
        .catch(error => {
            console.error('FCM registration error:', error);
        });
    }
    
    showToast(message) {
        if (this.isNativeApp) {
            window.ErgonNative.showToast(message);
        } else {
            // Web toast implementation
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.textContent = message;
            toast.style.cssText = `
                position: fixed; top: 20px; right: 20px; z-index: 9999;
                background: #333; color: white; padding: 12px 20px;
                border-radius: 4px; font-size: 14px;
            `;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
    }
    
    updateAttendanceUI(action) {
        const statusEl = document.querySelector('.attendance-status');
        if (statusEl) {
            statusEl.textContent = action === 'checkin' ? 'Checked In' : 'Checked Out';
            statusEl.className = `attendance-status ${action}`;
        }
    }
    
    updateTaskUI(taskId, progress) {
        const progressBar = document.querySelector(`[data-task-id="${taskId}"] .progress-bar`);
        if (progressBar) {
            progressBar.style.width = progress + '%';
            progressBar.textContent = progress + '%';
        }
    }
    
    generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0;
            const v = c == 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }
}

// Initialize mobile integration
window.ergonMobile = new ErgonMobile();

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ErgonMobile;
}