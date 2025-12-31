<?php // Partial: proof modal and JS (supports images and PDFs) ?>
<div id="receiptModal" class="modal" style="display: none;">
    <div class="modal-content modal-content--large">
        <div class="modal-header">
            <h3>📄 Receipt</h3>
            <span class="modal-close" onclick="closeReceiptModal()">&times;</span>
        </div>
        <div class="modal-body" style="text-align:center;">
            <img id="receiptImage" src="" alt="Receipt" class="receipt-full" style="display:none; max-width:100%; max-height:70vh;" />
            <embed id="receiptEmbed" src="" type="application/pdf" width="100%" height="600px" style="display:none; border: none;" />
        </div>
    </div>
</div>

<script>
function openReceiptModal(fileUrl) {
    var ext = fileUrl.split('.').pop().toLowerCase().split('?')[0];
    var img = document.getElementById('receiptImage');
    var emb = document.getElementById('receiptEmbed');
    if (!img || !emb) return;
    if (['jpg','jpeg','png','gif'].indexOf(ext) !== -1) {
        emb.style.display = 'none';
        emb.src = '';
        img.style.display = 'block';
        img.src = fileUrl;
    } else if (ext === 'pdf') {
        img.style.display = 'none';
        img.src = '';
        emb.style.display = 'block';
        emb.src = fileUrl;
    } else {
        // fallback: open new tab
        window.open(fileUrl, '_blank');
        return;
    }
    if (typeof showModalById === 'function') {
        showModalById('receiptModal');
    } else {
        document.getElementById('receiptModal').style.display = 'block';
        document.body.style.overflow = 'hidden';
        document.body.classList.add('modal-open');
    }
}

function closeReceiptModal() {
    var img = document.getElementById('receiptImage');
    var emb = document.getElementById('receiptEmbed');
    if (img) { img.src = ''; img.style.display = 'none'; }
    if (emb) { emb.src = ''; emb.style.display = 'none'; }
    if (typeof hideModalById === 'function') {
        hideModalById('receiptModal');
    } else {
        var m = document.getElementById('receiptModal'); if (m) m.style.display = 'none';
        document.body.style.overflow = '';
        document.body.classList.remove('modal-open');
    }
}

window.addEventListener('click', function(event) {
    var receiptModal = document.getElementById('receiptModal');
    if (!receiptModal) return;
    if (event.target === receiptModal) {
        closeReceiptModal();
    }
});
</script>
