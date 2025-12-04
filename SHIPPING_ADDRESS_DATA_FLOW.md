# Shipping Address Data Flow Documentation

## Overview

The shipping address data flow system synchronizes customer shipping addresses from PostgreSQL (SAP source) to MySQL (local database) and provides REST API access for frontend applications.

## Architecture

```
PostgreSQL (SAP)  →  Sync Process  →  MySQL (Local)  →  REST API  →  Frontend
```

## Data Source

### PostgreSQL Table: `finance_customershippingaddress`
- **Host**: 72.60.218.167:5432
- **Database**: modernsap
- **Table**: finance_customershippingaddress

### Schema Structure
```sql
id BIGINT PRIMARY KEY
label VARCHAR(255) NOT NULL
address_line1 VARCHAR(255) NOT NULL  
address_line2 VARCHAR(255) NOT NULL
city VARCHAR(255) NOT NULL
state VARCHAR(255) NOT NULL
pincode VARCHAR(20) NOT NULL
country VARCHAR(255) NOT NULL
is_default BOOLEAN NOT NULL DEFAULT 0
created_at TIMESTAMP NOT NULL
updated_at TIMESTAMP NOT NULL
customer_id BIGINT NOT NULL
```

## MySQL Target Table

### Table: `finance_customershippingaddress`
- **Engine**: InnoDB
- **Charset**: utf8mb4_unicode_ci
- **Indexes**: 
  - `idx_customer_id` on customer_id
  - `idx_is_default` on is_default

## Sync Components

### 1. CLI Sync Script
**File**: `src/cli/sync_shipping_addresses.php`

**Features**:
- Batch processing (1000 records per batch)
- Incremental sync with offset pagination
- Data transformation (boolean conversion, timestamp formatting)
- Upsert operations using `ON DUPLICATE KEY UPDATE`
- Error handling and logging

**Usage**:
```bash
php src/cli/sync_shipping_addresses.php
```

### 2. Web API Sync
**File**: `src/api/sync-shipping.php`

**Features**:
- Full table sync via HTTP endpoint
- JSON response format
- Real-time sync capability
- Error reporting

**Usage**:
```bash
GET /src/api/sync-shipping.php
```

**Response Format**:
```json
{
  "success": true,
  "message": "Synced 1250 shipping addresses"
}
```

## Data Transformations

### Boolean Conversion
```php
$row['is_default'] = $row['is_default'] ? 1 : 0;
```

### Timestamp Formatting
```php
$row['created_at'] = substr($row['created_at'], 0, 19);
$row['updated_at'] = substr($row['updated_at'], 0, 19);
```

## REST API Endpoints

### Get All Shipping Addresses
**File**: `src/api/shipping-addresses.php`

**Endpoint**: `GET /src/api/shipping-addresses.php`

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "label": "Home Address",
      "address_line1": "123 Main Street",
      "address_line2": "Apt 4B",
      "city": "Mumbai",
      "state": "Maharashtra",
      "pincode": "400001",
      "country": "India",
      "is_default": 1,
      "created_at": "2024-01-01 10:00:00",
      "updated_at": "2024-01-01 10:00:00",
      "customer_id": 12345
    }
  ],
  "timestamp": "2024-01-01 15:30:00"
}
```

## Data Flow Process

### 1. Source Query (PostgreSQL)
```sql
SELECT id, label, address_line1, address_line2, city, state, 
       pincode, country, is_default, created_at, updated_at, customer_id
FROM finance_customershippingaddress
ORDER BY id
LIMIT 1000 OFFSET 0
```

### 2. Data Transformation
- Convert PostgreSQL boolean to MySQL tinyint
- Format timestamps to MySQL compatible format
- Preserve all address components

### 3. Target Upsert (MySQL)
```sql
INSERT INTO finance_customershippingaddress 
(id, label, address_line1, address_line2, city, state, pincode, 
 country, is_default, created_at, updated_at, customer_id)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE
  label = VALUES(label),
  address_line1 = VALUES(address_line1),
  address_line2 = VALUES(address_line2),
  city = VALUES(city),
  state = VALUES(state),
  pincode = VALUES(pincode),
  country = VALUES(country),
  is_default = VALUES(is_default),
  updated_at = VALUES(updated_at)
```

### 4. API Access
- Frontend queries MySQL only
- No direct PostgreSQL access from frontend
- JSON formatted responses
- CORS enabled for cross-origin requests

## Sync Strategies

### Full Sync
- Processes entire table
- Used for initial setup or complete refresh
- Handles large datasets with batch processing

### Incremental Sync
- Uses offset-based pagination
- Processes records in configurable batches
- Continues until no more records found

## Error Handling

### Connection Errors
```php
try {
    $pg_conn = new PDO($pg_dsn, $pg_user, $pg_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
```

### Data Processing Errors
- Individual record failures don't stop batch processing
- Error messages logged with context
- HTTP 500 responses for API failures

## Performance Considerations

### Batch Size
- Default: 1000 records per batch
- Configurable based on memory and network constraints
- Prevents timeout issues with large datasets

### Indexing
- Primary key on `id` for fast lookups
- Index on `customer_id` for customer-based queries
- Index on `is_default` for default address queries

### Connection Management
- Single connection per sync operation
- Prepared statements for SQL injection prevention
- Connection pooling handled by PDO

## Security Features

### Database Security
- Dedicated PostgreSQL credentials
- MySQL connection through Database class
- No credentials exposed in frontend

### API Security
- CORS headers configured
- Input validation on API endpoints
- Error messages sanitized

## Monitoring & Troubleshooting

### Sync Verification
```sql
-- Check record count
SELECT COUNT(*) FROM finance_customershippingaddress;

-- Check recent updates
SELECT * FROM finance_customershippingaddress 
ORDER BY updated_at DESC LIMIT 10;

-- Check default addresses per customer
SELECT customer_id, COUNT(*) as default_count
FROM finance_customershippingaddress 
WHERE is_default = 1 
GROUP BY customer_id 
HAVING default_count > 1;
```

### Common Issues

#### Duplicate Default Addresses
- Multiple addresses marked as default for same customer
- Business logic should enforce single default per customer

#### Timestamp Format Issues
- PostgreSQL timestamps may include microseconds
- Truncated to 19 characters for MySQL compatibility

#### Boolean Conversion
- PostgreSQL boolean values need explicit conversion
- MySQL expects tinyint (0/1) values

## Integration Points

### Customer Management
- Links to customer records via `customer_id`
- Supports multiple addresses per customer
- Default address selection capability

### Order Processing
- Shipping addresses available for order fulfillment
- Address validation for delivery services
- Geographic analysis capabilities

### Reporting & Analytics
- Address distribution analysis
- Regional customer insights
- Delivery zone optimization

## Deployment Checklist

### Prerequisites
- PostgreSQL connection established
- MySQL table created with proper schema
- PHP PDO extensions installed
- Web server configured for API access

### Sync Setup
1. Configure PostgreSQL credentials
2. Test connection to source database
3. Run initial full sync
4. Verify data integrity
5. Set up automated sync schedule

### API Testing
```bash
# Test shipping addresses endpoint
curl "http://localhost/ergon/src/api/shipping-addresses.php"

# Test sync endpoint
curl "http://localhost/ergon/src/api/sync-shipping.php"
```

## Maintenance

### Regular Tasks
- Monitor sync performance
- Verify data consistency
- Update credentials as needed
- Review error logs

### Scaling Considerations
- Increase batch size for better performance
- Implement parallel processing for large datasets
- Consider read replicas for high-traffic scenarios
- Add caching layer for frequently accessed addresses

## Future Enhancements

### Planned Features
- Real-time sync with change data capture
- Address validation and geocoding
- Delivery zone mapping
- Performance metrics dashboard

### API Extensions
- Filter by customer_id
- Search by address components
- Pagination for large result sets
- Address validation endpoints

## Support

For issues with shipping address sync:
1. Check PostgreSQL connectivity
2. Verify MySQL table structure
3. Review sync logs for errors
4. Test API endpoints manually
5. Validate data transformations