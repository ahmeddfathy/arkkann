# Notification System Overview

## Architecture Overview

The notification system in this application is built with a multi-layered architecture that handles different types of notifications for various user roles (employees, managers, HR) across different request types (absence, permissions, overtime).

### Key Components

1. **Controllers**
   - `NotificationController` - Handles user notifications display, marking as read, and retrieving unread counts
   - `AdminNotificationController` - Manages notifications for administrative users
   - `BroadNotificationController` - Handles broadcast notifications to multiple users

2. **Services**
   - `NotificationService` - Core service that orchestrates notification creation and delivery
   - `FirebaseNotificationService` - Manages push notifications via Firebase Cloud Messaging
   - `NotificationPermissionService` - Handles permission-related notifications
   - `NotificationOvertimeService` - Manages overtime request notifications

3. **Specialized Notification Services**
   - `ManagerNotificationService` - Notifications targeted to managers
   - `EmployeeNotificationService` - Notifications targeted to employees
   - `OvertimeManagerNotificationService` - Manager notifications for overtime requests
   - `ManagerPermissionNotificationService` - Manager notifications for permission requests
   - `EmployeePermissionNotificationService` - Employee notifications for permission responses
   - `OvertimeEmployeeNotificationService` - Employee notifications for overtime responses

## Notification Flow

1. **Request Creation**
   - When a user creates a request (absence, permission, overtime), the controller calls the appropriate notification service
   - The notification service creates database entries and sends push notifications to relevant stakeholders

2. **Status Updates**
   - When managers or HR respond to requests, notifications are sent to inform employees
   - Additional notifications may be sent to other stakeholders (HR team, team managers)

3. **Notification Delivery**
   - In-app notifications are stored in the database
   - Push notifications are sent via Firebase to user devices with registered FCM tokens

## Key Features

### Database Notifications
- Stored in the `notifications` table
- Include type, related data, user ID, read status
- Supports broadcasting to groups of users

### Firebase Push Notifications
- Sent via FCM to user devices
- Supports web push notifications with clickable links
- Configurable titles, bodies, and target URLs

### Notification Types
- Request submissions
- Status updates
- Administrative decisions
- Broadcast announcements
- Reset/modification events

## Integration Points

1. **Routes**
   - `/notifications` - View all notifications
   - `/notifications/unread` - View unread notifications
   - `/notifications/unread-count` - Get count of unread notifications
   - `/notifications/{notification}/mark-as-read` - Mark notification as read
   - `/notifications/{decision}/acknowledge` - Acknowledge administrative decisions

2. **User Interface**
   - Notification bell with unread count
   - Notification lists with read/unread status
   - Action links within notifications

## Best Practices for Extending the System

1. **Adding New Notification Types**
   - Create a specific method in the appropriate service
   - Follow the pattern of existing notifications
   - Include all relevant data in the notification payload

2. **Creating New Notification Channels**
   - Implement a new service class for the channel
   - Inject the service into NotificationService if needed

3. **Modifying Notification Content**
   - Update the relevant service method
   - Ensure all necessary data is included in the payload 
