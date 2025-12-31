<style>
/* Owner Dashboard Styles - Exact Match */
.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: var(--space-3);
  margin-bottom: var(--space-6);
  margin-top: var(--space-6);
}

.kpi-card {
  background: rgba(255, 255, 255, 0.95);
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: var(--border-radius);
  padding: var(--space-2) var(--space-3);
  transition: var(--transition);
  box-shadow: var(--shadow-sm);
}

.kpi-card:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-sm);
}

.kpi-card__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--space-3);
}

.kpi-card__icon {
  font-size: 1.5rem;
  opacity: 0.8;
}

.kpi-card__trend {
  font-size: 0.75rem;
  font-weight: 600;
  color: #10b981;
  background: #ecfdf5;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
}

.kpi-card__trend--down {
  color: #ef4444;
  background: #fef2f2;
}

.kpi-card__value {
  font-size: 2rem;
  font-weight: 800;
  color: var(--text-primary);
  margin-bottom: var(--space-1);
  text-align: center;
}

.kpi-card__label {
  color: var(--text-secondary);
  font-size: var(--font-size-xs);
  text-align: center;
  margin-bottom: var(--space-2);
}

.kpi-card__status {
  text-align: center;
  font-size: var(--font-size-xs);
  font-weight: 600;
  padding: var(--space-1) var(--space-3);
  border-radius: 16px;
}

.kpi-card__status--pending {
  background: rgba(217, 119, 6, 0.1);
  color: var(--warning);
}

.kpi-card--warning {
  border-left: 4px solid #f59e0b;
}

.card {
  background: rgba(255, 255, 255, 0.95);
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: var(--border-radius);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
  transition: var(--transition);
}

.card:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-sm);
}

.card__header {
  padding: var(--space-3) var(--space-4);
  border-bottom: 1px solid var(--border-color);
  background: var(--bg-secondary);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card__title {
  font-size: var(--font-size-base);
  font-weight: 500;
  color: var(--text-primary);
  margin: 0;
}

.card__body {
  padding: var(--space-3) var(--space-4);
}

.card__body--scrollable {
  max-height: 300px;
  overflow-y: auto;
}

.card-actions {
  display: flex;
  gap: 0.5rem;
}

/* Owner Dashboard Specific Styles from dashboard-owner.css */
.overview-summary {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.summary-stat {
  flex: 1;
  text-align: center;
  padding: 0.5rem;
  background: rgba(59, 130, 246, 0.05);
  border: 1px solid rgba(59, 130, 246, 0.1);
  border-radius: 8px;
}

.summary-number {
  display: block;
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--primary);
  margin-bottom: 0.125rem;
}

.summary-label {
  font-size: 0.7rem;
  color: var(--text-secondary);
  font-weight: 500;
}

.overview-stats {
  margin-bottom: 1rem;
}

.stat-row {
  display: flex;
  gap: 0.5rem;
}

.stat-item-inline {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.5rem;
  background: rgba(148, 163, 184, 0.05);
  border-radius: 6px;
}

.stat-icon {
  font-size: 1rem;
}

.stat-value-sm {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--text-primary);
}

.stat-label-sm {
  font-size: 0.7rem;
  color: var(--text-secondary);
  font-weight: 500;
}

.overview-progress {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.03), rgba(99, 102, 241, 0.02));
  padding: 0.75rem;
  border-radius: 8px;
  border: 1px solid rgba(59, 130, 246, 0.08);
}

.progress-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.progress-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--text-primary);
}

.progress-value {
  font-size: 1rem;
  font-weight: 700;
  color: var(--primary);
}

.progress-bar {
  height: 4px;
  background: lightgrey;
  border-radius: 2px;
  overflow: hidden;
  margin-bottom: 0.25rem;
  position: relative;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #3b82f6, #1d4ed8, #2563eb);
  border-radius: 2px;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.progress-fill::after {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
  animation: shimmer 2s infinite;
}

@keyframes shimmer {
  0% {
    left: -100%;
  }
  100% {
    left: 100%;
  }
}

.progress-footer {
  text-align: center;
}

.progress-trend {
  font-size: 0.7rem;
  color: var(--text-secondary);
  font-weight: 500;
}

.form-group {
  margin-bottom: var(--space-5);
}

.form-label {
  display: block;
  margin-bottom: var(--space-2);
  font-weight: 500;
  color: var(--text-primary);
  font-size: var(--font-size-sm);
}

/* Approval Summary Alignment */
.approval-summary {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.approval-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 1rem;
  background: rgba(59, 130, 246, 0.05);
  border: 1px solid rgba(59, 130, 246, 0.1);
  border-radius: 8px;
  transition: all 0.2s ease;
}

.approval-item:hover {
  background: rgba(59, 130, 246, 0.08);
  border-color: rgba(59, 130, 246, 0.2);
}

.approval-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--text-primary);
}

.approval-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--primary);
  min-width: 3rem;
  text-align: right;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: var(--space-3) var(--space-4);
  border: 1px solid transparent;
  border-radius: var(--border-radius);
  font-size: var(--font-size-sm);
  font-weight: 500;
  text-decoration: none;
  cursor: pointer;
  transition: var(--transition);
  gap: var(--space-2);
}

.btn--primary {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  border: none;
  box-shadow: 0 1px 3px rgba(59, 130, 246, 0.3);
}

.btn--primary:hover {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  box-shadow: 0 2px 6px rgba(59, 130, 246, 0.4);
}

.btn--secondary {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  color: var(--text-primary);
  border: 1px solid rgba(226, 232, 240, 0.5);
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.btn--secondary:hover {
  background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.btn--sm {
  padding: 0.375rem 0.75rem;
  font-size: 0.875rem;
}

.header-actions {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
  flex-wrap: wrap;
}

@media (max-width: 768px) {
  .dashboard-grid {
    grid-template-columns: 1fr;
    gap: var(--space-3);
  }
  
  .overview-summary {
    flex-direction: column;
    gap: 0.75rem;
  }
  
  .stat-row {
    flex-direction: column;
    gap: 0.75rem;
  }
  
  .header-actions {
    flex-direction: column;
  }
  
  .card__header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
}
</style>
