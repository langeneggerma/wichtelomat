// Wichtelomat JavaScript for real-time updates and modern UX
class Wichtelomat {
    constructor() {
        this.sessionId = this.getSessionId();
        this.username = localStorage.getItem('wichtel_username');
        this.updateInterval = null;
        this.lastUpdateTime = 0;
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.startAutoUpdate();
        this.updateHeartbeat();
    }
    
    getSessionId() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('session') || document.querySelector('input[name="session_id"]')?.value;
    }
    
    setupEventListeners() {
        // Copy session link button
        const copyBtn = document.getElementById('copy-session-link');
        if (copyBtn) {
            copyBtn.addEventListener('click', this.copySessionLink.bind(this));
        }
        
        // Form submissions
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', this.handleFormSubmit.bind(this));
        });
        
        // Auto-refresh toggle
        const refreshToggle = document.getElementById('auto-refresh');
        if (refreshToggle) {
            refreshToggle.addEventListener('change', this.toggleAutoRefresh.bind(this));
        }
        
        // Participant name input
        const nameInput = document.getElementById('participant-name');
        if (nameInput) {
            nameInput.addEventListener('input', this.validateName.bind(this));
        }
        
        // Window focus/blur for activity tracking
        window.addEventListener('focus', this.updateHeartbeat.bind(this));
        window.addEventListener('beforeunload', this.cleanup.bind(this));
    }
    
    async copySessionLink() {
        const sessionLink = window.location.origin + window.location.pathname + '?session=' + this.sessionId;
        
        try {
            await navigator.clipboard.writeText(sessionLink);
            this.showNotification('Session-Link wurde kopiert!', 'success');
            
            // Visual feedback
            const btn = document.getElementById('copy-session-link');
            const originalText = btn.textContent;
            btn.textContent = '✓ Kopiert!';
            btn.classList.add('btn-success');
            
            setTimeout(() => {
                btn.textContent = originalText;
                btn.classList.remove('btn-success');
            }, 2000);
        } catch (err) {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = sessionLink;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            
            this.showNotification('Session-Link wurde kopiert!', 'success');
        }
    }
    
    async handleFormSubmit(event) {
        const form = event.target;
        const formData = new FormData(form);
        const action = formData.get('action');
        
        // Show loading state for certain actions
        if (['add_participant', 'start_wichtelomat'].includes(action)) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner"></span> Verarbeite...';
            }
        }
        
        // Store username for activity tracking
        if (action === 'add_participant') {
            const name = formData.get('name');
            if (name) {
                localStorage.setItem('wichtel_username', name);
                this.username = name;
            }
        }
    }
    
    validateName() {
        const nameInput = document.getElementById('participant-name');
        const addBtn = document.querySelector('button[value="add_participant"]');
        
        if (nameInput && addBtn) {
            const name = nameInput.value.trim();
            const isValid = name.length >= 2 && name.length <= 50;
            
            addBtn.disabled = !isValid;
            
            // Visual feedback
            if (name.length > 0) {
                nameInput.classList.toggle('is-valid', isValid);
                nameInput.classList.toggle('is-invalid', !isValid);
            } else {
                nameInput.classList.remove('is-valid', 'is-invalid');
            }
        }
    }
    
    startAutoUpdate() {
        // Update every 5 seconds
        this.updateInterval = setInterval(() => {
            this.updatePageData();
        }, 5000);
        
        // Initial update
        setTimeout(() => this.updatePageData(), 1000);
    }
    
    toggleAutoRefresh(event) {
        if (event.target.checked) {
            this.startAutoUpdate();
        } else {
            if (this.updateInterval) {
                clearInterval(this.updateInterval);
                this.updateInterval = null;
            }
        }
    }
    
    async updatePageData() {
        if (!this.sessionId) return;
        
        try {
            const response = await fetch(`api.php?action=get_session_data&session_id=${this.sessionId}&t=${Date.now()}`);
            const data = await response.json();
            
            if (data.success) {
                this.updateParticipantsList(data.participants);
                this.updateOnlineStatus(data.online_users);
                this.updateStats(data.stats);
                this.lastUpdateTime = Date.now();
                
                // Update last update time display
                const lastUpdateEl = document.getElementById('last-update');
                if (lastUpdateEl) {
                    lastUpdateEl.textContent = new Date().toLocaleTimeString();
                }
            }
        } catch (error) {
            console.error('Error updating page data:', error);
        }
    }
    
    updateParticipantsList(participants) {
        const container = document.getElementById('participants-list');
        if (!container) return;
        
        const currentHtml = container.innerHTML;
        let newHtml = '';
        
        participants.forEach(participant => {
            const isOnline = this.isUserOnline(participant.name);
            newHtml += `
                <div class="participant-item ${isOnline ? 'online' : 'offline'}">
                    <div class="participant-name">
                        <span class="status-indicator ${isOnline ? 'status-online' : 'status-offline'}"></span>
                        ${this.escapeHtml(participant.name)}
                    </div>
                    <div class="participant-status">
                        ${isOnline ? 'Online' : 'Offline'}
                    </div>
                </div>
            `;
        });
        
        // Only update if content changed
        if (newHtml !== currentHtml) {
            container.innerHTML = newHtml;
        }
    }
    
    updateOnlineStatus(onlineUsers) {
        this.onlineUsers = onlineUsers;
        
        // Update online count
        const onlineCountEl = document.getElementById('online-count');
        if (onlineCountEl) {
            onlineCountEl.textContent = Object.keys(onlineUsers).length;
        }
    }
    
    updateStats(stats) {
        if (!stats) return;
        
        const elements = {
            'participant-count': stats.participant_count,
            'online-count': stats.online_count,
            'session-status': stats.status
        };
        
        Object.entries(elements).forEach(([id, value]) => {
            const el = document.getElementById(id);
            if (el) {
                el.textContent = value;
            }
        });
    }
    
    isUserOnline(username) {
        return this.onlineUsers && this.onlineUsers.hasOwnProperty(username);
    }
    
    updateHeartbeat() {
        if (!this.sessionId || !this.username) return;
        
        // Send heartbeat to keep user marked as online
        fetch('api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=heartbeat&session_id=${this.sessionId}&username=${encodeURIComponent(this.username)}`
        }).catch(error => {
            console.error('Heartbeat error:', error);
        });
    }
    
    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} notification`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            max-width: 300px;
            animation: slideIn 0.3s ease-out;
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 4 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-in';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 4000);
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    cleanup() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.wichtelomat = new Wichtelomat();
});

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    .notification {
        transition: all 0.3s ease;
    }
    
    .form-control.is-valid {
        border-color: #28a745;
    }
    
    .form-control.is-invalid {
        border-color: #dc3545;
    }
    
    .spinner {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid transparent;
        border-top: 2px solid currentColor;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
`;
document.head.appendChild(style);