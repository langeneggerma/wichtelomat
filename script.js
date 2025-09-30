// Wichtelomat JavaScript for real-time updates and modern UX
class Wichtelomat {
    constructor() {
        this.sessionId = this.getSessionId();
        this.username = localStorage.getItem('wichtel_username');
        this.updateInterval = null;
        this.lastUpdateTime = 0;
        
        this.init();
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
    
    createAssignmentHTML(assignment) {
        return `
            <div class="alert alert-success">
                <strong>🎁 Deine Zuordnung:</strong><br>
                Du beschenkst: <strong>${this.escapeHtml(assignment)}</strong>
            </div>
            <div class="alert alert-info" style="margin-top: 1rem;">
                <strong>🤫 Pssst!</strong><br>
                Das ist nur für dich bestimmt. Jeder andere sieht nur seine eigene Zuordnung - 
                so bleibt es für alle eine Überraschung! 🎉
            </div>
            <div class="alert alert-secondary" style="margin-top: 1rem;">
                <strong>💾 Dauerhaft gespeichert</strong><br>
                Deine Zuordnung bleibt auch nach dem Neuladen der Seite sichtbar.
                <br>
                <button class="btn btn-sm btn-secondary" onclick="window.wichtelomat.forgetAssignment()" style="margin-top: 0.5rem;">
                    🗑️ Zuordnung vergessen
                </button>
            </div>
        `;
    }
    
    forgetAssignment() {
        if (confirm('Möchtest du deine Zuordnung wirklich aus dem Browser löschen? Du kannst sie durch Neuladen der Seite wieder anzeigen lassen.')) {
            this.clearStoredAssignment();
            this.showNotification('Zuordnung aus Browser gelöscht. Lade die Seite neu, um sie wieder anzuzeigen.', 'info');
            
            // Hide assignment display
            const userAssignmentEl = document.getElementById('user-assignment');
            if (userAssignmentEl) {
                userAssignmentEl.innerHTML = `
                    <div class="alert alert-info">
                        <strong>🔄 Zuordnung vergessen</strong><br>
                        Lade die Seite neu, um deine Zuordnung wieder anzuzeigen.
                        <br>
                        <button class="btn btn-primary" onclick="window.location.reload()" style="margin-top: 0.5rem;">
                            🔄 Seite neu laden
                        </button>
                    </div>
                `;
            }
        }
    }
    
    getStoredAssignment() {
        if (!this.sessionId || !this.username) return null;
        const assignmentKey = `wichtel_assignment_${this.sessionId}_${this.username}`;
        return localStorage.getItem(assignmentKey);
    }
    
    clearStoredAssignment() {
        if (!this.sessionId || !this.username) return;
        const assignmentKey = `wichtel_assignment_${this.sessionId}_${this.username}`;
        localStorage.removeItem(assignmentKey);
    }
    
    init() {
        this.setupEventListeners();
        this.assignmentsWereReady = false; // Track if assignments were already ready
        
        // Load stored assignment on page load - do this first
        this.loadStoredAssignmentOnInit();
        
        // Then start auto-updates and heartbeat
        this.startAutoUpdate();
        this.updateHeartbeat();
    }
    
    loadStoredAssignmentOnInit() {
        // Always check for stored assignment on page load
        setTimeout(() => {
            console.log('Loading stored assignment on init...');
            console.log('Username:', this.username);
            console.log('Session ID:', this.sessionId);
            
            if (!this.username || !this.sessionId) {
                console.log('Username or session ID missing, skipping stored assignment check');
                return;
            }
            
            // Check for stored assignment
            const storedAssignment = this.getStoredAssignment();
            console.log('Stored assignment:', storedAssignment);
            
            const userAssignmentEl = document.getElementById('user-assignment');
            if (!userAssignmentEl) {
                console.log('user-assignment element not found');
                return;
            }
            
            if (storedAssignment) {
                // Immediately show stored assignment
                console.log('Displaying stored assignment immediately');
                userAssignmentEl.innerHTML = this.createAssignmentHTML(storedAssignment);
                
                // Also check if assignment section should be visible
                const assignmentSection = document.getElementById('assignment-section');
                if (assignmentSection) {
                    assignmentSection.style.display = 'block';
                }
                
                const waitingSection = document.getElementById('waiting-section');
                if (waitingSection) {
                    waitingSection.style.display = 'none';
                }
            }
            
            // Always update page data to get current session status
            this.updatePageData();
        }, 100); // Small delay to ensure DOM is ready
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
        
        // Clear stored assignment on reset
        if (action === 'reset') {
            this.clearStoredAssignment();
            // Clear all stored assignments for this session
            this.clearAllStoredAssignmentsForSession();
        }
    }
    
    clearAllStoredAssignmentsForSession() {
        if (!this.sessionId) return;
        
        // Find and remove all localStorage items for this session
        const keysToRemove = [];
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            if (key && key.startsWith(`wichtel_assignment_${this.sessionId}_`)) {
                keysToRemove.push(key);
            }
        }
        
        keysToRemove.forEach(key => localStorage.removeItem(key));
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
            const params = new URLSearchParams({
                action: 'get_session_data',
                session_id: this.sessionId,
                t: Date.now()
            });
            
            // Add username if available for personal assignment
            if (this.username) {
                params.append('username', this.username);
            }
            
            const response = await fetch(`api.php?${params}`);
            const data = await response.json();
            
            if (data.success) {
                this.updateParticipantsList(data.participants);
                this.updateOnlineStatus(data.online_users);
                this.updateStats(data.stats);
                this.updateAssignmentStatus(data.status, data.user_assignment, data.assignments_ready);
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
    
    updateAssignmentStatus(status, userAssignment, assignmentsReady) {
        // Update assignment display
        const assignmentSection = document.getElementById('assignment-section');
        const waitingSection = document.getElementById('waiting-section');
        
        if (status === 'started' && assignmentsReady) {
            // Show assignment section, hide waiting section
            if (assignmentSection) {
                assignmentSection.style.display = 'block';
                if (!this.assignmentsWereReady) {
                    assignmentSection.classList.add('assignment-reveal');
                    setTimeout(() => assignmentSection.classList.remove('assignment-reveal'), 600);
                }
            }
            if (waitingSection) {
                waitingSection.style.display = 'none';
            }
            
            // Store assignment in localStorage for persistence (new assignment from API)
            if (userAssignment && this.username) {
                const assignmentKey = `wichtel_assignment_${this.sessionId}_${this.username}`;
                const timestampKey = assignmentKey + '_timestamp';
                localStorage.setItem(assignmentKey, userAssignment);
                localStorage.setItem(timestampKey, Date.now().toString());
                console.log('Stored new assignment:', userAssignment);
            }
            
            // Update user's personal assignment display
            const userAssignmentEl = document.getElementById('user-assignment');
            if (userAssignmentEl) {
                // Priority 1: Use assignment from API
                if (userAssignment) {
                    userAssignmentEl.innerHTML = this.createAssignmentHTML(userAssignment);
                    console.log('Displayed assignment from API:', userAssignment);
                } 
                // Priority 2: Use stored assignment if no API assignment but user exists
                else if (this.username) {
                    const storedAssignment = this.getStoredAssignment();
                    if (storedAssignment) {
                        userAssignmentEl.innerHTML = this.createAssignmentHTML(storedAssignment);
                        console.log('Displayed stored assignment:', storedAssignment);
                    } else {
                        userAssignmentEl.innerHTML = `
                            <div class="alert alert-warning">
                                <strong>⚠️ Keine Zuordnung gefunden</strong><br>
                                Du musst dich zuerst als Teilnehmer eintragen!
                            </div>
                        `;
                    }
                } 
                // Priority 3: No user set
                else {
                    userAssignmentEl.innerHTML = `
                        <div class="alert alert-info">
                            <strong>👋 Willkommen!</strong><br>
                            Trage dich als Teilnehmer ein, um deine Zuordnung zu sehen.
                        </div>
                    `;
                }
            }
            
            // Show notification if assignments just became ready
            if (!this.assignmentsWereReady && assignmentsReady) {
                this.showNotification('🎉 Wichtelomat gestartet! Deine persönliche Zuordnung ist bereit - nur für dich sichtbar! 🤫', 'success');
                this.assignmentsWereReady = true;
            }
        } else {
            // Hide assignment section, show waiting section
            if (assignmentSection) {
                assignmentSection.style.display = 'none';
            }
            if (waitingSection) {
                waitingSection.style.display = 'block';
            }
        }
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
        
        // Optional: Clean up old localStorage entries (older than 7 days)
        this.cleanupOldStoredAssignments();
    }
    
    cleanupOldStoredAssignments() {
        const now = Date.now();
        const weekInMs = 7 * 24 * 60 * 60 * 1000; // 7 days
        
        const keysToRemove = [];
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            if (key && key.startsWith('wichtel_assignment_')) {
                // Check if we have a timestamp for this entry
                const timestampKey = key + '_timestamp';
                const timestamp = localStorage.getItem(timestampKey);
                
                if (timestamp) {
                    if (now - parseInt(timestamp) > weekInMs) {
                        keysToRemove.push(key);
                        keysToRemove.push(timestampKey);
                    }
                } else {
                    // If no timestamp, assume it's old and remove
                    keysToRemove.push(key);
                }
            }
        }
        
        keysToRemove.forEach(key => localStorage.removeItem(key));
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded, initializing Wichtelomat...');
    window.wichtelomat = new Wichtelomat();
    
    // Force immediate check for stored assignments after initialization
    setTimeout(() => {
        const wichtelomat = window.wichtelomat;
        if (wichtelomat && wichtelomat.username && wichtelomat.sessionId) {
            console.log('Post-init: Checking for stored assignment...');
            const storedAssignment = wichtelomat.getStoredAssignment();
            
            if (storedAssignment) {
                console.log('Post-init: Found stored assignment:', storedAssignment);
                const userAssignmentEl = document.getElementById('user-assignment');
                const assignmentSection = document.getElementById('assignment-section');
                
                if (userAssignmentEl) {
                    // Force display of stored assignment
                    userAssignmentEl.innerHTML = wichtelomat.createAssignmentHTML(storedAssignment);
                    console.log('Post-init: Displayed stored assignment');
                    
                    // Ensure assignment section is visible if we have an assignment
                    if (assignmentSection) {
                        assignmentSection.style.display = 'block';
                        console.log('Post-init: Made assignment section visible');
                    }
                    
                    // Hide waiting section since we have an assignment
                    const waitingSection = document.getElementById('waiting-section');
                    if (waitingSection) {
                        waitingSection.style.display = 'none';
                        console.log('Post-init: Hid waiting section');
                    }
                }
            } else {
                console.log('Post-init: No stored assignment found');
            }
        } else {
            console.log('Post-init: Username or session ID missing', {
                username: wichtelomat?.username,
                sessionId: wichtelomat?.sessionId
            });
        }
    }, 300); // Slightly longer delay to ensure everything is fully initialized
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