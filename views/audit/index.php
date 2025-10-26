<?php $results = $data['audit_results']; ?>

<div class="header-actions">
    <button class="btn btn--primary" onclick="location.reload()">🔄 Refresh Audit</button>
</div>

<div class="dashboard-grid">
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🌐</div>
        </div>
        <div class="kpi-card__value"><?= $results['environment']['is_localhost'] ? 'LOCAL' : 'LIVE' ?></div>
        <div class="kpi-card__label">Environment</div>
        <div class="kpi-card__status"><?= $results['environment']['host'] ?></div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">📁</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($results['files'], fn($f) => $f['exists'])) ?>/<?= count($results['files']) ?></div>
        <div class="kpi-card__label">Files Present</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon">🎨</div>
        </div>
        <div class="kpi-card__value"><?= count(array_filter($results['css_features'] ?? [], fn($f) => $f)) ?>/<?= count($results['css_features'] ?? []) ?></div>
        <div class="kpi-card__label">CSS Features</div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">📁 Critical Files</h2>
        </div>
        <div class="card__body">
            <?php foreach ($results['files'] as $file => $info): ?>
            <div class="form-group">
                <div class="form-label"><?= htmlspecialchars($file) ?></div>
                <span class="kpi-card__status <?= $info['exists'] ? '' : 'kpi-card__status--pending' ?>">
                    <?= $info['exists'] ? '✓ EXISTS' : '✗ MISSING' ?>
                </span>
                <?php if ($info['exists']): ?>
                <small><?= number_format($info['size']) ?>b | <?= $info['modified'] ?> | <?= $info['hash'] ?></small>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="card">
        <div class="card__header">
            <h2 class="card__title">🎨 CSS Features</h2>
        </div>
        <div class="card__body">
            <?php if (!empty($results['css_features'])): ?>
                <?php foreach ($results['css_features'] as $feature => $present): ?>
                <div class="form-group">
                    <div class="form-label"><?= htmlspecialchars($feature) ?></div>
                    <span class="kpi-card__status <?= $present ? '' : 'kpi-card__status--pending' ?>">
                        <?= $present ? '✓ PRESENT' : '✗ MISSING' ?>
                    </span>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>CSS file not found</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card__header">
        <h2 class="card__title">📋 Action Items</h2>
    </div>
    <div class="card__body">
        <?php
        $actions = [];
        foreach ($results['files'] as $file => $info) {
            if (!$info['exists']) {
                $actions[] = "Upload missing file: " . $file;
            }
        }
        if (!empty($results['css_features'])) {
            foreach ($results['css_features'] as $feature => $present) {
                if (!$present) {
                    $actions[] = "Add CSS feature: " . $feature;
                }
            }
        }
        if (!($results['layout_features']['account_removed'] ?? false)) {
            $actions[] = "Remove Account section from layout";
        }
        ?>
        
        <?php if (empty($actions)): ?>
            <div class="form-group">
                <span class="kpi-card__status">✓ All checks passed</span>
            </div>
        <?php else: ?>
            <?php foreach ($actions as $action): ?>
            <div class="form-group">
                <div class="form-label">• <?= htmlspecialchars($action) ?></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
