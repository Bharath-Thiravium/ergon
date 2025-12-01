// 1. Quotations Status — Donut Chart
async function chartQuotations() {
    const data = await apiGet("/ergon/src/api/dashboard/quotations.php");
    if (!data) return;
    new Chart(document.getElementById("chart-quotations"), {
        type: "doughnut",
        data: {
            labels: ["Pending", "Placed", "Rejected"],
            datasets: [{
                data: [data.pending_count, data.placed_count, data.rejected_count],
                backgroundColor: ["#FFA500", "#4CAF50", "#F44336"]
            }]
        },
        options: {
            cutout: "60%",
            plugins: { legend: { display: true } }
        }
    });
}

// 2. Purchase Orders — Bar Chart
async function chartPurchaseOrders() {
    const data = await apiGet("/ergon/src/api/dashboard/purchase-orders.php");
    if (!data) return;
    new Chart(document.getElementById("chart-po"), {
        type: "bar",
        data: {
            labels: ["Open", "Fulfilled"],
            datasets: [{
                data: [data.open_count, data.fulfilled],
                backgroundColor: ["#FF6B6B", "#4CAF50"]
            }]
        },
        options: { plugins: { legend: { display: false } } }
    });
}

// 3. Invoice Status — Donut Chart
async function chartInvoices() {
    const data = await apiGet("/ergon/src/api/dashboard/invoices.php");
    if (!data) return;
    new Chart(document.getElementById("chart-invoices"), {
        type: "doughnut",
        data: {
            labels: ["Paid", "Unpaid", "Overdue"],
            datasets: [{
                data: [data.paid, data.unpaid, data.overdue],
                backgroundColor: ["#4CAF50", "#FFA500", "#F44336"]
            }]
        },
        options: {
            cutout: "58%",
            plugins: { legend: { display: true } }
        }
    });
}

// 4. Outstanding by Customer — Horizontal Bar Chart
async function chartOutstanding() {
    const data = await apiGet("/ergon/src/api/dashboard/outstanding-by-customer.php");
    if (!data) return;
    const labels = data.map(x => x.customer_name);
    const values = data.map(x => Number(x.outstanding));
    new Chart(document.getElementById("chart-outstanding"), {
        type: "bar",
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: "#2196F3"
            }]
        },
        options: {
            indexAxis: "y",
            plugins: { legend: { display: false } }
        }
    });
}

// 5. Aging Buckets — Bar Chart
async function chartAging() {
    const d = await apiGet("/ergon/src/api/dashboard/aging-buckets.php");
    if (!d) return;
    new Chart(document.getElementById("chart-aging"), {
        type: "bar",
        data: {
            labels: ["0-30 Days", "31-60 Days", "90+ Days"],
            datasets: [{
                data: [d.bucket_0_30, d.bucket_31_60, d.bucket_90_plus],
                backgroundColor: ["#4CAF50", "#FFA500", "#F44336"]
            }]
        },
        options: { plugins: { legend: { display: false } } }
    });
}

// 6. Payments Trend — Line Chart
async function chartPayments() {
    const d = await apiGet("/ergon/src/api/dashboard/payments.php");
    if (!d) return;
    new Chart(document.getElementById("chart-payments"), {
        type: "line",
        data: {
            labels: ["Total Paid", "Avg Payment", "Count"],
            datasets: [{
                data: [d.total_paid, d.avg_payment, d.payment_count],
                borderColor: "#2196F3",
                backgroundColor: "rgba(33, 150, 243, 0.1)",
                tension: 0.4
            }]
        },
        options: { plugins: { legend: { display: false } } }
    });
}

// Auto-load all charts
document.addEventListener("DOMContentLoaded", () => {
    chartQuotations();
    chartPurchaseOrders();
    chartInvoices();
    chartOutstanding();
    chartAging();
    chartPayments();
});
