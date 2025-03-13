importScripts('https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.10.1/firebase-messaging.js');

const firebaseConfig = {
    apiKey: "AIzaSyACtBQgmlxnNEFbQv92apHYUGTjVpjHq0w",
    authDomain: "hr-system-46dda.firebaseapp.com",
    projectId: "hr-system-46dda",
    storageBucket: "hr-system-46dda.firebasestorage.app",
    messagingSenderId: "266829467806",
    appId: "1:266829467806:web:22d996fa2b7b283033ab8f"
};

if (!firebase.apps.length) {
    firebase.initializeApp(firebaseConfig);
}

const messaging = firebase.messaging();

self.addEventListener('push', function(event) {
    console.log('[firebase-messaging-sw.js] Push received');

    let payload;
    try {
        payload = event.data.json();
    } catch (e) {
        console.error('Error parsing push event data:', e);
        return;
    }

    const data = payload.data || {};
    const notificationTitle = data.title || 'إشعار جديد';
    const notificationBody = data.body || '';

    const notificationOptions = {
        body: notificationBody,
        icon: '/logo.png',
        badge: '/badge.png',
        data: data,
        vibrate: [100, 50, 100],
        requireInteraction: true,
        dir: 'rtl',
        lang: 'ar',
        tag: `notification_${Date.now()}`
    };

    event.waitUntil(
        self.registration.showNotification(notificationTitle, notificationOptions)
    );
});

messaging.onBackgroundMessage((payload) => {
    console.log('[firebase-messaging-sw.js] Received background message ', payload);
    // لا نحتاج لمعالجة إضافية هنا لأن حدث 'push' سيتعامل معها
});

self.addEventListener('notificationclick', function(event) {
    console.log('[firebase-messaging-sw.js] Notification clicked: ', event);

    try {
        event.notification.close();
    } catch (e) {
        console.error('Error closing notification:', e);
    }

    let urlToOpen;
    try {
        urlToOpen = (event.notification.data?.url ||
                   determineUrlFromType(event.notification.data?.type) ||
                   '/dashboard');
    } catch (e) {
        console.error('Error getting URL from notification:', e);
        urlToOpen = '/dashboard';
    }

    event.waitUntil(
        clients.matchAll({
            type: 'window',
            includeUncontrolled: true
        }).then(function(clientList) {
            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                const clientUrl = new URL(client.url);
                const targetUrl = new URL(urlToOpen, self.location.origin);

                if (clientUrl.pathname === targetUrl.pathname && 'focus' in client) {
                    return client.focus();
                }
            }

            return clients.openWindow(urlToOpen);
        }).catch(function(error) {
            console.error('Error navigating to URL:', error);
            return clients.openWindow('/dashboard');
        })
    );
});

self.addEventListener('install', function(event) {
    console.log('[firebase-messaging-sw.js] Service Worker installed');
    self.skipWaiting();
});

self.addEventListener('activate', function(event) {
    console.log('[firebase-messaging-sw.js] Service Worker activated');
    event.waitUntil(clients.claim());
});

function determineUrlFromType(type) {
    if (!type) return '/dashboard';

    const urlMap = {
        'new_leave_request': '/leaves/requests',
        'leave_request_status_update': '/leaves/my-requests',
        'leave_request_modified': '/leaves/requests',
        'leave_request_deleted': '/leaves/requests',

        'new_permission_request': '/permissions/requests',
        'permission_request_status_update': '/permissions/my-requests',
        'permission_request_modified': '/permissions/requests',
        'permission_request_deleted': '/permissions/requests',

        'new_overtime_request': '/overtime/requests',
        'overtime_status_updated': '/overtime/my-requests',
        'overtime_request_modified': '/overtime/requests',
        'overtime_request_deleted': '/overtime/requests',

        'general_notification': '/notifications',
        'system_notification': '/dashboard'
    };

    return urlMap[type] || '/dashboard';
}
