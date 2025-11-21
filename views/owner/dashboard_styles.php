<style>
/* Owner Dashboard Styles */
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.kpi-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.kpi-card__value {
    font-size: 2rem;
    font-weight: bold;
    color: #1f2937;
}

.kpi-card__label {
    color: #6b7280;
    font-size: 0.875rem;
    margin-top: 0.5rem;
}
</style>