/**
 * Ergon PWA – install prompt + service worker lifecycle manager
 * Included in both login.php and dashboard.php
 */
(function () {
  'use strict';

  let deferredPrompt = null;

  // ── Service Worker registration ──────────────────────────────────────────
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/ergon/sw.js', { scope: '/ergon/' })
      .then(reg => {
        // Notify user when a new version is waiting
        reg.addEventListener('updatefound', () => {
          const newWorker = reg.installing;
          newWorker.addEventListener('statechange', () => {
            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
              showUpdateBanner();
            }
          });
        });
      })
      .catch(() => {}); // Silently fail — app still works without SW
  }

  // ── Install prompt capture ───────────────────────────────────────────────
  window.addEventListener('beforeinstallprompt', e => {
    e.preventDefault();
    deferredPrompt = e;
    showInstallButton();
  });

  window.addEventListener('appinstalled', () => {
    deferredPrompt = null;
    hideInstallButton();
  });

  // ── Public API ───────────────────────────────────────────────────────────
  window.ergonPWA = {
    install: async function () {
      if (!deferredPrompt) return;
      deferredPrompt.prompt();
      const { outcome } = await deferredPrompt.userChoice;
      deferredPrompt = null;
      if (outcome === 'accepted') hideInstallButton();
    },
    isInstalled: function () {
      return window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;
    }
  };

  // ── UI helpers ───────────────────────────────────────────────────────────
  function showInstallButton() {
    // Don't show if already running as installed PWA
    if (window.ergonPWA.isInstalled()) return;
    const btn = document.getElementById('pwa-install-btn');
    if (btn) btn.style.display = 'flex';
  }

  function hideInstallButton() {
    const btn = document.getElementById('pwa-install-btn');
    if (btn) btn.style.display = 'none';
  }

  function showUpdateBanner() {
    const banner = document.getElementById('pwa-update-banner');
    if (banner) {
      banner.style.display = 'flex';
      return;
    }
    // Create banner dynamically if not in DOM
    const el = document.createElement('div');
    el.id = 'pwa-update-banner';
    el.innerHTML = `
      <span>🔄 A new version of Ergon is available.</span>
      <button onclick="window.location.reload()" style="margin-left:12px;background:#4f46e5;color:#fff;border:none;border-radius:6px;padding:6px 14px;cursor:pointer;font-size:13px;font-weight:600">Update</button>
      <button onclick="this.parentElement.remove()" style="margin-left:6px;background:transparent;color:inherit;border:none;cursor:pointer;font-size:18px;line-height:1">×</button>
    `;
    Object.assign(el.style, {
      position: 'fixed', bottom: '16px', left: '50%', transform: 'translateX(-50%)',
      background: '#1e293b', color: '#f1f5f9', border: '1px solid #334155',
      borderRadius: '10px', padding: '12px 16px', display: 'flex', alignItems: 'center',
      zIndex: '999999', boxShadow: '0 8px 24px rgba(0,0,0,0.3)', fontSize: '14px',
      whiteSpace: 'nowrap', maxWidth: '90vw'
    });
    document.body.appendChild(el);
  }
})();
