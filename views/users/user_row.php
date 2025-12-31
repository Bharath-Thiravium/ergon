<tr>
    <td>
        <div class="user-info">
            <div class="user-avatar"><?= strtoupper(substr($user['name'], 0, 1)) ?></div>
            <div>
                <strong><?= htmlspecialchars($user['name']) ?></strong>
                <br><small class="text-muted">ID: <?= $user['id'] ?></small>
            </div>
        </div>
    </td>
    <td data-sort-value="<?= $user['email'] ?>"><?= htmlspecialchars($user['email']) ?></td>
    <td data-sort-value="<?= $user['role'] ?>"><span class="badge badge--<?= $user['role'] === 'admin' ? 'success' : ($user['role'] === 'owner' ? 'primary' : 'info') ?>"><?= ucfirst($user['role']) ?></span></td>
    <td data-sort-value="<?= $user['status'] ?>"><span class="badge badge--<?= $user['status'] === 'inactive' ? 'inactive' : ($user['status'] === 'suspended' ? 'suspended' : ($user['status'] === 'terminated' ? 'terminated' : 'success')) ?>"><?= ucfirst($user['status']) ?></span></td>
    <td>
        <?php 
        $userStatus = $user['status'] ?? 'active';
        $userId = $user['id'];
        $userName = htmlspecialchars($user['name']);
        ?>
        <div class="ab-container">
            <button class="ab-btn ab-btn--view" onclick="viewUser(<?= $user['id'] ?>)" data-tooltip="View Details">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
            </button>
            <?php if (($_SESSION['role'] ?? '') === 'admin' && in_array(($user['role'] ?? 'user'), ['admin', 'owner'])): ?>
                <!-- Admins cannot manage other admins/owners -->
                <span class="text-muted">Protected</span>
            <?php elseif ($userStatus === 'terminated'): ?>
                <!-- Terminated Users: Only view for admins, owners can reactivate -->
                <?php if (($_SESSION['role'] ?? '') === 'owner'): ?>
                <button class="ab-btn ab-btn--success" onclick="activateUser(<?= $user['id'] ?>, '<?= $userName ?>')" data-tooltip="Reactivate User">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M9 12l2 2 4-4"/>
                        <circle cx="12" cy="12" r="10"/>
                    </svg>
                </button>
                <?php endif; ?>
                <span class="text-muted">Terminated</span>
            <?php elseif (($_SESSION['role'] ?? '') === 'owner' || (($_SESSION['role'] ?? '') === 'admin' && ($user['role'] ?? 'user') === 'user')): ?>
                <!-- Status-based buttons for manageable users -->
                <?php if ($userStatus === 'suspended'): ?>
                    <button class="ab-btn ab-btn--success" onclick="activateUser(<?= $user['id'] ?>, '<?= $userName ?>')" data-tooltip="Activate User">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M9 12l2 2 4-4"/>
                            <circle cx="12" cy="12" r="10"/>
                        </svg>
                    </button>
                <?php elseif ($userStatus === 'inactive'): ?>
                    <button class="ab-btn ab-btn--success" onclick="activateUser(<?= $user['id'] ?>, '<?= $userName ?>')" data-tooltip="Activate User">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M9 12l2 2 4-4"/>
                            <circle cx="12" cy="12" r="10"/>
                        </svg>
                    </button>
                <?php elseif ($userStatus === 'active'): ?>
                    <button class="ab-btn ab-btn--warning" onclick="deactivateUser(<?= $user['id'] ?>, '<?= $userName ?>')" data-tooltip="Deactivate User">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                        </svg>
                    </button>
                    <button class="ab-btn ab-btn--danger" onclick="suspendUser(<?= $user['id'] ?>, '<?= $userName ?>')" data-tooltip="Suspend User">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <line x1="22" y1="11" x2="16" y2="11"/>
                        </svg>
                    </button>
                <?php endif; ?>
                <button class="ab-btn ab-btn--edit" onclick="editUser(<?= $user['id'] ?>)" data-tooltip="Edit User">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                        <path d="M15 5l4 4"/>
                    </svg>
                </button>
                <button class="ab-btn ab-btn--progress" onclick="resetPassword(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')" data-tooltip="Reset Password">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M2.586 17.414A2 2 0 0 0 2 18.828V21a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h.172a2 2 0 0 0 1.414-.586l.814-.814a6.5 6.5 0 1 0-4-4z"/>
                        <circle cx="16.5" cy="7.5" r=".5"/>
                    </svg>
                </button>
                <button class="ab-btn ab-btn--delete" onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')" data-tooltip="Terminate User">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <line x1="17" y1="8" x2="22" y2="13"/>
                        <line x1="22" y1="8" x2="17" y2="13"/>
                    </svg>
                </button>
            <?php endif; ?>
        </div>
    </td>
</tr>
