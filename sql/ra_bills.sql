-- RA Bill header: one row per RA bill raised against a PO
CREATE TABLE IF NOT EXISTS ra_bills (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    po_id           BIGINT NOT NULL,
    po_number       VARCHAR(100) NOT NULL,
    company_id      BIGINT NOT NULL,
    customer_id     BIGINT NOT NULL,
    ra_bill_number  VARCHAR(30) NOT NULL,   -- e.g. RA-01, RA-02 per PO
    ra_sequence     INT NOT NULL DEFAULT 1, -- numeric sequence within PO
    bill_date       DATE NOT NULL,
    project         VARCHAR(255),
    contractor      VARCHAR(255),
    notes           TEXT,
    total_claimed   DECIMAL(15,2) DEFAULT 0,
    status          ENUM('draft','submitted','approved','rejected') DEFAULT 'draft',
    created_by      INT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_po_seq (po_id, ra_sequence)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- RA Bill line items: one row per PO item per RA bill
CREATE TABLE IF NOT EXISTS ra_bill_items (
    id                      INT AUTO_INCREMENT PRIMARY KEY,
    ra_bill_id              INT NOT NULL,
    po_item_id              BIGINT NOT NULL,
    line_number             INT NOT NULL,
    product_name            VARCHAR(255),
    description             TEXT,
    unit                    VARCHAR(50),
    po_quantity             DECIMAL(15,3),
    po_unit_price           DECIMAL(15,2),
    po_line_total           DECIMAL(15,2),
    -- cumulative from all previous RA bills for this item
    prev_claimed_qty        DECIMAL(15,3) DEFAULT 0,
    prev_claimed_pct        DECIMAL(7,3)  DEFAULT 0,
    prev_claimed_amount     DECIMAL(15,2) DEFAULT 0,
    -- this bill claim
    claim_type              ENUM('quantity','percentage') NOT NULL DEFAULT 'quantity',
    this_qty                DECIMAL(15,3) DEFAULT 0,
    this_pct                DECIMAL(7,3)  DEFAULT 0,
    this_amount             DECIMAL(15,2) DEFAULT 0,
    -- cumulative after this bill
    cumulative_qty          DECIMAL(15,3) DEFAULT 0,
    cumulative_pct          DECIMAL(7,3)  DEFAULT 0,
    cumulative_amount       DECIMAL(15,2) DEFAULT 0,
    FOREIGN KEY (ra_bill_id) REFERENCES ra_bills(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
