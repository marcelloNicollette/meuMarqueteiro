/**
 * Service Worker — Meu Marqueteiro
 * Gerencia notificações push e cache básico do PWA
 */

const CACHE_NAME = 'marqueteiro-v1';

// ── Instalar ──────────────────────────────────────────────────
self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(clients.claim());
});

// ── Receber push ──────────────────────────────────────────────
self.addEventListener('push', (event) => {
    if (!event.data) return;

    let data = {};
    try {
        data = event.data.json();
    } catch (e) {
        data = { title: 'Meu Marqueteiro', body: event.data.text() };
    }

    const title   = data.title   || 'Meu Marqueteiro';
    const options = {
        body:    data.body    || '',
        icon:    data.icon    || '/images/mascote-robo.jpg',
        badge:   data.badge   || '/images/mascote-robo.jpg',
        tag:     data.tag     || 'marqueteiro',
        data:    { url: data.url || '/mayor/chat' },
        requireInteraction: data.requireInteraction || false,
        actions: data.actions || [],
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

// ── Clique na notificação ─────────────────────────────────────
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const url = event.notification.data?.url || '/mayor/chat';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // Se já tem uma janela aberta, foca nela
                for (const client of clientList) {
                    if (client.url.includes(self.location.origin) && 'focus' in client) {
                        client.navigate(url);
                        return client.focus();
                    }
                }
                // Caso contrário, abre nova janela
                if (clients.openWindow) {
                    return clients.openWindow(url);
                }
            })
    );
});