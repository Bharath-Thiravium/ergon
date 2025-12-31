<?php 
$title = 'Module Management';
$active_page = 'modules';
$additional_css = '<link href="/ergon/assets/css/modules-management.css?v=' . time() . '" rel="stylesheet">';
$additional_css .= '<style>
.page-header{background:#f8f9fa;padding:2rem;margin:-2rem -2rem 2rem;border-bottom:1px solid #dee2e6}
.page-title h1{font-size:1.75rem;font-weight:600;color:#212529;margin:0 0 0.5rem;display:flex;align-items:center;gap:0.5rem}
.page-title p{color:#6c757d;margin:0;font-size:0.95rem}
.card{background:#fff;border:1px solid #dee2e6;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.05);margin-bottom:1.5rem}
.card__header{padding:1rem 1.5rem;background:#f8f9fa;border-bottom:1px solid #dee2e6}
.card__title{font-size:1.25rem;font-weight:600;color:#495057;margin:0;display:flex;align-items:center;gap:0.5rem}
.card__body{padding:1.5rem}
.modules-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem}
.module-card{background:#fff;border:1px solid #dee2e6;border-radius:6px;padding:1rem;transition:box-shadow 0.2s ease}
.module-card:hover{box-shadow:0 4px 8px rgba(0,0,0,0.1)}
.module-card--enabled{border-left:4px solid #28a745;background:#f8fff9}
.module-card--disabled{border-left:4px solid #dc3545;background:#fff5f5}
.module-card__header{display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;padding:0;background:none;border:none}
.module-card__header h3{font-size:1rem;font-weight:600;color:#212529;margin:0}
.badge{padding:0.25rem 0.5rem;border-radius:4px;font-size:0.75rem;font-weight:500;text-transform:uppercase}
.badge--success{background:#d4edda;color:#155724;border:1px solid #c3e6cb}
.badge--info{background:#d1ecf1;color:#0c5460;border:1px solid #bee5eb}
.module-actions{display:flex;justify-content:flex-end;margin-top:0.75rem}
.btn{padding:0.5rem 1rem;border:none;border-radius:6px;font-size:0.75rem;font-weight:600;cursor:pointer;transition:all 0.3s ease;text-decoration:none;display:inline-flex;align-items:center;gap:0.375rem;position:relative;overflow:hidden;text-transform:uppercase;letter-spacing:0.3px;box-shadow:0 2px 6px rgba(0,0,0,0.1)}
.btn::before{content:"";position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.2),transparent);transition:left 0.5s}
.btn:hover::before{left:100%}
.btn--success{background:linear-gradient(135deg,#28a745,#20c997);color:#fff;box-shadow:0 4px 15px rgba(40,167,69,0.3)}
.btn--success:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(40,167,69,0.4)}
.btn--danger{background:linear-gradient(135deg,#dc3545,#e74c3c);color:#fff;box-shadow:0 4px 15px rgba(220,53,69,0.3)}
.btn--danger:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(220,53,69,0.4)}
.btn:active{transform:translateY(0);transition:transform 0.1s}
.text-muted{color:#6c757d;font-size:0.875rem}
@media(max-width:768px){.page-header{padding:1rem;margin:-1rem -1rem 1rem}.modules-grid{grid-template-columns:1fr}.module-card__header{flex-direction:column;align-items:flex-start;gap:0.5rem}}
</style>';
ob_start();
?>

<div class="page-header">
    <div class="page-title">
        <h1><i class="bi bi-gear"></i> Module Management</h1>
        <p>Configure system modules and features</p>
    </div>
</div>

<!-- Active Modules Section -->
<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>✅</span> Active Modules
        </h2>
    </div>
    <div class="card__body">
        <div class="modules-grid">
            <?php foreach ($modules as $module): ?>
                <?php if ($module['enabled']): ?>
                <div class="module-card module-card--enabled">
                    <div class="module-card__header">
                        <h3><?= htmlspecialchars($module['label']) ?></h3>
                        <?php if ($module['is_basic']): ?>
                            <span class="badge badge--success">Basic</span>
                        <?php else: ?>
                            <span class="badge badge--info">Premium</span>
                        <?php endif; ?>
                    </div>
                    <div class="module-card__body">
                        <?php if (!$module['is_basic']): ?>
                        <div class="module-actions">
                            <button class="btn btn--danger" 
                                    onclick="toggleModule('<?= $module['name'] ?>', 'disable')">
                                <i class="bi bi-power"></i> Deactivate
                            </button>
                        </div>
                        <?php else: ?>
                        <div class="module-actions">
                            <span class="text-muted">Always active</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Inactive Modules Section -->
<div class="card">
    <div class="card__header">
        <h2 class="card__title">
            <span>❌</span> Inactive Modules
        </h2>
    </div>
    <div class="card__body">
        <div class="modules-grid">
            <?php foreach ($modules as $module): ?>
                <?php if (!$module['enabled']): ?>
                <div class="module-card module-card--disabled">
                    <div class="module-card__header">
                        <h3><?= htmlspecialchars($module['label']) ?></h3>
                        <span class="badge badge--info">Premium</span>
                    </div>
                    <div class="module-card__body">
                        <div class="module-actions">
                            <button class="btn btn--success" 
                                    onclick="toggleModule('<?= $module['name'] ?>', 'enable')">
                                <i class="bi bi-check-circle"></i> Activate
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function toggleModule(module, action) {
    if (!confirm(`Are you sure you want to ${action} this module?`)) {
        return;
    }
    
    const basePath = window.location.hostname === 'localhost' ? '/ergon' : '/ergon';
    fetch(basePath + '/modules/toggle', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `module=${module}&action=${action}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        alert('Network error: ' + error.message);
    });
}
</script>

<?php 
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/dashboard.php';
?>
