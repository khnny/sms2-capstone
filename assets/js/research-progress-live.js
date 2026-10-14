/**
 * Research Progress Live Updates
 * Read-only polling for real-time updates
 * 
 * IMPORTANT: This module ONLY READS data. It does NOT create, update, or delete records.
 * Polling is safe to run repeatedly without causing duplicates.
 */

(function() {
    'use strict';

    function debugLog() {
        if (!window.SMS2_DEBUG_LIVE || !window.console) return;
        console.log.apply(console, arguments);
    }

    const ResearchProgressLive = {
        config: {
            pollInterval: 10000, // 10 seconds
            apiBaseUrl: '/modules/crad/api',
            maxRetries: 3,
            retryDelay: 5000
        },

        /**
         * Resolve the app's base URL from the current page path so polling
         * works both at the domain root and inside a subdirectory install
         * (e.g. /sms2_system). The legacy hardcoded config.apiBaseUrl broke
         * every poll request on subdirectory installs.
         */
        resolveBaseUrl: function() {
            const match = window.location.pathname.match(/^(.*\/)modules\//);
            return match ? match[1] : '/';
        },

        /** Build an absolute API URL for the given endpoint file. */
        apiUrl: function(endpoint) {
            return this.resolveBaseUrl() + 'modules/crad/api/' + endpoint;
        },

        state: {
            isPolling: false,
            retryCount: 0,
            lastUpdateTimestamp: null,
            cache: {
                milestones: null,
                updates: null,
                feedback: null,
                groups: null
            }
        },

        timers: {
            pollTimer: null
        },

        /**
         * Initialize live updates for student pages
         */
        initStudent: function() {
            debugLog('[ResearchProgressLive] Initializing student live updates');
            
            // Start polling based on current page
            const currentPage = this.getCurrentPage();
            
            if (currentPage === 'my-research') {
                this.startPolling(this.pollStudentDashboard.bind(this));
            } else if (currentPage === 'milestones') {
                this.startPolling(this.pollStudentMilestones.bind(this));
            } else if (currentPage === 'adviser-feedback') {
                this.startPolling(this.pollStudentFeedback.bind(this));
            }
        },

        /**
         * Initialize live updates for adviser pages
         */
        initAdviser: function() {
            debugLog('[ResearchProgressLive] Initializing adviser live updates');
            
            const currentPage = this.getCurrentPage();
            
            if (currentPage === 'my-research-groups') {
                this.startPolling(this.pollAdviserGroups.bind(this));
            } else if (currentPage === 'submitted-updates') {
                this.startPolling(this.pollAdviserUpdates.bind(this));
            } else if (currentPage === 'research-progress-monitoring' || currentPage === 'milestones-overview' || currentPage === 'adviser-feedback-history') {
                this.startPolling(this.pollAdviserGroupProgress.bind(this));
            }
        },

        /**
         * Get current page identifier
         */
        getCurrentPage: function() {
            const path = window.location.pathname;
            const filename = path.substring(path.lastIndexOf('/') + 1).replace('.php', '');
            return filename;
        },

        /**
         * Start polling with a callback function
         * READ-ONLY: This only fetches data, never modifies it
         */
        startPolling: function(pollFunction) {
            if (this.state.isPolling) {
                debugLog('[ResearchProgressLive] Polling already active');
                return;
            }

            this.state.isPolling = true;
            this.state.lastUpdateTimestamp = Date.now();

            // Initial fetch
            pollFunction();

            // Set interval for recurring polls
            this.timers.pollTimer = setInterval(() => {
                if (document.visibilityState === 'visible') {
                    pollFunction();
                }
            }, this.config.pollInterval);

            debugLog('[ResearchProgressLive] Polling started');
        },

        /**
         * Stop polling
         */
        stopPolling: function() {
            if (this.timers.pollTimer) {
                clearInterval(this.timers.pollTimer);
                this.timers.pollTimer = null;
            }
            this.state.isPolling = false;
            debugLog('[ResearchProgressLive] Polling stopped');
        },

        /**
         * Poll student dashboard data (READ-ONLY)
         */
        pollStudentDashboard: async function() {
            try {
                const response = await fetch(
                    `${this.apiUrl('research-progress.php')}?action=get_research_plan&_=${Date.now()}`,
                    {
                        method: 'GET',
                        headers: { 'Accept': 'application/json' },
                        cache: 'no-store'
                    }
                );

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    this.updateStudentDashboardUI(data);
                    this.state.retryCount = 0;
                }

            } catch (error) {
                console.error('[ResearchProgressLive] Poll error:', error);
                this.handlePollError();
            }
        },

        /**
         * Poll student milestones (READ-ONLY)
         */
        pollStudentMilestones: async function() {
            try {
                const response = await fetch(
                    `${this.apiUrl('research-progress.php')}?action=get_milestones&_=${Date.now()}`,
                    {
                        method: 'GET',
                        headers: { 'Accept': 'application/json' },
                        cache: 'no-store'
                    }
                );

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();

                if (data.success && this.hasChanged(data.milestones, this.state.cache.milestones)) {
                    this.state.cache.milestones = data.milestones;
                    this.updateMilestonesUI(data.milestones);
                    this.state.retryCount = 0;
                }

            } catch (error) {
                console.error('[ResearchProgressLive] Poll error:', error);
                this.handlePollError();
            }
        },

        /**
         * Poll student feedback (READ-ONLY)
         */
        pollStudentFeedback: async function() {
            try {
                const response = await fetch(
                    `${this.apiUrl('research-progress.php')}?action=get_adviser_feedback&_=${Date.now()}`,
                    {
                        method: 'GET',
                        headers: { 'Accept': 'application/json' },
                        cache: 'no-store'
                    }
                );

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();

                if (data.success && this.hasChanged(data.feedback, this.state.cache.feedback)) {
                    this.state.cache.feedback = data.feedback;
                    this.updateFeedbackUI(data.feedback);
                    this.showNotification('New feedback from your adviser', 'info');
                    this.state.retryCount = 0;
                }

            } catch (error) {
                console.error('[ResearchProgressLive] Poll error:', error);
                this.handlePollError();
            }
        },

        /**
         * Poll adviser groups (READ-ONLY)
         */
        pollAdviserGroups: async function() {
            try {
                const response = await fetch(
                    `${this.apiUrl('adviser-progress.php')}?action=get_assigned_groups&_=${Date.now()}`,
                    {
                        method: 'GET',
                        headers: { 'Accept': 'application/json' },
                        cache: 'no-store'
                    }
                );

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();

                if (data.success && this.hasChanged(data.groups, this.state.cache.groups)) {
                    this.state.cache.groups = data.groups;
                    this.updateAdviserGroupsUI(data.groups);
                    this.state.retryCount = 0;
                }

            } catch (error) {
                console.error('[ResearchProgressLive] Poll error:', error);
                this.handlePollError();
            }
        },

        /**
         * Poll adviser updates (READ-ONLY)
         */
        pollAdviserUpdates: async function() {
            try {
                const response = await fetch(
                    `${this.apiUrl('adviser-progress.php')}?action=get_progress_updates&_=${Date.now()}`,
                    {
                        method: 'GET',
                        headers: { 'Accept': 'application/json' },
                        cache: 'no-store'
                    }
                );

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();

                if (data.success && this.hasChanged(data.updates, this.state.cache.updates)) {
                    this.state.cache.updates = data.updates;
                    this.updateAdviserUpdatesUI(data.updates);
                    this.showNotification('New progress update submitted', 'info');
                    this.state.retryCount = 0;
                }

            } catch (error) {
                console.error('[ResearchProgressLive] Poll error:', error);
                this.handlePollError();
            }
        },

        /**
         * Poll specific group progress (READ-ONLY)
         */
        pollAdviserGroupProgress: async function() {
            const urlParams = new URLSearchParams(window.location.search);
            const pageRoot = document.querySelector('[data-group-number]');
            const groupNumber = urlParams.get('group') || (pageRoot ? pageRoot.getAttribute('data-group-number') : '');

            if (!groupNumber) return;

            try {
                const response = await fetch(
                    `${this.apiUrl('adviser-progress.php')}?action=get_group_progress&group_number=${encodeURIComponent(groupNumber)}&_=${Date.now()}`,
                    {
                        method: 'GET',
                        headers: { 'Accept': 'application/json' },
                        cache: 'no-store'
                    }
                );

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    this.updateAdviserGroupProgressUI(data);
                    this.state.retryCount = 0;
                }

            } catch (error) {
                console.error('[ResearchProgressLive] Poll error:', error);
                this.handlePollError();
            }
        },

        /**
         * Check if data has changed (simple comparison)
         */
        hasChanged: function(newData, cachedData) {
            if (!cachedData) return true;
            return JSON.stringify(newData) !== JSON.stringify(cachedData);
        },

        /**
         * Update student dashboard UI
         */
        updateStudentDashboardUI: function(data) {
            // Update overall progress
            const progressBar = document.querySelector('[data-overall-progress-bar]');
            const progressTexts = document.querySelectorAll('[data-overall-progress-text]');
            
            if (progressBar && data.plan) {
                const progress = parseFloat(data.plan.overall_progress);
                progressBar.style.width = `${progress}%`;
                progressBar.setAttribute('aria-valuenow', progress);
                
                progressTexts.forEach(progressText => {
                    progressText.textContent = `${progress.toFixed(1)}%`;
                });
            }

            // Update milestones if container exists
            const milestonesContainer = document.querySelector('[data-milestones-container]');
            if (milestonesContainer && data.milestones) {
                // Only update if changed
                if (this.hasChanged(data.milestones, this.state.cache.milestones)) {
                    this.state.cache.milestones = data.milestones;
                    this.updateMilestonesUI(data.milestones);
                    // Trigger UI update event
                    document.dispatchEvent(new CustomEvent('research:milestones-updated', { 
                        detail: { milestones: data.milestones } 
                    }));
                }
            }

            // Update last refresh indicator
            this.updateLastRefreshTime();
        },

        /**
         * Update milestones UI
         */
        updateMilestonesUI: function(milestones) {
            milestones.forEach(milestone => {
                const card = document.querySelector(`[data-milestone-id="${milestone.id}"]`);
                if (card) {
                    // Update progress bar
                    const progressBar = card.querySelector('[data-milestone-progress-bar], .progress-bar, .rm-progress-fill, .rm-milestone-fill');
                    const progressText = card.querySelector('[data-milestone-progress-text], .rm-milestone-header-pct');
                    if (progressBar) {
                        const progress = parseFloat(milestone.progress_percentage);
                        progressBar.style.width = `${progress}%`;
                        progressBar.setAttribute('aria-valuenow', progress);
                    }
                    if (progressText) {
                        const progress = parseFloat(milestone.progress_percentage);
                        progressText.textContent = `${progress.toFixed(progressText.classList.contains('rm-milestone-header-pct') ? 0 : 1)}%`;
                    }

                    // Update status badge
                    const statusBadge = card.querySelector('[data-milestone-status]');
                    if (statusBadge) {
                        if (statusBadge.classList.contains('rm-status-pill')) {
                            const icon = statusBadge.querySelector('i');
                            statusBadge.textContent = '';
                            if (icon) {
                                statusBadge.appendChild(icon);
                                statusBadge.appendChild(document.createTextNode(' '));
                            }
                            statusBadge.appendChild(document.createTextNode(milestone.status));
                        } else {
                            statusBadge.textContent = milestone.status;
                            statusBadge.className = `badge ${this.getStatusBadgeClass(milestone.status)}`;
                        }
                    }

                    // Update Panel Remarks block - show when panel_remarks is non-empty,
                    // hide when null/empty. Works on both student and adviser milestone cards.
                    const panelRemarksWrapper = card.querySelector('[data-milestone-panel-remarks]');
                    if (panelRemarksWrapper) {
                        const remarks = (milestone.panel_remarks || '').trim();
                        if (remarks) {
                            const textEl = panelRemarksWrapper.querySelector('[data-milestone-panel-remarks-text]');
                            if (textEl) {
                                // Preserve line breaks from the server value.
                                textEl.innerHTML = remarks
                                    .replace(/&/g, '&amp;')
                                    .replace(/</g, '&lt;')
                                    .replace(/>/g, '&gt;')
                                    .replace(/\n/g, '<br>');
                            }
                            panelRemarksWrapper.style.display = '';
                        } else {
                            panelRemarksWrapper.style.display = 'none';
                        }
                    }

                    // Update the adviser Milestone Progress "Pending" cell so a new
                    // submission shows its Pending badge without a manual reload.
                    const pendingCell = card.querySelector('[data-pending-cell]');
                    if (pendingCell && typeof milestone.pending_count !== 'undefined') {
                        const pend = parseInt(milestone.pending_count || 0, 10);
                        if (pend > 0) {
                            const existingLink = pendingCell.querySelector('a');
                            let href = existingLink ? existingLink.getAttribute('href') : null;
                            if (!href) {
                                href = this.resolveBaseUrl() + 'modules/faculty/pages/submitted-updates.php?group=' +
                                       encodeURIComponent(this.currentGroupNumber()) +
                                       '&milestone_id=' + encodeURIComponent(milestone.id);
                            }
                            pendingCell.innerHTML =
                                '<a class="badge bg-warning text-dark text-decoration-none" ' +
                                'style="font-size:0.75rem;padding:0.3rem 0.6rem;" href="' + href + '">' +
                                (window.smsIconHtml ? window.smsIconHtml('clock', 'me-1') : '') + pend + '</a>';
                        } else {
                            pendingCell.innerHTML = '<span class="text-muted" style="font-size:0.85rem;">-</span>';
                        }
                    }
                }
            });

            this.updateLastRefreshTime();
        },

        /**
         * Current adviser group number from the page root marker (used to build
         * Submitted Updates links during live refreshes).
         */
        currentGroupNumber: function() {
            const el = document.querySelector('[data-group-number]');
            return el ? (el.getAttribute('data-group-number') || '') : '';
        },

        /**
         * Mirror of the PHP $statusColors map used by the monitoring pages so
         * live-rendered rows keep the exact same look.
         */
        statusPillStyle: function(status) {
            const styles = {
                'Not Started':          { color: '#94a3b8', bg: '#f1f5f9', icon: 'circle' },
                'In Progress':          { color: '#f59e0b', bg: '#fef3c7', icon: 'spinner' },
                'Submitted for Review': { color: '#3b82f6', bg: '#dbeafe', icon: 'clock' },
                'Revision Requested':   { color: '#ef4444', bg: '#fee2e2', icon: 'exclamation-triangle' },
                'Approved':             { color: '#10b981', bg: '#d1fae5', icon: 'check-circle' },
                'Completed':            { color: '#059669', bg: '#d1fae5', icon: 'check-double' }
            };
            return styles[status] || styles['Not Started'];
        },

        /** Format 'YYYY-MM-DD HH:MM:SS' as 'M d, g:i A' (same as the PHP templates). */
        formatRpDate: function(value) {
            try {
                const d = new Date(String(value).replace(' ', 'T'));
                if (isNaN(d.getTime())) { return String(value); }
                const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                let h = d.getHours();
                const ap = h >= 12 ? 'PM' : 'AM';
                h = h % 12 || 12;
                return months[d.getMonth()] + ' ' + String(d.getDate()).padStart(2, '0') + ', ' +
                       h + ':' + String(d.getMinutes()).padStart(2, '0') + ' ' + ap;
            } catch (e) {
                return String(value);
            }
        },

        /**
         * Re-render the Adviser Recent Activity feed from polled updates,
         * using the same markup as the server-rendered template.
         */
        renderRecentUpdates: function(updates) {
            const container = document.querySelector('[data-recent-updates-container]');
            if (!container) { return; }
            const gnum = encodeURIComponent(this.currentGroupNumber());
            const self = this;
            const items = (updates || []).slice(0, 5).map(function(u) {
                const st = String(u.milestone_status || 'In Progress');
                const sc = self.statusPillStyle(st);
                const pct = parseFloat(u.new_progress || 0).toFixed(0);
                const esc = self.escapeHtml;
                const milestoneRow = u.milestone_name
                    ? '<div style="font-size:0.75rem;color:var(--sms-text-muted);font-weight:600;margin-bottom:0.3rem;">' +
                      (window.smsIconHtml ? window.smsIconHtml('bookmark', 'me-1') : '') + esc(u.milestone_name) + '</div>'
                    : '';
                return '<div style="padding-bottom:0.85rem;border-bottom:1px solid var(--sms-border-soft);">' +
                    '<div class="d-flex align-items-start justify-content-between gap-2 mb-1">' +
                    '<div style="font-weight:700;font-size:0.88rem;color:var(--sms-heading);flex:1;min-width:0;' +
                    '-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;display:-webkit-box;">' + esc(u.update_title) + '</div>' +
                    '<div style="font-size:1.1rem;font-weight:800;color:' + sc.color + ';flex-shrink:0;">' + pct + '%</div>' +
                    '</div>' + milestoneRow +
                    '<div class="d-flex align-items-center justify-content-between gap-2">' +
                    '<div style="font-size:0.7rem;color:var(--sms-text-muted);">' + (window.smsIconHtml ? window.smsIconHtml('clock', 'me-1') : '') +
                    esc(self.formatRpDate(u.submitted_at)) + '</div>' +
                    '<span class="rm-status-pill" style="background:' + sc.bg + ';color:' + sc.color +
                    ';font-size:0.65rem;padding:0.18rem 0.55rem;">' + esc(st) + '</span>' +
                    '</div>' +
                    '<div class="mt-2"><a href="' + self.resolveBaseUrl() + 'modules/faculty/pages/submitted-updates.php?group=' +
                    gnum + '&update_id=' + encodeURIComponent(u.id) +
                    '" style="font-size:0.78rem;font-weight:700;color:var(--sms-primary);text-decoration:none;">' +
                    (window.smsIconHtml ? window.smsIconHtml('eye', 'me-1') : '') + 'Review →</a></div>' +
                    '</div>';
            });
            if (!items.length) { return; }
            container.innerHTML = items.join('');
        },

        /**
         * Update feedback UI
         */
        updateFeedbackUI: function(feedback) {
            const feedbackContainer = document.querySelector('[data-feedback-container]');
            if (!feedbackContainer) return;

            // Trigger update event for page-specific handling
            document.dispatchEvent(new CustomEvent('research:feedback-updated', { 
                detail: { feedback: feedback } 
            }));

            this.updateLastRefreshTime();
        },

        /**
         * Update adviser groups UI
         */
        updateAdviserGroupsUI: function(groups) {
            const container = document.querySelector('[data-groups-container]');
            const currentCards = document.querySelectorAll('[data-group-number]');

            if (container && groups.length !== currentCards.length) {
                window.location.reload();
                return;
            }

            groups.forEach(group => {
                const groupNumber = String(group.group_number || '');
                const card = Array.from(document.querySelectorAll('[data-group-number]'))
                    .find(el => el.getAttribute('data-group-number') === groupNumber);
                if (!card) {
                    window.location.reload();
                    return;
                }

                const progress = parseFloat(group.overall_progress || 0);
                const progressColor = progress >= 80 ? '#10b981' : (progress >= 40 ? '#f59e0b' : '#3b82f6');
                const progressText = card.querySelector('[data-group-progress-text]');
                const progressBar = card.querySelector('[data-group-progress-bar]');

                if (progressText) {
                    progressText.textContent = `${progress.toFixed(1)}%`;
                    progressText.style.color = progressColor;
                }
                if (progressBar) {
                    progressBar.style.width = `${progress}%`;
                    progressBar.style.background = progressColor;
                }

                const milestonesContainer = card.querySelector('[data-group-milestones]');
                if (milestonesContainer && Array.isArray(group.milestones)) {
                    milestonesContainer.innerHTML = group.milestones.map(milestone => `
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-2"
                             style="font-size:0.78rem;color:var(--sms-text);"
                             data-milestone-id="${this.escapeHtml(milestone.id || '')}">
                            <span style="font-weight:700;color:var(--sms-heading);">
                                ${this.escapeHtml(milestone.milestone_name || '')}
                            </span>
                            <span class="text-nowrap" style="color:var(--sms-text-muted);" data-milestone-status>
                                ${this.escapeHtml(milestone.status || 'Not Started')}
                            </span>
                        </div>
                    `).join('');
                }
            });

            const groupCount = document.querySelector('[data-live-group-count]');
            if (groupCount) {
                groupCount.textContent = String(groups.length);
            }

            const pending = groups.reduce((sum, group) => sum + parseInt(group.pending_reviews || 0, 10), 0);
            const pendingCount = document.querySelector('[data-live-pending-count]');
            if (pendingCount) {
                pendingCount.textContent = String(pending);
            }

            const avgProgress = groups.length
                ? groups.reduce((sum, group) => sum + parseFloat(group.overall_progress || 0), 0) / groups.length
                : 0;
            const avgProgressEl = document.querySelector('[data-live-avg-progress]');
            if (avgProgressEl) {
                avgProgressEl.textContent = `${avgProgress.toFixed(1)}%`;
            }

            document.dispatchEvent(new CustomEvent('research:groups-updated', { 
                detail: { groups: groups } 
            }));

            this.updateLastRefreshTime();
        },

        /**
         * Escape HTML before rendering live data into existing cards
         */
        escapeHtml: function(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        },

        /**
         * Update adviser updates UI
         */
        updateAdviserUpdatesUI: function(updates) {
            document.dispatchEvent(new CustomEvent('research:updates-refreshed', { 
                detail: { updates: updates } 
            }));

            this.updateLastRefreshTime();
        },

        /**
         * Update adviser group progress UI
         */
        updateAdviserGroupProgressUI: function(data) {
            if (data.plan) {
                const progress = parseFloat(data.plan.overall_progress || 0);
                const progressBar = document.querySelector('[data-overall-progress-bar]');
                const progressTexts = document.querySelectorAll('[data-overall-progress-text]');
                if (progressBar) {
                    progressBar.style.width = `${progress}%`;
                    progressBar.setAttribute('aria-valuenow', progress);
                }
                progressTexts.forEach(progressText => {
                    progressText.textContent = `${progress.toFixed(1)}%`;
                });
            }

            if (Array.isArray(data.milestones)) {
                this.updateMilestonesUI(data.milestones);

                // Keep the hero stat cards and the Review Updates quick-action
                // badge in sync with the live milestone data.
                let pendingTotal = 0;
                let doneTotal = 0;
                data.milestones.forEach(function (m) {
                    pendingTotal += parseInt(m.pending_count || 0, 10);
                    if (['Approved', 'Completed'].indexOf(String(m.status || '')) !== -1) {
                        doneTotal++;
                    }
                });
                const heroPending = document.querySelector('[data-hero-pending-total]');
                if (heroPending) { heroPending.textContent = String(pendingTotal); }
                const heroDone = document.querySelector('[data-hero-done-total]');
                if (heroDone) { heroDone.textContent = String(doneTotal); }
                const qaBadge = document.querySelector('[data-review-updates-badge]');
                if (qaBadge) {
                    qaBadge.textContent = String(pendingTotal);
                    qaBadge.classList.toggle('d-none', pendingTotal === 0);
                }
            }

            // Refresh the Recent Activity feed so the latest submission appears
            // without a manual reload.
            if (Array.isArray(data.updates)) {
                this.renderRecentUpdates(data.updates);
            }

            document.dispatchEvent(new CustomEvent('research:group-progress-updated', { 
                detail: data 
            }));

            this.updateLastRefreshTime();
        },

        /**
         * Get status badge CSS class
         */
        getStatusBadgeClass: function(status) {
            const classes = {
                'Not Started': 'bg-secondary',
                'In Progress': 'bg-warning',
                'Submitted for Review': 'bg-info',
                'Revision Requested': 'bg-danger',
                'Approved': 'bg-success',
                'Completed': 'bg-success'
            };
            return classes[status] || 'bg-secondary';
        },

        /**
         * Update last refresh time indicator
         * Supports both legacy [data-last-refresh] and new #rmRefreshBar
         */
        updateLastRefreshTime: function() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString();

            // â”€â”€ New rm-refresh-bar (all redesigned pages) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            const bar      = document.getElementById('rmRefreshBar');
            const icon     = document.getElementById('rmRefreshIcon');
            const textEl   = document.getElementById('rmRefreshText');

            if (bar && textEl) {
                // Spin the icon briefly
                if (icon) {
                    icon.classList.add('rm-spinning');
                    setTimeout(() => icon.classList.remove('rm-spinning'), 700);
                }
                textEl.textContent = `Last updated: ${timeStr}`;
                bar.classList.add('rm-just-updated');
                setTimeout(() => bar.classList.remove('rm-just-updated'), 1500);
            }

            // â”€â”€ Legacy fallback â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            const legacy = document.querySelector('[data-last-refresh]');
            if (legacy) {
                legacy.innerHTML = (window.smsIconHtml ? window.smsIconHtml('sync-alt', 'me-1') : '') + 'Last updated: ' + timeStr;
                legacy.classList.add('text-success');
                setTimeout(() => legacy.classList.remove('text-success'), 1200);
            }
        },

        /**
         * Show notification (browser notification API)
         */
        showNotification: function(message, type = 'info') {
            // Check if browser supports notifications
            if (!('Notification' in window)) return;

            // Request permission if needed
            if (Notification.permission === 'granted') {
                new Notification('Research Progress', {
                    body: message,
                    icon: '/images/bcp-logo-source.png',
                    tag: 'research-progress',
                    renotify: false // Prevent duplicate notifications
                });
            } else if (Notification.permission !== 'denied') {
                Notification.requestPermission().then(permission => {
                    if (permission === 'granted') {
                        new Notification('Research Progress', {
                            body: message,
                            icon: '/images/bcp-logo-source.png'
                        });
                    }
                });
            }
        },

        /**
         * Handle polling errors with retry logic
         */
        handlePollError: function() {
            this.state.retryCount++;

            if (this.state.retryCount >= this.config.maxRetries) {
                console.warn('[ResearchProgressLive] Max retries reached, stopping polling');
                this.stopPolling();
                
                // Show error message to user
                const errorContainer = document.querySelector('[data-live-update-status]');
                if (errorContainer) {
                    errorContainer.innerHTML = `
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            ${window.smsIconHtml ? window.smsIconHtml('exclamation-triangle', 'me-2') : ''}
                            Live updates temporarily unavailable. <a href="#" onclick="location.reload()">Refresh page</a> to retry.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `;
                }
            } else {
                debugLog(`[ResearchProgressLive] Retry ${this.state.retryCount}/${this.config.maxRetries} in ${this.config.retryDelay}ms`);
            }
        },

        /**
         * Cleanup on page unload
         */
        cleanup: function() {
            this.stopPolling();
            this.state.cache = {
                milestones: null,
                updates: null,
                feedback: null,
                groups: null
            };
        }
    };

    // Expose to global scope
    window.ResearchProgressLive = ResearchProgressLive;

    // Auto-initialize based on page and role
    document.addEventListener('DOMContentLoaded', function() {
        const body = document.body;
        
        if (body.classList.contains('student-portal')) {
            ResearchProgressLive.initStudent();
        } else if (body.classList.contains('faculty-portal')) {
            ResearchProgressLive.initAdviser();
        }
    });

    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        ResearchProgressLive.cleanup();
    });

    // Pause polling when page is hidden (battery saving)
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'hidden') {
            debugLog('[ResearchProgressLive] Page hidden, polling paused');
        } else {
            debugLog('[ResearchProgressLive] Page visible, polling resumed');
        }
    });

})();

