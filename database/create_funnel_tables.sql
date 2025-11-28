-- Create funnel_stats table
CREATE TABLE IF NOT EXISTS funnel_stats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_prefix VARCHAR(50) NOT NULL,
    
    quotation_count INT DEFAULT 0,
    quotation_value DECIMAL(15,2) DEFAULT 0,
    
    po_count INT DEFAULT 0,
    po_value DECIMAL(15,2) DEFAULT 0,
    po_conversion_rate DECIMAL(5,2) DEFAULT 0,
    
    invoice_count INT DEFAULT 0,
    invoice_value DECIMAL(15,2) DEFAULT 0,
    invoice_conversion_rate DECIMAL(5,2) DEFAULT 0,
    
    payment_count INT DEFAULT 0,
    payment_value DECIMAL(15,2) DEFAULT 0,
    payment_conversion_rate DECIMAL(5,2) DEFAULT 0,
    
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_prefix (company_prefix)
);

-- Create chart_stats table
CREATE TABLE IF NOT EXISTS chart_stats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_prefix VARCHAR(50) NOT NULL,
    
    -- Chart Card 1: Quotations
    quotation_pipeline_draft INT DEFAULT 0,
    quotation_pipeline_revised INT DEFAULT 0,
    quotation_pipeline_converted INT DEFAULT 0,
    win_rate DECIMAL(5,2) DEFAULT 0,
    avg_deal_size DECIMAL(15,2) DEFAULT 0,
    pipeline_value DECIMAL(15,2) DEFAULT 0,
    
    -- Chart Card 2: Purchase Orders
    po_count INT DEFAULT 0,
    po_fulfillment_rate DECIMAL(5,2) DEFAULT 0,
    po_avg_lead_time INT DEFAULT 0,
    po_open_commitments DECIMAL(15,2) DEFAULT 0,
    
    -- Chart Card 3: Invoices
    invoice_paid_count INT DEFAULT 0,
    invoice_unpaid_count INT DEFAULT 0,
    invoice_overdue_count INT DEFAULT 0,
    dso_days INT DEFAULT 0,
    bad_debt_risk DECIMAL(15,2) DEFAULT 0,
    collection_efficiency DECIMAL(5,2) DEFAULT 0,
    
    -- Chart Card 4: Outstanding Distribution
    outstanding_total DECIMAL(15,2) DEFAULT 0,
    top_customer_outstanding DECIMAL(15,2) DEFAULT 0,
    concentration_risk DECIMAL(5,2) DEFAULT 0,
    top3_exposure DECIMAL(15,2) DEFAULT 0,
    customer_diversity INT DEFAULT 0,
    
    -- Chart Card 5: Aging Buckets
    aging_current DECIMAL(15,2) DEFAULT 0,
    aging_watch DECIMAL(15,2) DEFAULT 0,
    aging_concern DECIMAL(15,2) DEFAULT 0,
    aging_critical DECIMAL(15,2) DEFAULT 0,
    provision_required DECIMAL(15,2) DEFAULT 0,
    recovery_rate DECIMAL(5,2) DEFAULT 0,
    credit_quality VARCHAR(20) DEFAULT 'Good',
    
    -- Chart Card 6: Payments
    payment_total DECIMAL(15,2) DEFAULT 0,
    payment_velocity_daily DECIMAL(15,2) DEFAULT 0,
    forecast_accuracy DECIMAL(5,2) DEFAULT 0,
    cash_conversion_days INT DEFAULT 0,
    
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_prefix (company_prefix)
);