# Finance Backend System with Cash Flow Projections

A production-ready PHP backend system that syncs finance data from PostgreSQL to MySQL, computes cash flow projections, and provides REST API for recent activities and dashboard stats.

## Features

- **Multi-Type Sync**: Syncs invoices, quotations, purchase orders, and payments
- **Cash Flow Projections**: Computes expected inflow, PO commitments, and net cash flow
- **Incremental & Full Sync**: Supports both incremental (delta) and full data synchronization
- **Batch Processing**: Configurable batch sizes with transaction safety
- **Data Transformation**: Business logic for outstanding amounts, overdue calculations, and status overrides
- **Error Handling**: Comprehensive error logging with per-row error tracking
- **Idempotency**: Safe to run multiple times using UNIQUE KEY constraints
- **REST API**: Backend API for recent activities and dashboard stats (MySQL only)
- **Monitoring**: Built-in logging to files and database tables
- **Performance**: Optimized with prepared statements, indexes, and batch commits
- **High Precision**: Uses BCMath for accurate decimal calculations

## Installation

1. **Install Dependencies**
   ```bash
   composer install
   ```

2. **Configure Environment**
   ```bash
   cp .env.example .env
   # Edit .env with your database credentials
   ```

3. **Set Permissions** (Production)
   ```bash
   chmod 600 .env
   chown www-data:www-data .env
   ```

4. **Create MySQL Tables**
   ```bash
   mysql -u root -p finance_db < sql/schema.sql
   ```

## Configuration

### Environment Variables (.env)

```bash
# PostgreSQL Source Database (SAP)
PG_DSN=pgsql:host=localhost;port=5432;dbname=sap_source
PG_USER=postgres
PG_PASS=your_password

# MySQL Target Database (MariaDB)
MYSQL_DSN=mysql:host=localhost;port=3306;dbname=finance_db;charset=utf8mb4
MYSQL_USER=mysql_user
MYSQL_PASS=your_password

# Sync Configuration
COMPANY_PREFIX=ERGN
BATCH_SIZE=500
LAST_SYNC_LOOKUP_TABLE=sync_metadata

# Logging
LOG_FILE=/var/log/finance_sync.log

# API Configuration
API_BASE_URL=http://localhost/ergon/api
API_CORS_ORIGINS=*

# Cash Flow Configuration
CASHFLOW_ACTIVE_PO_STATUSES=Active,Released,Approved
```

## Usage

### Command Line Interface

#### Invoice Sync
```bash
# Basic incremental sync
php src/cli/sync_invoices.php --prefix=ERGN

# Full sync (ignore last sync timestamp)
php src/cli/sync_invoices.php --prefix=ERGN --full

# Limited sync for testing
php src/cli/sync_invoices.php --prefix=ERGN --limit=100
```

#### Activities Sync
```bash
# Basic incremental activities sync
php src/cli/sync_activities.php --prefix=ERGN

# Full activities sync
php src/cli/sync_activities.php --prefix=ERGN --full

# Limited activities sync for testing
php src/cli/sync_activities.php --prefix=ERGN --limit=50
```

#### Cash Flow Computation
```bash
# Basic incremental cashflow computation
php src/cli/compute_cashflow.php --prefix=ERGN

# Full cashflow computation
php src/cli/compute_cashflow.php --prefix=ERGN --full
```

### REST API Endpoints

#### Get Recent Activities
```bash
# Get latest 20 activities
GET /api/?action=activities&prefix=ERGN

# Filter by activity type
GET /api/?action=activities&prefix=ERGN&record_type=invoice

# Custom limit
GET /api/?action=activities&prefix=ERGN&limit=50
```

#### Get Activity Statistics
```bash
# Get activity stats by type
GET /api/?action=stats&prefix=ERGN
```

#### Get Dashboard Cash Flow Stats
```bash
# Get cash flow projections
GET /api/?action=dashboard&prefix=ERGN
```

### API Response Formats

#### Recent Activities
```json
{
  "success": true,
  "data": [
    {
      "record_type": "invoice",
      "icon": "💰",
      "document_number": "ERGN001",
      "customer_name": "Customer ABC",
      "customer_id": "CUST001",
      "status": "pending",
      "amount": 10000.00,
      "outstanding_amount": 5000.00,
      "due_date": "2024-01-15",
      "created_at": "2024-01-01 10:00:00",
      "formatted_amount": "10,000.00",
      "is_overdue": false
    }
  ],
  "timestamp": "2024-01-01 12:00:00"
}
```

#### Dashboard Stats
```json
{
  "success": true,
  "data": {
    "expected_inflow": 125000.50,
    "po_commitments": 85000.00,
    "net_cash_flow": 40000.50,
    "last_computed_at": "2024-01-01 15:30:00",
    "formatted": {
      "expected_inflow": "125,000.50",
      "po_commitments": "85,000.00",
      "net_cash_flow": "40,000.50"
    }
  },
  "timestamp": "2024-01-01 15:35:00"
}
```

## Activity Types & Icons

| Type | Icon | Description |
|------|------|-------------|
| quotation | 📝 | Sales quotations |
| purchase_order | 🛒 | Purchase orders |
| invoice | 💰 | Customer invoices |
| payment | 💳 | Payment receipts |

## Cash Flow Computations

### Expected Inflow
- **Definition**: Sum of outstanding amounts from all invoices
- **Formula**: `SUM(total_amount - amount_paid)` where `(total_amount - amount_paid) > 0`
- **Source**: `finance_invoices` table in PostgreSQL

### PO Commitments
- **Definition**: Sum of active purchase order values
- **Formula**: `SUM(po_total_value)` where `po_status` in configured active statuses
- **Source**: `finance_purchase_orders` table in PostgreSQL
- **Active Statuses**: Configurable via `CASHFLOW_ACTIVE_PO_STATUSES` environment variable

### Net Cash Flow
- **Definition**: Expected inflow minus PO commitments
- **Formula**: `expected_inflow - po_commitments`
- **Precision**: Uses BCMath for accurate decimal calculations

## Scheduling

### Cron (Recommended)

```bash
# Invoice sync every 10 minutes
*/10 * * * * /usr/bin/php /var/www/ergon/src/cli/sync_invoices.php --prefix=ERGN >> /var/log/finance_sync_ERGN.log 2>&1

# Activities sync every 5 minutes
*/5 * * * * /usr/bin/php /var/www/ergon/src/cli/sync_activities.php --prefix=ERGN >> /var/log/finance_activities_ERGN.log 2>&1

# Cashflow computation every 15 minutes
*/15 * * * * /usr/bin/php /var/www/ergon/src/cli/compute_cashflow.php --prefix=ERGN >> /var/log/finance_cashflow_ERGN.log 2>&1

# Full syncs daily
0 2 * * * /usr/bin/php /var/www/ergon/src/cli/sync_invoices.php --prefix=ERGN --full >> /var/log/finance_sync_ERGN_full.log 2>&1
0 3 * * * /usr/bin/php /var/www/ergon/src/cli/sync_activities.php --prefix=ERGN --full >> /var/log/finance_activities_ERGN_full.log 2>&1
0 4 * * * /usr/bin/php /var/www/ergon/src/cli/compute_cashflow.php --prefix=ERGN --full >> /var/log/finance_cashflow_ERGN_full.log 2>&1
```

### Systemd (Alternative)

See `examples/cron_and_systemd.txt` for complete systemd service and timer configurations.

## Monitoring & Troubleshooting

### Check Sync Status

```sql
-- Recent sync runs
SELECT * FROM sync_runs 
WHERE company_prefix = 'ERGN' 
ORDER BY created_at DESC 
LIMIT 10;

-- Recent errors
SELECT * FROM sync_errors 
WHERE company_prefix = 'ERGN' 
ORDER BY created_at DESC 
LIMIT 10;

-- Last sync timestamps
SELECT * FROM sync_metadata 
WHERE company_prefix = 'ERGN';

-- Dashboard stats
SELECT * FROM dashboard_stats 
WHERE company_prefix = 'ERGN';

-- Activity counts by type
SELECT record_type, COUNT(*) as count, SUM(amount) as total_amount
FROM finance_consolidated 
WHERE company_prefix = 'ERGN' 
GROUP BY record_type;
```

### Log Files

```bash
# View sync logs
tail -f /var/log/finance_sync.log

# View cron logs
tail -f /var/log/finance_sync_ERGN.log
tail -f /var/log/finance_activities_ERGN.log
tail -f /var/log/finance_cashflow_ERGN.log
```

### Reset Last Sync Timestamps

```sql
-- Reset all sync timestamps for a prefix
UPDATE sync_metadata 
SET last_sync_invoices = NULL, 
    last_sync_activities = NULL, 
    last_sync_cashflow = NULL 
WHERE company_prefix = 'ERGN';

-- Reset specific sync type
UPDATE sync_metadata 
SET last_sync_cashflow = NULL 
WHERE company_prefix = 'ERGN';
```

## Data Flow

1. **Source Query**: Fetches data from PostgreSQL with JOINs
2. **Transformation**: Applies business logic (outstanding, overdue, status)
3. **Upsert**: Inserts/updates MySQL using `ON DUPLICATE KEY UPDATE`
4. **Cash Flow Computation**: Processes invoice and PO data to compute projections
5. **Dashboard Stats**: Stores computed values in `dashboard_stats` table
6. **API Access**: Frontend queries MySQL only via REST API
7. **Logging**: Records run summary and individual errors
8. **Timestamp Update**: Updates last sync timestamp for incremental runs

## Business Logic

### Invoice Transformations
- **Outstanding Amount**: `max(0, taxable_amount - amount_paid)`
- **Status Override**: 
  - `paid` if `outstanding_amount <= 0`
  - `overdue` if past due date with outstanding amount
  - Otherwise keeps original status

### Activity Transformations
- **Quotations**: All tax fields = 0, outstanding = 0
- **Purchase Orders**: All tax fields = 0, outstanding = 0  
- **Invoices**: Full business logic with outstanding calculations
- **Payments**: amount_paid = amount, outstanding = 0

### Customer Name Fallback
- Uses `customer_name` from JOIN
- Falls back to `customer_id` if name is empty/null

## Testing

### Unit Tests
```bash
# Run all tests
composer test

# Run only unit tests
composer test-unit

# Run specific test
vendor/bin/phpunit tests/TransformerTest.php
vendor/bin/phpunit tests/ActivityTransformerTest.php
vendor/bin/phpunit tests/CashflowTest.php
```

### Integration Tests
```bash
# Run integration tests (uses SQLite in-memory)
composer test-integration
```

### Manual Testing
```bash
# Test invoice sync with limited rows
php src/cli/sync_invoices.php --prefix=TEST --limit=5

# Test activities sync with limited rows
php src/cli/sync_activities.php --prefix=TEST --limit=5

# Test cashflow computation
php src/cli/compute_cashflow.php --prefix=TEST --full

# Test API endpoints
curl "http://localhost/ergon/src/api/?action=activities&prefix=ERGN&limit=5"
curl "http://localhost/ergon/src/api/?action=dashboard&prefix=ERGN"
```

## API Integration Examples

### JavaScript/Frontend
```javascript
// Fetch recent activities
async function fetchRecentActivities(prefix, recordType = null) {
    const url = new URL('/ergon/src/api/', window.location.origin);
    url.searchParams.set('action', 'activities');
    url.searchParams.set('prefix', prefix);
    if (recordType) url.searchParams.set('record_type', recordType);
    
    const response = await fetch(url);
    const data = await response.json();
    
    if (data.success) {
        return data.data;
    } else {
        throw new Error(data.error);
    }
}

// Fetch dashboard stats
async function fetchDashboardStats(prefix) {
    const url = new URL('/ergon/src/api/', window.location.origin);
    url.searchParams.set('action', 'dashboard');
    url.searchParams.set('prefix', prefix);
    
    const response = await fetch(url);
    const data = await response.json();
    
    if (data.success) {
        return data.data;
    } else {
        throw new Error(data.error);
    }
}

// Usage
Promise.all([
    fetchRecentActivities('ERGN', 'invoice'),
    fetchDashboardStats('ERGN')
]).then(([activities, stats]) => {
    console.log('Recent Activities:', activities);
    console.log('Cash Flow Stats:', stats);
});
```

## Security

- Store `.env` with restricted permissions (600)
- Use dedicated database users with minimal privileges
- Enable TLS for database connections in production
- API includes CORS headers and input validation
- No PostgreSQL access from frontend (MySQL only)
- Prepared statements prevent SQL injection
- BCMath extension required for precise decimal calculations

## Performance Tuning

- Adjust `BATCH_SIZE` based on available memory and network
- Schedule syncs during off-peak hours
- Monitor database performance during sync
- Use proper indexes (included in schema.sql)
- Consider partitioning large tables by company_prefix
- Cashflow computation frequency can be adjusted based on data volatility

## Exit Codes

- `0` = Success (all rows processed)
- `1` = Partial success (some row errors, check `sync_errors` table)
- `2` = Fatal failure (check logs)

## Support

For issues and questions:
1. Check logs and `sync_errors` table
2. Verify configuration and connectivity
3. Test with `--limit` flag for debugging
4. Review this documentation
5. Check API responses for error details
6. Verify BCMath extension is installed for cashflow computations

## License

Proprietary - Internal use only.