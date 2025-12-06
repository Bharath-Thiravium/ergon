<?php
$active_page = 'contact_followups';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><span>👥</span> Contact Follow-ups</h1>
        <p>Contact-centric follow-up management and tracking</p>
    </div>
    <div class="page-actions">
        <button class="btn btn--secondary" onclick="showAddContactModal()">
            <span>👤</span> Add Contact
        </button>
        <a href="/ergon/contacts/followups/view" class="btn btn--secondary">
            <span>👁️</span> View Follow-ups
        </a>
        <button onclick="openFollowupModal(0)" class="btn btn--primary">
            <span>➕</span> New Follow-up
        </button>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-warning"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php
$totalContacts = count($contacts);
$totalFollowups = array_sum(array_column($contacts, 'total_followups'));
$overdueCount = array_sum(array_column($contacts, 'overdue_count'));
$todayCount = array_sum(array_column($contacts, 'today_count'));
?>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">👥</div>
            <div class="kpi-card__trend">Active</div>
        </div>
        <div class="kpi-card__value"><?= $totalContacts ?></div>
        <div class="kpi-card__label">Total Contacts</div>
        <div class="kpi-card__status">With Follow-ups</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📞</div>
            <div class="kpi-card__trend">↗ +<?= $totalFollowups ?></div>
        </div>
        <div class="kpi-card__value"><?= $totalFollowups ?></div>
        <div class="kpi-card__label">Total Follow-ups</div>
        <div class="kpi-card__status">Active</div>
    </div>
    
    <div class="kpi-card kpi-card--warning">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">⚠️</div>
            <div class="kpi-card__trend kpi-card__trend--down">— <?= $overdueCount ?></div>
        </div>
        <div class="kpi-card__value"><?= $overdueCount ?></div>
        <div class="kpi-card__label">Overdue</div>
        <div class="kpi-card__status kpi-card__status--pending">Urgent</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📅</div>
            <div class="kpi-card__trend">Today</div>
        </div>
        <div class="kpi-card__value"><?= $todayCount ?></div>
        <div class="kpi-card__label">Due Today</div>
        <div class="kpi-card__status">Scheduled</div>
    </div>
</div>

<!-- Contacts with Follow-ups -->
<div class="card">
    <div class="card__header">
        <h2 class="card__title">Contacts with Follow-ups</h2>
        <div class="card__actions">
            <div class="view-toggle">
                <button class="view-btn view-btn--active" data-view="grid" title="Grid View">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M3 3h7v7H3V3zm0 11h7v7H3v-7zm11-11h7v7h-7V3zm0 11h7v7h-7v-7z"/>
                    </svg>
                </button>
                <button class="view-btn" data-view="list" title="List View">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/>
                    </svg>
                </button>
            </div>
            <span class="badge badge--info"><?= count($contacts) ?> contacts</span>
        </div>
    </div>
    <div class="card__body">
        <?php if (!empty($contacts)): ?>
            <!-- Grid View -->
            <div class="contacts-grid" id="gridView">
                <?php foreach ($contacts as $contact): ?>
                    <div class="contact-card">
                        <div class="contact-card__header">
                            <h4 data-initials="<?= strtoupper(substr($contact['name'], 0, 1) . (strpos($contact['name'], ' ') ? substr($contact['name'], strpos($contact['name'], ' ') + 1, 1) : substr($contact['name'], 1, 1))) ?>"><?= htmlspecialchars($contact['name']) ?></h4>
                            <div class="contact-badges">
                                <?php if ($contact['overdue_count'] > 0): ?>
                                    <span class="badge badge--danger"><?= $contact['overdue_count'] ?> overdue</span>
                                <?php endif; ?>
                                <?php if ($contact['today_count'] > 0): ?>
                                    <span class="badge badge--warning"><?= $contact['today_count'] ?> today</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="contact-card__body">
                            <div class="contact-info">
                                <?php if ($contact['phone']): ?>
                                    <div class="contact-detail">
                                        <span class="icon">📞</span>
                                        <a href="tel:<?= $contact['phone'] ?>"><?= htmlspecialchars($contact['phone']) ?></a>
                                    </div>
                                <?php endif; ?>
                                <?php if ($contact['email']): ?>
                                    <div class="contact-detail">
                                        <span class="icon">✉️</span>
                                        <a href="mailto:<?= $contact['email'] ?>"><?= htmlspecialchars($contact['email']) ?></a>
                                    </div>
                                <?php endif; ?>
                                <?php if ($contact['company']): ?>
                                    <div class="contact-detail">
                                        <span class="icon">🏢</span>
                                        <?= htmlspecialchars($contact['company']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="followup-summary">
                                <div class="summary-item">
                                    <span class="count"><?= $contact['total_followups'] ?></span>
                                    <span class="label">Total Follow-ups</span>
                                </div>
                                <?php if ($contact['next_followup_date'] && strtotime($contact['next_followup_date']) > time()): ?>
                                    <div class="summary-item">
                                        <span class="date"><?= date('M d', strtotime($contact['next_followup_date'])) ?></span>
                                        <span class="label">Next Follow-up</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="contact-card__actions">
                            <div class="ab-container">
                                <button class="ab-btn ab-btn--view" onclick="viewContact('C_<?= $contact['id'] ?>')" title="View Contact Details">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                                <button class="ab-btn ab-btn--edit" onclick="editContact('C_<?= $contact['id'] ?>')" title="Edit Contact">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                            </div>
                            <a href="/ergon/contacts/followups/view/C_<?= $contact['id'] ?>" class="btn btn--primary btn--sm">
                                View Follow-ups
                            </a>
                            <?php if ($contact['phone']): ?>
                                <a href="tel:<?= $contact['phone'] ?>" class="btn btn--secondary btn--sm">
                                    📞 Call
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- List View -->
            <div class="contacts-list" id="listView" style="display: none;">
                <?php foreach ($contacts as $contact): ?>
                    <div class="contact-list-item">
                        <div class="contact-avatar" data-initials="<?= strtoupper(substr($contact['name'], 0, 1) . (strpos($contact['name'], ' ') ? substr($contact['name'], strpos($contact['name'], ' ') + 1, 1) : substr($contact['name'], 1, 1))) ?>"></div>
                        <div class="contact-main">
                            <div class="contact-name"><?= htmlspecialchars($contact['name']) ?></div>
                            <div class="contact-meta">
                                <?php if ($contact['phone']): ?>
                                    <span class="meta-item">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
                                        <a href="tel:<?= $contact['phone'] ?>"><?= htmlspecialchars($contact['phone']) ?></a>
                                    </span>
                                <?php endif; ?>
                                <?php if ($contact['email']): ?>
                                    <span class="meta-item">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.89 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                                        <a href="mailto:<?= $contact['email'] ?>"><?= htmlspecialchars($contact['email']) ?></a>
                                    </span>
                                <?php endif; ?>
                                <?php if ($contact['company']): ?>
                                    <span class="meta-item">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg>
                                        <?= htmlspecialchars($contact['company']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="contact-stats">
                            <div class="stat-item">
                                <span class="stat-value"><?= $contact['total_followups'] ?></span>
                                <span class="stat-label">Follow-ups</span>
                            </div>
                            <?php if ($contact['next_followup_date'] && strtotime($contact['next_followup_date']) > time()): ?>
                                <div class="stat-item">
                                    <span class="stat-value"><?= date('M d', strtotime($contact['next_followup_date'])) ?></span>
                                    <span class="stat-label">Next</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="contact-badges">
                            <?php if ($contact['overdue_count'] > 0): ?>
                                <span class="badge badge--danger"><?= $contact['overdue_count'] ?> overdue</span>
                            <?php endif; ?>
                            <?php if ($contact['today_count'] > 0): ?>
                                <span class="badge badge--warning"><?= $contact['today_count'] ?> today</span>
                            <?php endif; ?>
                        </div>
                        <div class="contact-actions">
                            <div class="ab-container">
                                <button class="ab-btn ab-btn--view" onclick="viewContact('C_<?= $contact['id'] ?>')" title="View Contact Details">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                                <button class="ab-btn ab-btn--edit" onclick="editContact('C_<?= $contact['id'] ?>')" title="Edit Contact">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                            </div>
                            <a href="/ergon/contacts/followups/view/C_<?= $contact['id'] ?>" class="btn btn--primary btn--sm">
                                Follow-ups
                            </a>
                            <?php if ($contact['phone']): ?>
                                <a href="tel:<?= $contact['phone'] ?>" class="btn btn--secondary btn--sm">
                                    📞
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">👥</div>
                <h3>No Contacts with Follow-ups</h3>
                <p>Create follow-ups for your contacts to see them here</p>
                <button onclick="openFollowupModal(0)" class="btn btn--primary">Create Follow-up</button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- View Contact Modal -->
<div id="viewContactModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>👁️ View Contact Details</h3>
            <button class="modal-close" onclick="closeModal('viewContactModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="contact-details">
                <div class="detail-row">
                    <label>Name:</label>
                    <span id="viewContactName">-</span>
                </div>
                <div class="detail-row">
                    <label>Phone:</label>
                    <span id="viewContactPhone">-</span>
                </div>
                <div class="detail-row">
                    <label>Email:</label>
                    <span id="viewContactEmail">-</span>
                </div>
                <div class="detail-row">
                    <label>Company:</label>
                    <span id="viewContactCompany">-</span>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn--secondary" onclick="closeModal('viewContactModal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Close
            </button>
            <button type="button" class="btn btn--warning" id="editFromViewBtn">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Edit
            </button>
            <button type="button" class="btn btn--primary" id="viewFollowupsBtn">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                View Follow-ups
            </button>
        </div>
    </div>
</div>

<!-- Edit Contact Modal -->
<div id="editContactModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>✏️ Edit Contact</h3>
            <button class="modal-close" onclick="closeModal('editContactModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editContactForm">
                <input type="hidden" id="editContactId" name="contact_id">
                <div class="form-group">
                    <label class="form-label">Name *</label>
                    <input type="text" id="editContactName" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="tel" id="editContactPhone" name="phone" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" id="editContactEmail" name="email" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Company</label>
                    <input type="text" id="editContactCompany" name="company" class="form-control">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn--secondary" onclick="closeModal('editContactModal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Cancel
            </button>
            <button type="button" class="btn btn--success" onclick="saveContactChanges()">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                </svg>
                Save Changes
            </button>
        </div>
    </div>
</div>

<style>
.view-toggle {
    display: flex;
    gap: 2px;
    background: var(--bg-secondary);
    border-radius: 6px;
    padding: 2px;
    margin-right: var(--space-3);
}

.view-btn {
    background: transparent;
    border: none;
    padding: 8px 12px;
    border-radius: 4px;
    cursor: pointer;
    color: var(--text-secondary);
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
}

.view-btn:hover {
    background: var(--bg-hover);
    color: var(--text-primary);
}

.view-btn--active {
    background: var(--primary);
    color: white;
}

.view-btn--active:hover {
    background: var(--primary);
    color: white;
}

.contacts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--space-3);
    margin-bottom: var(--space-6);
}

.contacts-list {
    display: flex;
    flex-direction: column;
    gap: 1px;
    background: var(--border-color);
    border-radius: var(--border-radius);
    overflow: hidden;
}

.contact-list-item {
    display: flex;
    align-items: center;
    gap: var(--space-4);
    padding: var(--space-4);
    background: var(--bg-primary);
    transition: var(--transition);
    min-height: 80px;
}

.contact-list-item:hover {
    background: var(--bg-secondary);
}

.contact-avatar {
    width: 48px;
    height: 48px;
    background: var(--primary);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: var(--font-size-sm);
    flex-shrink: 0;
}

.contact-avatar::before {
    content: attr(data-initials);
}

.contact-main {
    flex: 1;
    min-width: 0;
}

.contact-name {
    font-weight: 600;
    color: var(--text-primary);
    font-size: var(--font-size-lg);
    margin-bottom: var(--space-1);
}

.contact-meta {
    display: flex;
    gap: var(--space-4);
    flex-wrap: wrap;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: var(--space-1);
    font-size: var(--font-size-sm);
    color: var(--text-secondary);
}

.meta-item svg {
    opacity: 0.7;
}

.meta-item a {
    color: var(--primary);
    text-decoration: none;
}

.meta-item a:hover {
    text-decoration: underline;
}

.contact-stats {
    display: flex;
    gap: var(--space-4);
    margin-right: var(--space-4);
}

.stat-item {
    text-align: center;
    min-width: 60px;
}

.stat-value {
    display: block;
    font-weight: 700;
    color: var(--primary);
    font-size: var(--font-size-xl);
    line-height: 1;
}

.stat-label {
    display: block;
    font-size: var(--font-size-xs);
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 2px;
}

.contact-actions {
    display: flex;
    gap: var(--space-2);
    flex-shrink: 0;
}

.contact-card {
    background: rgba(255, 255, 255, 0.95);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: var(--border-radius);
    padding: var(--space-3);
    transition: var(--transition);
    box-shadow: var(--shadow-sm);
}

.contact-card:hover {
    transform: translateY(-1px);
    box-shadow: var(--shadow);
}

.contact-card__header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: var(--space-3);
}

.contact-card__header h4 {
    margin: 0;
    color: var(--text-primary);
    font-size: var(--font-size-lg);
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

.contact-card__header h4::before {
    content: attr(data-initials);
    width: 32px;
    height: 32px;
    background: var(--primary);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: var(--font-size-xs);
    font-weight: 600;
    flex-shrink: 0;
}

.contact-badges {
    display: flex;
    gap: var(--space-1);
    flex-wrap: wrap;
}

.contact-info {
    margin-bottom: var(--space-3);
    padding: var(--space-2);
    background: var(--bg-secondary);
    border-radius: 4px;
    border: 1px solid var(--border-color);
}

.contact-detail {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    margin-bottom: var(--space-1);
    font-size: var(--font-size-sm);
    color: var(--text-secondary);
    padding: var(--space-1) 0;
}

.contact-detail:last-child {
    margin-bottom: 0;
}

.contact-detail .icon {
    width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--bg-tertiary);
    border-radius: 4px;
    font-size: 12px;
    flex-shrink: 0;
}

.contact-detail a {
    color: var(--primary);
    text-decoration: none;
    font-weight: 500;
}

.contact-detail a:hover {
    text-decoration: underline;
}

.followup-summary {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-2);
    margin-bottom: var(--space-3);
    padding: var(--space-2);
    background: var(--bg-secondary);
    border-radius: 4px;
    border: 1px solid var(--border-color);
}

.summary-item {
    text-align: center;
}

.summary-item .count,
.summary-item .date {
    display: block;
    font-weight: 700;
    color: var(--primary);
    font-size: var(--font-size-xl);
    margin-bottom: var(--space-1);
}

.summary-item .label {
    display: block;
    font-size: var(--font-size-xs);
    color: var(--text-muted);
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.contact-card__actions {
    display: flex;
    gap: var(--space-2);
    flex-wrap: wrap;
    padding-top: var(--space-2);
    border-top: 1px solid var(--border-color);
}

.contact-card__actions .btn {
    flex: 1;
    min-width: 100px;
}

.btn--sm {
    padding: var(--space-2) var(--space-3);
    font-size: var(--font-size-sm);
}

@media (max-width: 768px) {
    .contacts-grid {
        grid-template-columns: 1fr;
    }
    
    .contacts-list {
        display: flex !important;
    }
    
    #gridView[style*="display: none"] {
        display: none !important;
    }
    
    #listView[style*="display: none"] {
        display: none !important;
    }
    
    .contact-card__header {
        flex-direction: column;
        gap: 0.75rem;
        align-items: flex-start;
    }
    
    .followup-summary {
        grid-template-columns: 1fr;
    }
    
    .contact-card__actions {
        flex-direction: column;
    }
    
    .contact-list-item {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--space-3);
        padding: var(--space-3);
    }
    
    .contact-main {
        width: 100%;
    }
    
    .contact-meta {
        flex-direction: column;
        gap: var(--space-2);
    }
    
    .contact-stats {
        width: 100%;
        justify-content: space-around;
        margin-right: 0;
    }
    
    .contact-actions {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
function closeFollowupModal() {
    const modal = document.getElementById('followupModal');
    if (modal && modal.parentElement) {
        modal.parentElement.remove();
    }
}

function openFollowupModal(contactId) {
    fetch(`/ergon/contacts/followups/create?contact_id=C_${contactId}&ajax=1`)
    .then(response => response.text())
    .then(html => {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = html;
        document.body.appendChild(wrapper);
    })
    .catch(error => console.error('Error loading modal:', error));
}

document.addEventListener('DOMContentLoaded', function() {
    const viewButtons = document.querySelectorAll('.view-btn');
    const gridView = document.getElementById('gridView');
    const listView = document.getElementById('listView');
    
    // Load saved view preference
    const savedView = localStorage.getItem('contactsView') || 'grid';
    switchView(savedView);
    
    viewButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const view = this.dataset.view;
            switchView(view);
            localStorage.setItem('contactsView', view);
        });
    });
    
    function switchView(view) {
        viewButtons.forEach(btn => {
            btn.classList.toggle('view-btn--active', btn.dataset.view === view);
        });
        
        if (view === 'list') {
            gridView.style.display = 'none';
            listView.style.display = 'flex';
        } else {
            gridView.style.display = 'grid';
            listView.style.display = 'none';
        }
    }
});

// Followup action functions
function completeFollowup(id) {
    if (confirm('Mark this follow-up as completed?')) {
        fetch(`/ergon/contacts/followups/complete/${id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed to complete follow-up'));
            }
        })
        .catch(error => {
            console.error('Complete error:', error);
            alert('An error occurred while completing the follow-up.');
        });
    }
}

function cancelFollowup(id) {
    const reason = prompt('Please provide a reason for cancelling this follow-up:');
    if (reason && reason.trim()) {
        const formData = new FormData();
        formData.append('reason', reason.trim());
        
        fetch(`/ergon/contacts/followups/cancel/${id}`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed to cancel follow-up'));
            }
        })
        .catch(error => {
            console.error('Cancel error:', error);
            alert('An error occurred while cancelling the follow-up.');
        });
    }
}

function rescheduleFollowup(id) {
    const newDate = prompt('Enter new date (YYYY-MM-DD):');
    const reason = prompt('Reason for rescheduling:');
    
    if (newDate && newDate.trim()) {
        const formData = new FormData();
        formData.append('new_date', newDate.trim());
        formData.append('reason', reason || 'No reason provided');
        
        fetch(`/ergon/contacts/followups/reschedule/${id}`, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.ok) {
                location.reload();
            } else {
                alert('Failed to reschedule follow-up');
            }
        })
        .catch(error => {
            console.error('Reschedule error:', error);
            alert('An error occurred while rescheduling the follow-up.');
        });
    }
}

function showFollowupDetails(id) {
    // Redirect to contact followup view
    window.location.href = `/ergon/contacts/followups/view/${id}`;
}

function showHistory(id) {
    alert('History feature coming soon');
}

function viewContact(contactId) {
    // Strip C_ prefix if present
    contactId = contactId.replace('C_', '');
    fetch(`/ergon/api/contacts/${contactId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.contact) {
                const contact = data.contact;
                document.getElementById('viewContactName').textContent = contact.name || 'N/A';
                document.getElementById('viewContactPhone').textContent = contact.phone || 'N/A';
                document.getElementById('viewContactEmail').textContent = contact.email || 'N/A';
                document.getElementById('viewContactCompany').textContent = contact.company || 'N/A';
                document.getElementById('viewFollowupsBtn').onclick = () => {
                    window.location.href = `/ergon/contacts/followups/view/C_${contactId}`;
                };
                document.getElementById('editFromViewBtn').onclick = () => {
                    closeModal('viewContactModal');
                    editContact(contactId);
                };
                showModal('viewContactModal');
            } else {
                alert('Error loading contact details');
            }
        })
        .catch(error => {
            console.error('Error loading contact:', error);
            alert('Error loading contact details');
        });
}

function editContact(contactId) {
    // Strip C_ prefix if present
    contactId = contactId.replace('C_', '');
    fetch(`/ergon/api/contacts/${contactId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.contact) {
                const contact = data.contact;
                document.getElementById('editContactId').value = contact.id;
                document.getElementById('editContactName').value = contact.name || '';
                document.getElementById('editContactPhone').value = contact.phone || '';
                document.getElementById('editContactEmail').value = contact.email || '';
                document.getElementById('editContactCompany').value = contact.company || '';
                showModal('editContactModal');
            } else {
                alert('Error loading contact details');
            }
        })
        .catch(error => {
            console.error('Error loading contact:', error);
            alert('Error loading contact details');
        });
}

function saveContactChanges() {
    const form = document.getElementById('editContactForm');
    const formData = new FormData(form);
    const contactId = document.getElementById('editContactId').value;
    
    if (!formData.get('name').trim()) {
        alert('Name is required!');
        return;
    }
    
    fetch(`/ergon/api/contacts/${contactId}/update`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Contact details saved successfully!');
            closeModal('editContactModal');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to update contact'));
        }
    })
    .catch(error => {
        console.error('Error updating contact:', error);
        alert('Error updating contact');
    });
}

function showModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}



function showAddContactModal() {
    document.getElementById('addContactModal').style.display = 'block';
}

function closeAddContactModal() {
    document.getElementById('addContactModal').style.display = 'none';
    document.getElementById('addContactForm').reset();
}

function saveNewContact() {
    const form = document.getElementById('addContactForm');
    const formData = new FormData(form);
    
    const contactData = {
        name: formData.get('name'),
        phone: formData.get('phone'),
        email: formData.get('email'),
        company: formData.get('company')
    };
    
    if (!contactData.name.trim()) {
        alert('Name is required');
        return;
    }
    
    fetch('/ergon/api/contacts/create', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(contactData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeAddContactModal();
            location.reload();
        } else {
            alert('Error: ' + (data.error || 'Failed to create contact'));
        }
    })
    .catch(error => {
        console.error('Error creating contact:', error);
        alert('Error creating contact');
    });
}
</script>

<!-- Add Contact Modal -->
<div id="addContactModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>👤 Add New Contact</h3>
            <button class="modal-close" onclick="closeAddContactModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="addContactForm">
                <div class="form-group">
                    <label class="form-label">Name *</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="tel" name="phone" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Company</label>
                    <input type="text" name="company" class="form-control">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn--secondary" onclick="closeAddContactModal()">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Cancel
            </button>
            <button type="button" class="btn btn--success" onclick="saveNewContact()">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                </svg>
                Save Contact
            </button>
        </div>
    </div>
</div>



<style>
.modal {
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding-top: 80px;
}

.modal-content {
    background-color: var(--bg-primary);
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    max-height: calc(100vh - 120px);
    overflow-y: auto;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.25);
    border: 1px solid var(--border-color);
    position: relative;
    margin: 0;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.modal-header h3 {
    margin: 0;
    color: var(--text-primary);
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--text-secondary);
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 1.5rem;
    border-top: 1px solid var(--border-color);
}

.contact-details {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: var(--bg-secondary);
    border-radius: 6px;
    border: 1px solid var(--border-color);
}

.detail-row label {
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}

.detail-row span {
    color: var(--text-secondary);
    font-weight: 500;
}

.form-group {
    margin-bottom: 1rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--text-primary);
    font-size: 0.875rem;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    font-size: 0.875rem;
    background: var(--bg-primary);
    color: var(--text-primary);
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.ab-btn {
    position: relative;
}

.ab-btn::after {
    content: attr(title);
    position: absolute;
    bottom: -35px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0,0,0,0.8);
    color: white;
    padding: 0.5rem 0.75rem;
    border-radius: 4px;
    font-size: 0.75rem;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: all 0.2s ease;
    z-index: 1000;
    pointer-events: none;
}

.ab-btn:hover::after {
    opacity: 1;
    visibility: visible;
}



.modal-content {
    background-color: var(--bg-primary);
    margin: 5% auto;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.modal-header h3 {
    margin: 0;
    color: var(--text-primary);
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--text-secondary);
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 1.5rem;
    border-top: 1px solid var(--border-color);
}

.form-group {
    margin-bottom: 1rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--text-primary);
    font-size: 0.875rem;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    font-size: 0.875rem;
    background: var(--bg-primary);
    color: var(--text-primary);
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.contact-details {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: var(--bg-secondary);
    border-radius: 6px;
    border: 1px solid var(--border-color);
}

.detail-row label {
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}

.detail-row span {
    color: var(--text-secondary);
    font-weight: 500;
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';
?>