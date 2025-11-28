# Install PostgreSQL on Laragon

## Steps to add PostgreSQL to Laragon:

1. **Download PostgreSQL portable:**
   - Go to https://www.enterprisedb.com/downloads/postgres-postgresql-downloads
   - Download PostgreSQL 15.x for Windows x64

2. **Extract to Laragon:**
   - Extract to `C:\laragon\bin\postgresql\postgresql-15.x`

3. **Initialize database:**
   ```cmd
   cd C:\laragon\bin\postgresql\postgresql-15.x\bin
   initdb -D ../data -U postgres
   ```

4. **Start PostgreSQL:**
   ```cmd
   pg_ctl -D ../data -l ../logfile start
   ```

5. **Create finance database:**
   ```cmd
   createdb -U postgres ergon_finance
   ```

6. **Create tables:**
   ```sql
   CREATE TABLE finance_invoices (
       id SERIAL PRIMARY KEY,
       invoice_number VARCHAR(50),
       customer_name VARCHAR(100),
       total_amount DECIMAL(10,2),
       outstanding_amount DECIMAL(10,2),
       due_date DATE
   );

   CREATE TABLE finance_quotations (
       id SERIAL PRIMARY KEY,
       quote_number VARCHAR(50),
       customer_name VARCHAR(100),
       amount DECIMAL(10,2),
       status VARCHAR(20)
   );

   CREATE TABLE finance_customers (
       id SERIAL PRIMARY KEY,
       customer_name VARCHAR(100),
       contact_email VARCHAR(100)
   );
   ```

## Alternative: Use existing MySQL
Since you already have MySQL working, consider using MySQL for finance data instead of PostgreSQL.