# Firebase Cloud Messaging Integration Guide

## Overview

The application uses Firebase Cloud Messaging (FCM) to deliver push notifications to users' devices. This enables real-time notifications even when users are not actively using the application.

## Components

### FCM Token Management

1. **Token Registration**
   - `FcmTokenController`: Manages user FCM token registration
   - When users log in or grant notification permissions, their FCM token is saved to the database

2. **Token Storage**
   - Tokens are stored in the `fcm_token` field of the `users` table
   - Each user can have a single FCM token associated with their account

### Firebase Notification Service

The `FirebaseNotificationService` class handles all communication with the Firebase API:

1. **Authentication**
   - Uses service account credentials stored in `storage/app/firebase/hr-system-46dda-firebase-adminsdk-fbsvc-4465c46c3e.json`
   - Generates JWT tokens for Firebase API authentication
   - Automatically refreshes access tokens as needed

2. **Notification Sending**
   - Supports sending notifications to individual users via their FCM token
   - Includes methods for broadcasting to groups (all employees, all admins)

3. **Notification Structure**
   - Title: Brief description of the notification type
   - Body: Detailed message content
   - Link: URL that will open when the notification is clicked

## Implementation Details

### FCM Token Registration

```javascript
// Client-side code to request permission and register token
firebase.initializeApp(firebaseConfig);
const messaging = firebase.messaging();

messaging.requestPermission().then(() => {
  return messaging.getToken();
}).then((token) => {
  // Send token to server
  axios.post('/fcm-token', { token });
}).catch((err) => {
  console.log('Permission denied', err);
});
```

### Server-Side Integration

1. **Route Setup**
   
   The route for updating FCM tokens:
   ```php
   Route::post('/fcm-token', [App\Http\Controllers\FcmTokenController::class, 'update'])
       ->middleware('auth')
       ->name('fcm.token.update');
   ```

2. **Token Updates in FcmTokenController**
   
   ```php
   public function update(Request $request)
   {
       $validated = $request->validate([
           'token' => 'required|string'
       ]);
       
       auth()->user()->update([
           'fcm_token' => $validated['token']
       ]);
       
       return response()->json(['success' => true]);
   }
   ```

### Sending Notifications

The `FirebaseNotificationService` handles different notification scenarios:

1. **Individual Notification**
   
   ```php
   $firebaseService->sendNotification(
       $user->fcm_token,
       'Request Updated',
       'Your absence request has been approved',
       '/absence-requests/5'
   );
   ```

2. **Broadcasting to Groups**
   
   ```php
   // Send to all employees
   $firebaseService->sendNotificationToEmployees(
       'Company Announcement',
       'Important meeting tomorrow',
       '/announcements'
   );
   
   // Send to all admins
   $firebaseService->sendNotificationToAdmins(
       'System Alert',
       'User registration spike detected',
       '/admin/dashboard'
   );
   ```

## Notification Payload Structure

The notification payload sent to Firebase has this structure:

```json
{
  "message": {
    "token": "USER_FCM_TOKEN",
    "data": {
      "url": "/target-url",
      "title": "Notification Title",
      "body": "Notification body text"
    },
    "webpush": {
      "headers": {
        "Urgency": "high"
      },
      "fcm_options": {
        "link": "/target-url"
      }
    }
  }
}
```

## Service Worker Integration

For web applications, a Firebase service worker is needed to receive notifications:

```javascript
// firebase-messaging-sw.js
importScripts('https://www.gstatic.com/firebasejs/8.10.0/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.10.0/firebase-messaging.js');

firebase.initializeApp({
  apiKey: "...",
  authDomain: "...",
  projectId: "hr-system-46dda",
  messagingSenderId: "...",
  appId: "..."
});

const messaging = firebase.messaging();

// Handle background messages
messaging.onBackgroundMessage((payload) => {
  console.log('Background message received', payload);
  
  const notificationTitle = payload.data.title;
  const notificationOptions = {
    body: payload.data.body,
    icon: '/logo.png',
    data: {
      url: payload.data.url
    }
  };
  
  return self.registration.showNotification(notificationTitle, notificationOptions);
});

// Handle notification click
self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const url = event.notification.data.url;
  
  event.waitUntil(
    clients.matchAll({ type: 'window' }).then((windowClients) => {
      // Check if there is already a window open
      for (let i = 0; i < windowClients.length; i++) {
        const client = windowClients[i];
        if (client.url === url && 'focus' in client) {
          return client.focus();
        }
      }
      
      // If no window is open, open a new one
      if (clients.openWindow) {
        return clients.openWindow(url);
      }
    })
  );
});
```

## Troubleshooting

Common issues and solutions when working with Firebase notifications:

1. **Token Not Registering**
   - Ensure proper permissions are requested in the browser
   - Check that the service worker is properly registered

2. **Notifications Not Delivered**
   - Verify the FCM token is current and valid
   - Check Firebase console for delivery errors
   - Ensure the payload format matches Firebase requirements

3. **Background Notifications Not Working**
   - Verify the service worker is properly registered
   - Check that the notification payload includes all required fields
   - Test with different browsers to isolate browser-specific issues

## Security Considerations

1. **Service Account Security**
   - Keep the Firebase service account key secure
   - Do not expose the key in client-side code
   - Consider using environment variables for Firebase configuration

2. **Token Validation**
   - Validate FCM tokens before saving to prevent injection attacks
   - Only allow authenticated users to update their own FCM token

3. **Content Security**
   - Be cautious about the data included in notification payloads
   - Avoid including sensitive information in notification bodies 
