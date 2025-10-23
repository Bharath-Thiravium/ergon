# Ergon Android WebView App

## Overview
This is the Android WebView application for the Ergon Employee Tracker system. It provides native mobile features including GPS location, offline sync, push notifications, and secure token storage.

## Features
- **Secure Auto-Login**: JWT tokens stored in EncryptedSharedPreferences
- **GPS Integration**: Native location services with geofencing
- **Offline Sync**: Queue attendance and task updates when offline
- **Push Notifications**: FCM integration for real-time notifications
- **File Upload**: Camera and gallery access for receipts/attachments

## Build Instructions

### Prerequisites
1. Android Studio Arctic Fox or later
2. Android SDK 23+ (minimum) / 34 (target)
3. Firebase project with FCM enabled
4. Kotlin 1.8.0+

### Setup Steps

1. **Clone/Copy Project**
   ```bash
   # Copy the android_ergon_app folder to your development machine
   ```

2. **Firebase Configuration**
   - Create a Firebase project at https://console.firebase.google.com
   - Add an Android app with package name: `com.ergon.app`
   - Download `google-services.json` and place in `app/` directory
   - Enable Firebase Cloud Messaging (FCM)
   - Copy the Server Key for server-side configuration

3. **Update Configuration**
   - Edit `MainActivity.kt` and update `ERGON_URL` to your server domain
   - Update `network_security_config.xml` with your domain
   - Configure FCM server key in your PHP environment

4. **Build APK**
   ```bash
   # Open in Android Studio and build, or use command line:
   ./gradlew assembleRelease
   ```

5. **Install APK**
   ```bash
   # Install on device
   adb install app/build/outputs/apk/release/app-release.apk
   ```

## Server Integration

### Required Server Endpoints
- `POST /ergon/api/login` - JWT authentication
- `POST /ergon/api/attendance` - Attendance check-in/out
- `POST /ergon/api/tasks/update` - Task progress updates
- `POST /ergon/api/register_device` - FCM token registration
- `POST /ergon/api/sync` - Offline data synchronization

### JavaScript Integration
Include `mobile-integration.js` in your web pages:
```html
<script src="/ergon/public/assets/js/mobile-integration.js"></script>
```

## Security Features
- HTTPS enforcement via network security config
- JWT token encryption using Android Keystore
- Geofence validation for attendance
- Anti-fraud detection (duplicate requests, location spoofing)

## Offline Capabilities
- Queue attendance records when offline
- Cache task updates locally
- Auto-sync when connection restored
- Conflict resolution for duplicate data

## Push Notifications
- Real-time notifications for task assignments
- Leave/expense approval notifications
- Attendance reminders
- System alerts

## Troubleshooting

### Common Issues
1. **Location not working**: Check permissions in device settings
2. **Push notifications not received**: Verify FCM configuration
3. **Auto-login fails**: Check JWT token validity and server endpoints
4. **Offline sync issues**: Verify network connectivity and server endpoints

### Debug Mode
Enable debug logging by adding to `MainActivity.kt`:
```kotlin
WebView.setWebContentsDebuggingEnabled(true)
```

## Production Deployment
1. Generate signed APK with release keystore
2. Test on multiple devices and Android versions
3. Configure ProGuard for code obfuscation
4. Upload to Google Play Console or distribute internally

## Support
For technical support, contact the development team or refer to the main Ergon documentation.