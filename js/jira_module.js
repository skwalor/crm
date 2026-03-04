/**
 * IntePros Federal Celios.AI CRM - Jira Integration Module
 * 
 * This file handles all frontend Jira functionality including:
 * - Connection management
 * - Board display (Kanban + List views)
 * - Issue editing
 * - CRM linking
 * 
 * Add this script to your index.html/index.php
 */

const JiraModule = {
    
    // State
    connected: false,
    user: null,
    projectData: null,
    issues: [],
    currentView: 'kanban',
    selectedIssue: null,
    
    // ========================================================================
    // INITIALIZATION
    // ========================================================================
    
    async init() {
        this.handleOAuthCallback();
        await this.checkConnection();
        this.renderReportsTab();
    },
    
    handleOAuthCallback() {
        const urlParams = new URLSearchParams(window.location.search);
        
        if (urlParams.has('jira_success')) {
            this.showNotification(urlParams.get('jira_success'), 'success');
            window.history.replaceState({}, '', window.location.pathname);
        }
        
        if (urlParams.has('jira_error')) {
            this.showNotification('Jira Error: ' + urlParams.get('jira_error'), 'error');
            window.history.replaceState({}, '', window.location.pathname);
        }
    },
    
    // ========================================================================
    // API CALLS
    // ========================================================================
    
    async apiCall(action, params = {}) {
        try {
            // Build query string
            const queryParams = new URLSearchParams();
            queryParams.append('action', action);
            
            for (const [key, value] of Object.entries(params)) {
                queryParams.append(key, typeof value === 'object' ? JSON.stringify(value) : value);
            }
            
            const response = await fetch('jira_api.php?' + queryParams.toString());
            const text = await response.text();
            
            // Debug logging
            console.log(`Jira API [${action}]:`, text.substring(0, 200));
            
            if (!text) {
                throw new Error('Empty response from server');
            }
            
            const data = JSON.parse(text);
            
            if (!data.success && data.error) {
                throw new Error(data.error);
            }
            
            return data;
            
        } catch (error) {
            console.error('Jira API Error:', error);
            throw error;
        }
    },
    
    async checkConnection() {
        try {
            const data = await this.apiCall('getConnectionStatus');
            this.connected = data.connected;
            this.user = data.user || null;
            return data;
        } catch (error) {
            this.connected = false;
            this.user = null;
            return { connected: false };
        }
    },
    
    async connect() {
        try {
            // Use GET request directly like the manual test that worked
            const response = await fetch('jira_api.php?action=getAuthUrl');
            const text = await response.text();
            
            // Debug: log what we received
            console.log('Jira getAuthUrl response:', text);
            
            if (!text) {
                throw new Error('Empty response from server');
            }
            
            const data = JSON.parse(text);
            
            if (data.authUrl) {
                window.location.href = data.authUrl;
            } else if (data.error) {
                throw new Error(data.error);
            } else {
                throw new Error('No auth URL returned');
            }
        } catch (error) {
            console.error('Jira connect error:', error);
            this.showNotification('Failed to start Jira connection: ' + error.message, 'error');
        }
    },
    
    async disconnect() {
        if (!confirm('Are you sure you want to disconnect from Jira?')) {
            return;
        }
        
        try {
            await this.apiCall('disconnect');
            this.connected = false;
            this.user = null;
            this.projectData = null;
            this.issues = [];
            this.renderReportsTab();
            this.showNotification('Disconnected from Jira', 'success');
        } catch (error) {
            this.showNotification('Failed to disconnect: ' + error.message, 'error');
        }
    },
    
    async loadProjectData() {
        try {
            const data = await this.apiCall('getProjectData');
            this.projectData = data.data;
            return this.projectData;
        } catch (error) {
            this.showNotification('Failed to load project data: ' + error.message, 'error');
            throw error;
        }
    },
    
    async loadBoardIssues() {
        try {
            this.showLoading(true);
            const data = await this.apiCall('getBoardIssues');
            this.issues = data.issues || [];
            this.renderBoard();
        } catch (error) {
            this.showNotification('Failed to load issues: ' + error.message, 'error');
        } finally {
            this.showLoading(false);
        }
    },
    
    async refreshBoard() {
        await this.apiCall('refreshProjectCache');
        await this.loadProjectData();
        await this.loadBoardIssues();
        this.showNotification('Board refreshed', 'success');
    },
    
    // ========================================================================
    // ISSUE OPERATIONS
    // ========================================================================
    
    async openIssueModal(issueKey) {
        try {
            this.showLoading(true);
            const data = await this.apiCall('getIssue', { issueKey });
            this.selectedIssue = data.issue;
            this.renderIssueModal(data.issue, data.transitions, data.crmLinks);
        } catch (error) {
            this.showNotification('Failed to load issue: ' + error.message, 'error');
        } finally {
            this.showLoading(false);
        }
    },
    
    async transitionIssue(issueKey, transitionId) {
        try {
            await this.apiCall('transitionIssue', { issueKey, transitionId });
            this.showNotification('Issue status updated', 'success');
            await this.loadBoardIssues();
            this.closeIssueModal();
        } catch (error) {
            this.showNotification('Failed to update status: ' + error.message, 'error');
        }
    },
    
    async assignIssue(issueKey, accountId) {
        try {
            await this.apiCall('assignIssue', { issueKey, accountId });
            this.showNotification('Issue assigned', 'success');
            await this.loadBoardIssues();
        } catch (error) {
            this.showNotification('Failed to assign issue: ' + error.message, 'error');
        }
    },
    
    async addComment(issueKey, comment) {
        try {
            await this.apiCall('addComment', { issueKey, comment });
            this.showNotification('Comment added', 'success');
            if (this.selectedIssue?.key === issueKey) {
                await this.openIssueModal(issueKey);
            }
        } catch (error) {
            this.showNotification('Failed to add comment: ' + error.message, 'error');
        }
    },
    
    // ========================================================================
    // CRM LINKING
    // ========================================================================
    
    async linkIssueToCRM(issueKey, recordType, recordId) {
        try {
            await this.apiCall('linkIssueToCRM', { issueKey, recordType, recordId });
            this.showNotification('Issue linked to CRM record', 'success');
            await this.loadBoardIssues();
            if (this.selectedIssue?.key === issueKey) {
                await this.openIssueModal(issueKey);
            }
        } catch (error) {
            this.showNotification('Failed to link issue: ' + error.message, 'error');
        }
    },
    
    async unlinkIssueFromCRM(linkId) {
        if (!confirm('Remove this link?')) return;
        
        try {
            await this.apiCall('unlinkIssueFromCRM', { linkId });
            this.showNotification('Link removed', 'success');
            await this.loadBoardIssues();
            if (this.selectedIssue) {
                await this.openIssueModal(this.selectedIssue.key);
            }
        } catch (error) {
            this.showNotification('Failed to remove link: ' + error.message, 'error');
        }
    },
    
    async searchCRMRecords(recordType, search) {
        try {
            const data = await this.apiCall('getCRMRecordsForLinking', { recordType, search });
            return data.records || [];
        } catch (error) {
            console.error('Failed to search CRM records:', error);
            return [];
        }
    },
    
    // ========================================================================
    // RENDERING
    // ========================================================================
    
    renderReportsTab() {
        const container = document.getElementById('reports');
        if (!container) return;
        
        if (!this.connected) {
            container.innerHTML = this.getDisconnectedHTML();
        } else {
            container.innerHTML = this.getConnectedHTML();
            this.loadProjectData().then(() => this.loadBoardIssues());
        }
    },
    
    getDisconnectedHTML() {
        return `
            <div class="jira-connect-container">
                <div class="jira-connect-card">
                    <div class="jira-logo">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none">
                            <path d="M11.53 2c0 2.4 1.97 4.35 4.35 4.35h1.78v1.7c0 2.4 1.94 4.34 4.34 4.35V2.84a.84.84 0 0 0-.84-.84h-9.63z" fill="#2684FF"/>
                            <path d="M6.77 6.82a4.36 4.36 0 0 0 4.34 4.38h1.8v1.7c0 2.4 1.93 4.35 4.33 4.35V7.65a.84.84 0 0 0-.83-.83H6.77z" fill="url(#jg1)"/>
                            <path d="M2 11.65c0 2.4 1.94 4.35 4.35 4.35h1.78v1.72c0 2.4 1.95 4.33 4.35 4.34V12.5a.85.85 0 0 0-.84-.85H2z" fill="url(#jg2)"/>
                            <defs>
                                <linearGradient id="jg1" x1="12.8" y1="6.9" x2="9" y2="10.7" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#0052CC"/><stop offset="1" stop-color="#2684FF"/>
                                </linearGradient>
                                <linearGradient id="jg2" x1="8" y1="11.7" x2="4.2" y2="15.5" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#0052CC"/><stop offset="1" stop-color="#2684FF"/>
                                </linearGradient>
                            </defs>
                        </svg>
                    </div>
                    <h2>Connect to Jira</h2>
                    <p>Link your Jira account to view and manage issues directly from the CRM.</p>
                    <button class="btn btn-primary jira-connect-btn" onclick="JiraModule.connect()">
                        Connect Jira Account
                    </button>
                </div>
            </div>
        `;
    },
    
    getConnectedHTML() {
        const userName = this.user?.displayName || 'Connected';
        const avatarUrl = this.user?.avatarUrl || '';
        
        return `
            <div class="jira-header">
                <div class="jira-header-left">
                    <h2>Jira Board</h2>
                    <span class="jira-project-badge">EL</span>
                </div>
                <div class="jira-header-right">
                    <div class="jira-view-toggle">
                        <button class="view-btn ${this.currentView === 'kanban' ? 'active' : ''}" 
                                onclick="JiraModule.setView('kanban')" title="Kanban View">▦</button>
                        <button class="view-btn ${this.currentView === 'list' ? 'active' : ''}" 
                                onclick="JiraModule.setView('list')" title="List View">☰</button>
                    </div>
                    <button class="btn btn-secondary" onclick="JiraModule.refreshBoard()">↻ Refresh</button>
                    <div class="jira-user-badge">
                        ${avatarUrl ? `<img src="${avatarUrl}" alt="${userName}" class="jira-user-avatar">` : ''}
                        <span>${userName}</span>
                        <button class="btn-icon" onclick="JiraModule.disconnect()" title="Disconnect">✕</button>
                    </div>
                </div>
            </div>
            <div id="jira-board-container" class="jira-board-container">
                <div class="jira-loading">Loading board...</div>
            </div>
            
            <div id="jiraIssueModal" class="modal jira-modal">
                <div class="modal-content jira-modal-content">
                    <span class="close" onclick="JiraModule.closeIssueModal()">&times;</span>
                    <div id="jiraIssueModalBody"></div>
                </div>
            </div>
            
            <div id="jiraLinkModal" class="modal jira-modal">
                <div class="modal-content jira-link-modal-content">
                    <span class="close" onclick="JiraModule.closeLinkModal()">&times;</span>
                    <div id="jiraLinkModalBody"></div>
                </div>
            </div>
        `;
    },
    
    setView(view) {
        this.currentView = view;
        document.querySelectorAll('.view-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelector(`.view-btn[onclick*="${view}"]`)?.classList.add('active');
        this.renderBoard();
    },
    
    renderBoard() {
        const container = document.getElementById('jira-board-container');
        if (!container) return;
        
        if (this.currentView === 'kanban') {
            this.renderKanbanBoard(container);
        } else {
            this.renderListView(container);
        }
    },
    
    renderKanbanBoard(container) {
        const statusGroups = {};
        const statusOrder = ['To Do', 'In Progress', 'Done'];
        
        statusOrder.forEach(status => { statusGroups[status] = []; });
        
        this.issues.forEach(issue => {
            const status = issue.fields?.status?.name || 'To Do';
            const normalizedStatus = this.normalizeStatus(status);
            if (!statusGroups[normalizedStatus]) statusGroups[normalizedStatus] = [];
            statusGroups[normalizedStatus].push(issue);
        });
        
        let html = '<div class="jira-kanban">';
        
        for (const [status, issues] of Object.entries(statusGroups)) {
            const statusColor = this.getStatusColor(status);
            html += `
                <div class="jira-kanban-column">
                    <div class="jira-kanban-header" style="border-top: 3px solid ${statusColor}">
                        <span class="status-name">${status}</span>
                        <span class="status-count">${issues.length}</span>
                    </div>
                    <div class="jira-kanban-cards">
                        ${issues.map(issue => this.renderIssueCard(issue)).join('')}
                    </div>
                </div>
            `;
        }
        
        html += '</div>';
        container.innerHTML = html;
    },
    
    renderListView(container) {
        let html = `
            <table class="data-table jira-list-table">
                <thead>
                    <tr>
                        <th>Key</th>
                        <th>Summary</th>
                        <th>Status</th>
                        <th>Assignee</th>
                        <th>Priority</th>
                        <th>CRM Links</th>
                        <th>Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        this.issues.forEach(issue => {
            const assignee = issue.fields?.assignee?.displayName || 'Unassigned';
            const status = issue.fields?.status?.name || '-';
            const priority = issue.fields?.priority?.name || '-';
            const updated = issue.fields?.updated ? new Date(issue.fields.updated).toLocaleDateString() : '-';
            const crmLinks = issue.crmLinks || [];
            
            html += `
                <tr onclick="JiraModule.openIssueModal('${issue.key}')" style="cursor: pointer;">
                    <td><span class="jira-issue-key">${issue.key}</span></td>
                    <td>${this.escapeHtml(issue.fields?.summary || '')}</td>
                    <td><span class="jira-status-badge" style="background: ${this.getStatusColor(status)}">${status}</span></td>
                    <td>${this.escapeHtml(assignee)}</td>
                    <td>${priority}</td>
                    <td>${crmLinks.length > 0 ? `<span class="crm-link-badge">${crmLinks.length} linked</span>` : '-'}</td>
                    <td>${updated}</td>
                    <td>
                        <button class="btn btn-sm" onclick="event.stopPropagation(); JiraModule.openLinkModal('${issue.key}')">🔗</button>
                    </td>
                </tr>
            `;
        });
        
        html += '</tbody></table>';
        container.innerHTML = html;
    },
    
    renderIssueCard(issue) {
        const assignee = issue.fields?.assignee;
        const priority = issue.fields?.priority?.name || '';
        const type = issue.fields?.issuetype?.name || 'Task';
        const crmLinks = issue.crmLinks || [];
        
        return `
            <div class="jira-card" onclick="JiraModule.openIssueModal('${issue.key}')">
                <div class="jira-card-header">
                    <span class="jira-issue-type">${this.getTypeIcon(type)}</span>
                    <span class="jira-issue-key">${issue.key}</span>
                    ${priority ? `<span class="jira-priority">${this.getPriorityIcon(priority)}</span>` : ''}
                </div>
                <div class="jira-card-summary">${this.escapeHtml(issue.fields?.summary || '')}</div>
                <div class="jira-card-footer">
                    ${assignee ? `
                        <div class="jira-assignee" title="${this.escapeHtml(assignee.displayName)}">
                            ${assignee.avatarUrls?.['24x24'] ? 
                                `<img src="${assignee.avatarUrls['24x24']}" alt="">` :
                                `<span class="jira-avatar-placeholder">${assignee.displayName?.charAt(0) || '?'}</span>`}
                        </div>
                    ` : ''}
                    ${crmLinks.length > 0 ? `<span class="crm-link-indicator" title="${crmLinks.length} CRM link(s)">🔗</span>` : ''}
                </div>
            </div>
        `;
    },
    
    renderIssueModal(issue, transitions, crmLinks) {
        const modal = document.getElementById('jiraIssueModal');
        const body = document.getElementById('jiraIssueModalBody');
        
        const assignee = issue.fields?.assignee;
        const status = issue.fields?.status?.name || 'Unknown';
        const priority = issue.fields?.priority?.name || 'None';
        const type = issue.fields?.issuetype?.name || 'Task';
        const description = issue.fields?.description?.content?.[0]?.content?.[0]?.text || 'No description';
        const comments = issue.fields?.comment?.comments || [];
        
        body.innerHTML = `
            <div class="jira-issue-header">
                <span class="jira-issue-type-badge">${type}</span>
                <a href="https://inteprosfed.atlassian.net/browse/${issue.key}" target="_blank" class="jira-issue-key-link">${issue.key} ↗</a>
            </div>
            
            <h2 class="jira-issue-title">${this.escapeHtml(issue.fields?.summary || '')}</h2>
            
            <div class="jira-issue-meta">
                <div class="jira-meta-item">
                    <label>Status</label>
                    <span class="jira-status-badge" style="background: ${this.getStatusColor(status)}">${status}</span>
                    <select onchange="JiraModule.transitionIssue('${issue.key}', this.value)">
                        <option value="">Change...</option>
                        ${transitions.map(t => `<option value="${t.id}">${t.name}</option>`).join('')}
                    </select>
                </div>
                <div class="jira-meta-item">
                    <label>Assignee</label>
                    <span>${assignee ? this.escapeHtml(assignee.displayName) : 'Unassigned'}</span>
                </div>
                <div class="jira-meta-item">
                    <label>Priority</label>
                    <span>${priority}</span>
                </div>
            </div>
            
            <div class="jira-issue-section">
                <h3>Description</h3>
                <div class="jira-description">${this.escapeHtml(description)}</div>
            </div>
            
            <div class="jira-issue-section">
                <h3>CRM Links</h3>
                <div class="jira-crm-links">
                    ${crmLinks.length > 0 ? crmLinks.map(link => `
                        <div class="jira-crm-link-item">
                            <span class="link-type">${link.opportunity_id ? 'Opportunity' : link.proposal_id ? 'Proposal' : 'Task'}</span>
                            <span class="link-title">${this.escapeHtml(link.opportunity_title || link.proposal_title || link.task_title || 'Unknown')}</span>
                            <button class="btn-icon" onclick="JiraModule.unlinkIssueFromCRM(${link.id})">✕</button>
                        </div>
                    `).join('') : '<p class="no-links">No CRM records linked</p>'}
                    <button class="btn btn-secondary" onclick="JiraModule.openLinkModal('${issue.key}')">+ Link to CRM Record</button>
                </div>
            </div>
            
            <div class="jira-issue-section">
                <h3>Comments (${comments.length})</h3>
                <div class="jira-comments">
                    ${comments.slice(-5).map(comment => `
                        <div class="jira-comment">
                            <div class="comment-header">
                                <strong>${this.escapeHtml(comment.author?.displayName || 'Unknown')}</strong>
                                <span>${new Date(comment.created).toLocaleString()}</span>
                            </div>
                            <div class="comment-body">${this.escapeHtml(this.extractCommentText(comment.body))}</div>
                        </div>
                    `).join('')}
                </div>
                <div class="jira-add-comment">
                    <textarea id="newJiraComment" placeholder="Add a comment..."></textarea>
                    <button class="btn btn-primary" onclick="JiraModule.submitComment('${issue.key}')">Add Comment</button>
                </div>
            </div>
        `;
        
        modal.style.display = 'block';
    },
    
    extractCommentText(body) {
        if (typeof body === 'string') return body;
        if (body?.content?.[0]?.content?.[0]?.text) return body.content[0].content[0].text;
        return '';
    },
    
    async submitComment(issueKey) {
        const textarea = document.getElementById('newJiraComment');
        const comment = textarea.value.trim();
        if (!comment) return;
        await this.addComment(issueKey, comment);
        textarea.value = '';
    },
    
    closeIssueModal() {
        const modal = document.getElementById('jiraIssueModal');
        if (modal) modal.style.display = 'none';
        this.selectedIssue = null;
    },
    
    openLinkModal(issueKey) {
        const modal = document.getElementById('jiraLinkModal');
        const body = document.getElementById('jiraLinkModalBody');
        
        body.innerHTML = `
            <h2>Link ${issueKey} to CRM Record</h2>
            <div class="jira-link-form">
                <div class="form-group">
                    <label>Record Type</label>
                    <select id="linkRecordType" onchange="JiraModule.onRecordTypeChange()">
                        <option value="">Select type...</option>
                        <option value="opportunity">Opportunity</option>
                        <option value="proposal">Proposal</option>
                        <option value="task">Task</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Search Records</label>
                    <input type="text" id="linkRecordSearch" placeholder="Type to search..." 
                           oninput="JiraModule.searchRecordsDebounced()" disabled>
                </div>
                <div id="linkRecordResults" class="link-record-results"></div>
                <input type="hidden" id="linkIssueKey" value="${issueKey}">
            </div>
        `;
        
        modal.style.display = 'block';
    },
    
    closeLinkModal() {
        const modal = document.getElementById('jiraLinkModal');
        if (modal) modal.style.display = 'none';
    },
    
    onRecordTypeChange() {
        const searchInput = document.getElementById('linkRecordSearch');
        const recordType = document.getElementById('linkRecordType').value;
        searchInput.disabled = !recordType;
        if (recordType) {
            searchInput.focus();
            this.searchRecordsDebounced();
        }
    },
    
    searchDebounceTimer: null,
    
    searchRecordsDebounced() {
        clearTimeout(this.searchDebounceTimer);
        this.searchDebounceTimer = setTimeout(() => this.searchRecords(), 300);
    },
    
    async searchRecords() {
        const recordType = document.getElementById('linkRecordType').value;
        const search = document.getElementById('linkRecordSearch').value;
        const resultsContainer = document.getElementById('linkRecordResults');
        
        if (!recordType) { resultsContainer.innerHTML = ''; return; }
        
        const records = await this.searchCRMRecords(recordType, search);
        const issueKey = document.getElementById('linkIssueKey').value;
        
        resultsContainer.innerHTML = records.length === 0 
            ? '<p class="no-results">No records found</p>'
            : records.map(record => `
                <div class="link-record-item" onclick="JiraModule.selectRecordToLink('${issueKey}', '${recordType}', ${record.id})">
                    <span class="record-title">${this.escapeHtml(record.title)}</span>
                    <span class="record-status">${record.status}</span>
                </div>
            `).join('');
    },
    
    async selectRecordToLink(issueKey, recordType, recordId) {
        await this.linkIssueToCRM(issueKey, recordType, recordId);
        this.closeLinkModal();
    },
    
    // ========================================================================
    // UTILITIES
    // ========================================================================
    
    normalizeStatus(status) {
        const map = { 'to do': 'To Do', 'open': 'To Do', 'backlog': 'To Do', 'in progress': 'In Progress',
                      'in development': 'In Progress', 'in review': 'In Progress', 'review': 'In Progress',
                      'done': 'Done', 'closed': 'Done', 'resolved': 'Done' };
        return map[status.toLowerCase()] || status;
    },
    
    getStatusColor(status) {
        const colors = { 'To Do': '#6B778C', 'In Progress': '#0052CC', 'Done': '#36B37E' };
        return colors[this.normalizeStatus(status)] || '#6B778C';
    },
    
    getTypeIcon(type) {
        const icons = { 'story': '📖', 'bug': '🐛', 'task': '✓', 'epic': '⚡', 'subtask': '↳' };
        return icons[type.toLowerCase()] || '📋';
    },
    
    getPriorityIcon(priority) {
        const icons = { 'highest': '⬆️', 'high': '↑', 'medium': '➡️', 'low': '↓', 'lowest': '⬇️' };
        return icons[priority.toLowerCase()] || '';
    },
    
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },
    
    showLoading(show) {
        const container = document.getElementById('jira-board-container');
        if (!container) return;
        if (show && !container.querySelector('.jira-loading-overlay')) {
            container.insertAdjacentHTML('afterbegin', '<div class="jira-loading-overlay"><div class="jira-loading">Loading...</div></div>');
        } else if (!show) {
            container.querySelector('.jira-loading-overlay')?.remove();
        }
    },
    
    showNotification(message, type = 'info') {
        if (typeof showNotification === 'function') { showNotification(message, type); return; }
        const notification = document.createElement('div');
        notification.className = `jira-notification jira-notification-${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => notification.classList.add('show'), 10);
        setTimeout(() => { notification.classList.remove('show'); setTimeout(() => notification.remove(), 300); }, 3000);
    }
};

// Auto-initialize
document.addEventListener('DOMContentLoaded', function() {
    const originalShowTab = window.showTab;
    if (originalShowTab) {
        window.showTab = function(tabName, element) {
            originalShowTab(tabName, element);
            if (tabName === 'reports') JiraModule.init();
        };
    }
    const reportsTab = document.getElementById('reports');
    if (reportsTab && reportsTab.classList.contains('active')) JiraModule.init();
});

window.JiraModule = JiraModule;
