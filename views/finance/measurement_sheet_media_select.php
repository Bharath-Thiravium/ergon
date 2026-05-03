<?php
$title = 'Select Company Media';
$active_page = 'measurement_sheet';
ob_start();
?>

<div style="max-width: 900px; margin: 0 auto; padding: 20px;">
    <div style="text-align: center; margin-bottom: 30px;">
        <h2 style="margin: 0; font-size: 24px; font-weight: 700; color: #111827;">Select Company Media</h2>
        <p style="margin: 8px 0 0; color: #6b7280; font-size: 16px;">Choose logo and seal for your RA Bill</p>
    </div>

    <?php if (!empty($error)): ?>
    <div style="background: #fef2f2; border: 1px solid #fca5a5; color: #dc2626; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
        <strong>Error:</strong> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- RA Bill Details -->
    <?php if ($ra && $po): ?>
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 24px; margin-bottom: 24px; color: white; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; align-items: center;">
            <div>
                <div style="font-size: 12px; font-weight: 600; opacity: 0.8; margin-bottom: 4px;">RA BILL NUMBER</div>
                <div style="font-size: 20px; font-weight: 700;"><?= htmlspecialchars($ra['ra_bill_number'] ?? '—') ?></div>
            </div>
            <div>
                <div style="font-size: 12px; font-weight: 600; opacity: 0.8; margin-bottom: 4px;">PO NUMBER</div>
                <div style="font-size: 16px; font-weight: 600;"><?= htmlspecialchars($po['po_number'] ?? $po['internal_po_number'] ?? '—') ?></div>
            </div>
            <div>
                <div style="font-size: 12px; font-weight: 600; opacity: 0.8; margin-bottom: 4px;">CUSTOMER</div>
                <div style="font-size: 16px; font-weight: 600;"><?= htmlspecialchars($po['customer_name'] ?? '—') ?></div>
            </div>
            <div>
                <div style="font-size: 12px; font-weight: 600; opacity: 0.8; margin-bottom: 4px;">COMPANY</div>
                <div style="font-size: 16px; font-weight: 600;"><?= htmlspecialchars($po['company_name'] ?? '—') ?></div>
            </div>
            <div>
                <div style="font-size: 12px; font-weight: 600; opacity: 0.8; margin-bottom: 4px;">PROJECT</div>
                <div style="font-size: 16px; font-weight: 600;"><?= htmlspecialchars($ra['project'] ?? '—') ?></div>
            </div>
            <div>
                <div style="font-size: 12px; font-weight: 600; opacity: 0.8; margin-bottom: 4px;">BILL DATE</div>
                <div style="font-size: 16px; font-weight: 600;"><?= htmlspecialchars($ra['bill_date'] ?? '—') ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <form method="POST" action="/ergon/finance/measurement-sheet/<?= $ra['id'] ?>/update-media" style="background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); overflow: hidden;">
        
        <!-- Info Banner -->
        <div style="padding: 16px 24px; background: #eff6ff; border-bottom: 1px solid #e5e7eb;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <svg width="20" height="20" fill="#2563eb" viewBox="0 0 16 16">
                    <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                </svg>
                <div>
                    <div style="font-weight: 600; color: #1e40af; font-size: 14px;">Select Appropriate Media</div>
                    <div style="color: #3730a3; font-size: 13px;">Choose logo and seal that match the company: <strong><?= htmlspecialchars($po['company_name'] ?? 'Unknown Company') ?></strong></div>
                </div>
            </div>
        </div>
        
        <!-- Logo Selection -->
        <div style="padding: 24px; border-bottom: 1px solid #e5e7eb;">
            <h3 style="margin: 0 0 16px; font-size: 18px; font-weight: 600; color: #111827;">Company Logo</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 16px;">
                <?php if (empty($logoFiles)): ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #9ca3af;">
                        <p>No logos available. <a href="#upload-section" style="color: #2563eb;">Upload one below</a></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($logoFiles as $file): ?>
                        <?php 
                        $filename = pathinfo($file, PATHINFO_FILENAME);
                        $extension = pathinfo($file, PATHINFO_EXTENSION);
                        $relativePath = '/ergon/storage/company/logos/' . basename($file);
                        ?>
                        <label style="display: block; cursor: pointer; text-align: center; padding: 16px; border: 2px solid #e5e7eb; border-radius: 8px; transition: all 0.2s;" 
                               onmouseover="this.style.borderColor='#2563eb'" 
                               onmouseout="this.style.borderColor='#e5e7eb'"
                               onclick="this.style.borderColor='#2563eb'; this.style.background='#eff6ff'">
                            <input type="radio" name="selected_logo" value="<?= $filename ?>" style="margin-bottom: 8px;" 
                                   <?= ($ra['selected_logo'] ?? '') === $filename ? 'checked' : '' ?>
                                   onchange="document.querySelectorAll('label').forEach(l => {l.style.borderColor='#e5e7eb'; l.style.background='white'}); this.parentElement.style.borderColor='#2563eb'; this.parentElement.style.background='#eff6ff'">
                            <img src="<?= $relativePath ?>?v=<?= time() ?>" alt="Logo" style="max-width: 80px; max-height: 80px; object-fit: contain; display: block; margin: 0 auto 8px;">
                            <p style="margin: 0; font-size: 12px; font-weight: 600; color: #374151;"><?= $filename === 'default' ? 'Default Logo' : "Company {$filename}" ?></p>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Client Logo Selection -->
        <div style="padding: 24px; border-bottom: 1px solid #e5e7eb;">
            <h3 style="margin: 0 0 16px; font-size: 18px; font-weight: 600; color: #111827;">Client Logo</h3>
            <p style="margin: 0 0 16px; font-size: 13px; color: #6b7280;">Select client logo for: <strong><?= htmlspecialchars($po['customer_name'] ?? 'Unknown Customer') ?></strong></p>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 16px;">
                <?php if (empty($clientLogoFiles)): ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #9ca3af;">
                        <p>No client logos available. <a href="#upload-section" style="color: #2563eb;">Upload one below</a></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($clientLogoFiles as $file): ?>
                        <?php 
                        $filename = pathinfo($file, PATHINFO_FILENAME);
                        $extension = pathinfo($file, PATHINFO_EXTENSION);
                        $relativePath = '/ergon/storage/client/logos/' . basename($file);
                        ?>
                        <label style="display: block; cursor: pointer; text-align: center; padding: 16px; border: 2px solid #e5e7eb; border-radius: 8px; transition: all 0.2s;" 
                               onmouseover="this.style.borderColor='#059669'" 
                               onmouseout="this.style.borderColor='#e5e7eb'"
                               onclick="this.style.borderColor='#059669'; this.style.background='#f0fdf4'">
                            <input type="radio" name="selected_client_logo" value="<?= $filename ?>" style="margin-bottom: 8px;" 
                                   <?= ($ra['selected_client_logo'] ?? '') === $filename ? 'checked' : '' ?>
                                   onchange="document.querySelectorAll('label').forEach(l => {l.style.borderColor='#e5e7eb'; l.style.background='white'}); this.parentElement.style.borderColor='#059669'; this.parentElement.style.background='#f0fdf4'">
                            <img src="<?= $relativePath ?>?v=<?= time() ?>" alt="Client Logo" style="max-width: 80px; max-height: 80px; object-fit: contain; display: block; margin: 0 auto 8px;">
                            <p style="margin: 0; font-size: 12px; font-weight: 600; color: #374151;"><?= $filename === 'default' ? 'Default Client' : "Client {$filename}" ?></p>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Seal Selection -->
        <div style="padding: 24px; border-bottom: 1px solid #e5e7eb;">
            <h3 style="margin: 0 0 16px; font-size: 18px; font-weight: 600; color: #111827;">Company Seal</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 16px;">
                <?php if (empty($sealFiles)): ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #9ca3af;">
                        <p>No seals available. <a href="#upload-section" style="color: #2563eb;">Upload one below</a></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($sealFiles as $file): ?>
                        <?php 
                        $filename = pathinfo($file, PATHINFO_FILENAME);
                        $extension = pathinfo($file, PATHINFO_EXTENSION);
                        $relativePath = '/ergon/storage/company/seals/' . basename($file);
                        ?>
                        <label style="display: block; cursor: pointer; text-align: center; padding: 16px; border: 2px solid #e5e7eb; border-radius: 8px; transition: all 0.2s;" 
                               onmouseover="this.style.borderColor='#2563eb'" 
                               onmouseout="this.style.borderColor='#e5e7eb'"
                               onclick="this.style.borderColor='#2563eb'; this.style.background='#eff6ff'">
                            <input type="radio" name="selected_seal" value="<?= $filename ?>" style="margin-bottom: 8px;" 
                                   <?= ($ra['selected_seal'] ?? '') === $filename ? 'checked' : '' ?>
                                   onchange="document.querySelectorAll('label').forEach(l => {l.style.borderColor='#e5e7eb'; l.style.background='white'}); this.parentElement.style.borderColor='#2563eb'; this.parentElement.style.background='#eff6ff'">
                            <img src="<?= $relativePath ?>?v=<?= time() ?>" alt="Seal" style="max-width: 80px; max-height: 80px; object-fit: contain; display: block; margin: 0 auto 8px; border-radius: 50%;">
                            <p style="margin: 0; font-size: 12px; font-weight: 600; color: #374151;"><?= $filename === 'default' ? 'Default Seal' : "Company {$filename}" ?></p>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Action Buttons -->
        <div style="padding: 24px; display: flex; justify-content: space-between; align-items: center; background: #f9fafb;">
            <a href="/ergon/finance/measurement-sheet/<?= $ra['id'] ?>/print" style="color: #6b7280; text-decoration: none; font-weight: 600;">
                Skip & Print Basic Sheet
            </a>
            <div style="display: flex; gap: 12px;">
                <a href="/ergon/finance/measurement-sheet" style="padding: 10px 20px; background: #6b7280; color: white; text-decoration: none; border-radius: 8px; font-weight: 600;">
                    Back to PO List
                </a>
                <button type="submit" name="print_type" value="basic" style="padding: 10px 20px; background: #2563eb; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                    Save & Print RA Bill
                </button>
                <button type="submit" name="print_type" value="clearance" style="padding: 10px 20px; background: #059669; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                    Save & Print Clearance Sheet
                </button>
            </div>
        </div>
    </form>

    <!-- Upload Section -->
    <div id="upload-section" style="margin-top: 30px; background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); overflow: hidden;">
        <div style="padding: 20px; border-bottom: 1px solid #e5e7eb; background: #f9fafb;">
            <h3 style="margin: 0; font-size: 18px; font-weight: 600; color: #111827;">Upload New Media</h3>
            <p style="margin: 4px 0 0; color: #6b7280; font-size: 14px;">Upload logos and seals for future use</p>
        </div>
        
        <form method="POST" action="/ergon/finance/company-media" enctype="multipart/form-data" style="padding: 20px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr 2fr 1fr; gap: 16px; align-items: end;">
                <div>
                    <label style="display: block; margin-bottom: 4px; font-weight: 600; color: #374151;">Media Type:</label>
                    <select name="upload_type" required style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px;">
                        <option value="">Select Type</option>
                        <option value="logo">Company Logo</option>
                        <option value="seal">Company Seal</option>
                        <option value="client_logo">Client Logo</option>
                    </select>
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 4px; font-weight: 600; color: #374151;">Company ID:</label>
                    <input type="text" name="company_id" value="default" required style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 4px; font-weight: 600; color: #374151;">Select File:</label>
                    <input type="file" name="media_file" accept="image/png,image/jpeg,image/jpg" required style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px;">
                </div>
                
                <div>
                    <button type="submit" style="width: 100%; padding: 10px; background: #059669; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">
                        Upload
                    </button>
                </div>
            </div>
            <input type="hidden" name="return_to" value="/ergon/finance/measurement-sheet/<?= $ra['id'] ?>/select-media">
        </form>
    </div>
</div>

<script>
// Auto-select first available options if none selected
document.addEventListener('DOMContentLoaded', function() {
    const logoRadios = document.querySelectorAll('input[name="selected_logo"]');
    const sealRadios = document.querySelectorAll('input[name="selected_seal"]');
    
    if (logoRadios.length > 0 && !document.querySelector('input[name="selected_logo"]:checked')) {
        logoRadios[0].checked = true;
        logoRadios[0].parentElement.style.borderColor = '#2563eb';
        logoRadios[0].parentElement.style.background = '#eff6ff';
    }
    
    if (sealRadios.length > 0 && !document.querySelector('input[name="selected_seal"]:checked')) {
        sealRadios[0].checked = true;
        sealRadios[0].parentElement.style.borderColor = '#2563eb';
        sealRadios[0].parentElement.style.background = '#eff6ff';
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/dashboard.php';