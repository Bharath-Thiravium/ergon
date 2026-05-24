/**
 * Ergon Push Notification Manager
 * Handles Web Push (browser) + FCM (Capacitor Android)
 */
(function () {
    'use strict';

    const SUBSCRIBE_URL = '/ergon/api/push/subscribe';

    // ── Web Push ──────────────────────────────────────────────────────────────
    async function subscribeWebPush() {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;

        const vapidKey = document.querySelector('meta[name="vapid-public-key"]')?.content;
        if (!vapidKey) return;

        try {
            const reg = await navigator.serviceWorker.ready;
            let sub = await reg.pushManager.getSubscription();

            if (!sub) {
                sub = await reg.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(vapidKey),
                });
            }

            await fetch(SUBSCRIBE_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    type:     'web',
                    endpoint: sub.endpoint,
                    keys: {
                        p256dh: arrayBufferToBase64Url(sub.getKey('p256dh')),
                        auth:   arrayBufferToBase64Url(sub.getKey('auth')),
                    },
                }),
            });
        } catch (e) {
            console.warn('Ergon Push: Web Push failed', e);
        }
    }

    // ── FCM via Capacitor (uses global Capacitor.Plugins) ─────────────────────
    async function subscribeFCM() {
        if (!window.Capacitor || !window.Capacitor.isNativePlatform()) return;

        const PushNotifications = window.Capacitor.Plugins.PushNotifications;
        if (!PushNotifications) return;

        try {
            const perm = await PushNotifications.requestPermissions();
            if (perm.receive !== 'granted') return;

            await PushNotifications.register();

            PushNotifications.addListener('registration', async (token) => {
                await fetch(SUBSCRIBE_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        type:   'fcm',
                        token:  token.value,
                        device: navigator.userAgent,
                    }),
                });
            });

            // Foreground: show in-app modal
            PushNotifications.addListener('pushNotificationReceived', (n) => {
                if (window.showUniversalModal) showUniversalModal(n.body, 'info', n.title);
            });

            // Tap on notification: navigate to URL
            PushNotifications.addListener('pushNotificationActionPerformed', (action) => {
                const url = action.notification.data?.url;
                if (url) window.location.href = url;
            });

        } catch (e) {
            console.warn('Ergon Push: FCM failed', e);
        }
    }

    // ── Init ──────────────────────────────────────────────────────────────────
    async function init() {
        if (window.location.pathname.includes('/login')) return;

        const permission = await Notification.requestPermission();
        if (permission !== 'granted') return;

        await subscribeWebPush();
        await subscribeFCM();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64  = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const raw     = atob(base64);
        return Uint8Array.from([...raw].map(c => c.charCodeAt(0)));
    }

    function arrayBufferToBase64Url(buffer) {
        return btoa(String.fromCharCode(...new Uint8Array(buffer)))
            .replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
