@if(auth()->check())
    <script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-messaging.js"></script>

    <script>
        (function() {
            if (window.fcmInitialized) return;
            window.fcmInitialized = true;

            const firebaseConfig = {
                apiKey: "AIzaSyACtBQgmlxnNEFbQv92apHYUGTjVpjHq0w",
                authDomain: "hr-system-46dda.firebaseapp.com",
                projectId: "hr-system-46dda",
                storageBucket: "hr-system-46dda.firebasestorage.app",
                messagingSenderId: "266829467806",
                appId: "1:266829467806:web:22d996fa2b7b283033ab8f"
            };

            firebase.initializeApp(firebaseConfig);
            const messaging = firebase.messaging();

            let currentFcmToken = localStorage.getItem('fcm_token') || '';
            let tokenLastSent = localStorage.getItem('fcm_token_last_sent') || 0;
            let isNewSession = {!! session('new_login') ? 'true' : 'false' !!};

            const TOKEN_UPDATE_INTERVAL = 24 * 60 * 60 * 1000;
            const isEdgeBrowser = navigator.userAgent.indexOf("Edg") !== -1;

            async function initializeFirebaseMessaging() {
                try {
                    const permission = await Notification.requestPermission();
                    if (permission !== 'granted') {
                        return;
                    }

                    const swOptions = {
                        updateViaCache: 'none'
                    };

                    const registration = await navigator.serviceWorker.register('/firebase-messaging-sw.js', swOptions);

                    if (isEdgeBrowser) {
                        await new Promise(resolve => setTimeout(resolve, 1000));
                    }

                    messaging.useServiceWorker(registration);

                    const now = Date.now();
                    let shouldUpdateServer = false;

                    try {
                        const tokenFromFirebase = await messaging.getToken();

                        if (tokenFromFirebase) {
                            if (
                                !currentFcmToken ||
                                tokenFromFirebase !== currentFcmToken ||
                                isNewSession ||
                                (now - tokenLastSent > TOKEN_UPDATE_INTERVAL)
                            ) {
                                currentFcmToken = tokenFromFirebase;
                                shouldUpdateServer = true;
                            }

                            localStorage.setItem('fcm_token', currentFcmToken);
                        }
                    } catch (error) {
                        if (currentFcmToken) {
                            try {
                                await messaging.deleteToken();
                            } catch (e) {}
                        }

                        try {
                            const newToken = await messaging.getToken();
                            if (newToken) {
                                currentFcmToken = newToken;
                                localStorage.setItem('fcm_token', newToken);
                                shouldUpdateServer = true;
                            }
                        } catch (e) {}
                    }

                    if (shouldUpdateServer && currentFcmToken) {
                        const updateResult = await updateTokenOnServer(currentFcmToken);
                        if (updateResult.success) {
                            localStorage.setItem('fcm_token_last_sent', now.toString());
                        }
                    }

                    messaging.onTokenRefresh(async () => {
                        try {
                            const refreshedToken = await messaging.getToken();
                            if (refreshedToken && refreshedToken !== currentFcmToken) {
                                currentFcmToken = refreshedToken;
                                localStorage.setItem('fcm_token', refreshedToken);
                                localStorage.setItem('fcm_token_last_sent', Date.now().toString());
                                await updateTokenOnServer(refreshedToken);
                            }
                        } catch (error) {}
                    });
                } catch (error) {}
            }

            async function updateTokenOnServer(token) {
                try {
                    const response = await fetch('{{ route("fcm.token.update") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ token })
                    });

                    const data = await response.json();
                    return data;
                } catch (error) {
                    return { success: false, error: error.message };
                }
            }

            messaging.onMessage((payload) => {
                console.log('Received foreground message, letting service worker handle it');
            });

            if (document.readyState === 'complete' || document.readyState === 'interactive') {
                initializeFirebaseMessaging();
            } else {
                document.addEventListener('DOMContentLoaded', initializeFirebaseMessaging, { once: true });
            }
        })();
    </script>
@endif
