<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Upload CSV - Finance System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { padding: 8px; width: 300px; }
        input[type="submit"] { width: auto; background: #007cba; color: white; border: none; padding: 10px 20px; cursor: pointer; }
        .note { background: #f0f8ff; padding: 15px; border-left: 4px solid #007cba; margin: 20px 0; }
    </style>
</head>
<body>
    <h2>Upload CSV to Finance System</h2>
    
    <div class="note">
        <strong>Instructions:</strong>
        <ul>
            <li>Select your company prefix (e.g., ERGN)</li>
            <li>Choose the correct record type for your CSV</li>
            <li>Upload CSV file (max 5MB, 5000 rows)</li>
        </ul>
    </div>

    <form action="process_upload.php" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label>Company Prefix:</label>
            <input type="text" name="company_prefix" required placeholder="e.g., ERGN" />
        </div>
        
        <div class="form-group">
            <label>Record Type:</label>
            <select name="record_type" required>
                <option value="">-- Select Type --</option>
                <option value="invoice">Invoice</option>
                <option value="quotation">Quotation</option>
                <option value="purchase_order">Purchase Order</option>
                <option value="payment">Payment</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>CSV File:</label>
            <input type="file" name="csvfile" accept=".csv,text/csv" required />
        </div>
        
        <div class="form-group">
            <input type="submit" value="Upload & Process" />
        </div>
    </form>

    <h3>Expected CSV Headers by Type:</h3>
    <ul>
        <li><strong>Invoice:</strong> invoice_number,total_amount,amount_paid,taxable_amount,igst,cgst,sgst,due_date,invoice_date,customer_id,customer_name,customer_gstin,status</li>
        <li><strong>Quotation:</strong> quotation_number,amount,customer_id,customer_name,customer_gstin,status</li>
        <li><strong>Purchase Order:</strong> po_number,po_total_value,customer_id,customer_name,customer_gstin,status</li>
        <li><strong>Payment:</strong> payment_id,amount,customer_id,customer_name,customer_gstin,status</li>
    </ul>
</body>
</html>
