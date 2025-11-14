# ClickBasket Mobile App - APK Build Guide

## Prerequisites

### Required Software
1. **Node.js** (v16 or higher) - [Download](https://nodejs.org/)
2. **Expo CLI** - Install globally: `npm install -g @expo/cli`
3. **EAS CLI** - Install globally: `npm install -g eas-cli`
4. **Android Studio** (for local builds) - [Download](https://developer.android.com/studio)

### Expo Account
1. Create a free account at [expo.dev](https://expo.dev)
2. Login via CLI: `eas login`

## Method 1: Build APK using Expo (Recommended)

### Step 1: Setup Project
```bash
# Navigate to project directory
cd "c:\xampp\htdocs\mobile app clickbasket\ClickBasketMobile"

# Install dependencies
npm install

# Initialize EAS
eas build:configure
```

### Step 2: Configure API URL
Edit `src/config/api.js` and update the BASE_URL:
```javascript
export const API_CONFIG = {
  // Replace with your actual cPanel domain
  BASE_URL: 'https://yourdomain.com/clickbasket/api',
  // ...
};
```

### Step 3: Build APK
```bash
# Build APK for Android
eas build --platform android --profile preview

# Or build both APK and AAB
eas build --platform android
```

### Step 4: Download APK
1. Wait for build to complete (5-15 minutes)
2. Download APK from the provided link
3. Install on Android device

## Method 2: Local Build with Expo

### Step 1: Install Expo Development Build
```bash
# Install development build tools
npx expo install expo-dev-client

# Create development build
eas build --profile development --platform android
```

### Step 2: Run Locally
```bash
# Start development server
npx expo start --dev-client

# Scan QR code with Expo Go app or development build
```

## Method 3: Build with Android Studio

### Step 1: Eject to React Native CLI
```bash
# Eject from Expo (WARNING: This is irreversible)
npx expo eject

# Install React Native CLI
npm install -g @react-native-community/cli
```

### Step 2: Setup Android Environment
1. Install Android Studio
2. Setup Android SDK (API level 30+)
3. Create virtual device or connect physical device

### Step 3: Build APK
```bash
# Navigate to android directory
cd android

# Build debug APK
./gradlew assembleDebug

# Build release APK
./gradlew assembleRelease

# APK location: android/app/build/outputs/apk/
```

## Configuration Files

### EAS Build Configuration (`eas.json`)
```json
{
  "cli": {
    "version": ">= 3.0.0"
  },
  "build": {
    "development": {
      "developmentClient": true,
      "distribution": "internal"
    },
    "preview": {
      "android": {
        "buildType": "apk"
      }
    },
    "production": {
      "android": {
        "buildType": "aab"
      }
    }
  }
}
```

### App Configuration Updates

#### Update `app.json` for production:
```json
{
  "expo": {
    "name": "ClickBasket",
    "slug": "clickbasket-mobile",
    "version": "1.0.0",
    "android": {
      "package": "com.clickbasket.mobile",
      "versionCode": 1,
      "permissions": [
        "INTERNET",
        "CAMERA",
        "READ_EXTERNAL_STORAGE"
      ]
    }
  }
}
```

## Testing the APK

### Before Building
1. **Test API Connection**: Update API URL in `src/config/api.js`
2. **Test Authentication**: Ensure login/register works
3. **Test Core Features**: Products, cart, orders
4. **Test on Device**: Use Expo Go for testing

### After Building
1. **Install APK**: Enable "Unknown Sources" on Android
2. **Test Offline Behavior**: Check app behavior without internet
3. **Test Performance**: Monitor app performance and memory usage
4. **Test on Multiple Devices**: Different screen sizes and Android versions

## Troubleshooting

### Common Issues

#### Build Fails
```bash
# Clear cache and retry
expo r -c
npm install
eas build --platform android --clear-cache
```

#### API Connection Issues
- Ensure your PHP backend is accessible via HTTPS
- Check CORS headers are properly configured
- Verify API endpoints return valid JSON

#### App Crashes
- Check logs: `adb logcat` (Android Studio required)
- Test in development mode first
- Verify all required permissions are granted

### Performance Optimization

#### Reduce APK Size
```bash
# Enable Hermes engine (add to app.json)
"expo": {
  "jsEngine": "hermes"
}

# Use production build
eas build --platform android --profile production
```

#### Optimize Images
- Compress images before including in app
- Use appropriate image formats (WebP for Android)
- Implement lazy loading for product images

## Publishing to Google Play Store

### Step 1: Create Google Play Console Account
1. Sign up at [Google Play Console](https://play.google.com/console)
2. Pay one-time $25 registration fee

### Step 2: Prepare App Bundle
```bash
# Build AAB (Android App Bundle) for Play Store
eas build --platform android --profile production
```

### Step 3: Upload to Play Store
1. Create new app in Play Console
2. Upload AAB file
3. Fill app details, screenshots, descriptions
4. Submit for review

## Maintenance and Updates

### Update App Version
1. Update version in `app.json`
2. Update `versionCode` for Android
3. Build new APK/AAB
4. Test thoroughly before release

### Backend Updates
- Ensure API backward compatibility
- Test app with new backend changes
- Update API documentation if needed

## Security Considerations

### Production Checklist
- [ ] Use HTTPS for all API calls
- [ ] Implement proper token refresh mechanism
- [ ] Validate all user inputs
- [ ] Enable ProGuard for code obfuscation
- [ ] Remove debug logs and console statements
- [ ] Test with security scanning tools

### API Security
- [ ] Implement rate limiting on backend
- [ ] Use secure authentication tokens
- [ ] Validate all API requests server-side
- [ ] Enable CORS only for your domain

## Support and Resources

### Documentation
- [Expo Documentation](https://docs.expo.dev/)
- [React Native Documentation](https://reactnative.dev/)
- [EAS Build Documentation](https://docs.expo.dev/build/introduction/)

### Community
- [Expo Discord](https://discord.gg/4gtbPAdpaE)
- [React Native Community](https://reactnative.dev/community/overview)

---

**Your ClickBasket mobile app is now ready to be built into an APK!**

Choose the method that best fits your needs:
- **Method 1** (Expo) - Easiest, cloud-based building
- **Method 2** (Local Expo) - Good for development and testing
- **Method 3** (Android Studio) - Full control, more complex setup
