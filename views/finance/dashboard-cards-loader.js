// Global API Helper
async function apiGet(url) {
    try {
        const res = await fetch(url);
        const data = await res.json();
        if (!data.success) throw new Error(data.error || "API error");
        return data.data;
    } catch (err) {
        console.error("API Error:", url, err);
        return null;
    }
}

function formatINR(amount) {
    return Number(amount || 0).toLocaleString('en-IN', {
        style: 'currency',
        currency: 'INR'
    });
}

// 1. Quotations Card
async function loadQuotationsCard() {
    const data = await apiGet("/ergon/src/api/dashboard/quotations.php");
    if (!data) return;
    document.querySelector("#quotations-total").innerText = formatINR(data.total_value);
    document.querySelector("#quotations-pending").innerText = data.pending_count;
    document.querySelector("#quotations-placed").innerText = data.placed_count;
    document.querySelector("#quotations-rejected").innerText = data.rejected_count;
}

// 2. Purchase Orders Card
async function loadPOCard() {
    const data = await apiGet("/ergon/src/api/dashboard/purchase-orders.php");
    if (!data) return;
    document.querySelector("#po-total").innerText = formatINR(data.total_value);
    document.querySelector("#po-open").innerText = data.open_count;
    document.querySelector("#po-fulfilled").innerText = data.fulfilled;
    document.querySelector("#po-rate").innerText = data.rate + "%";
}

// 3. Invoices Card
async function loadInvoicesCard() {
    const data = await apiGet("/ergon/src/api/dashboard/invoices.php");
    if (!data) return;
    document.querySelector("#invoice-total").innerText = formatINR(data.total_value);
    document.querySelector("#invoice-paid").innerText = data.paid;
    document.querySelector("#invoice-unpaid").innerText = data.unpaid;
    document.querySelector("#invoice-overdue").innerText = data.overdue;
}

// 4. Outstanding by Customer
async function loadOutstandingByCustomer() {
    const data = await apiGet("/ergon/src/api/dashboard/outstanding-by-customer.php");
    if (!data) return;
    let total = data.reduce((s, c) => s + Number(c.outstanding), 0);
    document.querySelector("#outstanding-total").innerText = formatINR(total);
    let container = document.querySelector("#outstanding-list");
    container.innerHTML = "";
    data.slice(0, 5).forEach(cust => {
        container.innerHTML += `<div class="customer-item"><strong>${cust.customer_name}</strong><span>${formatINR(cust.outstanding)}</span></div>`;
    });
}

// 5. Aging Buckets
async function loadAgingBuckets() {
    const data = await apiGet("/ergon/src/api/dashboard/aging-buckets.php");
    if (!data) return;
    document.querySelector("#age-0-30").innerText = formatINR(data.bucket_0_30);
    document.querySelector("#age-31-60").innerText = formatINR(data.bucket_31_60);
    document.querySelector("#age-90-plus").innerText = formatINR(data.bucket_90_plus);
    let total = Number(data.bucket_0_30) + Number(data.bucket_31_60) + Number(data.bucket_90_plus);
    document.querySelector("#aging-total").innerText = formatINR(total);
}

// 6. Payments Trend
async function loadPaymentsTrend() {
    const data = await apiGet("/ergon/src/api/dashboard/payments.php");
    if (!data) return;
    document.querySelector("#payments-total").innerText = formatINR(data.total_paid);
    document.querySelector("#payments-avg").innerText = formatINR(data.avg_payment);
    document.querySelector("#payments-count").innerText = data.payment_count;
}

// Auto-load all cards
document.addEventListener("DOMContentLoaded", () => {
    loadQuotationsCard();
    loadPOCard();
    loadInvoicesCard();
    loadOutstandingByCustomer();
    loadAgingBuckets();
    loadPaymentsTrend();
});
