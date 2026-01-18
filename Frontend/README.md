# ClickBasket Mobile App

A React Native mobile application for the ClickBasket e-commerce platform, built with Expo and connected to a PHP backend.

## Features

- **User Authentication** - Login, register, and secure token-based authentication
- **Product Catalog** - Browse products by category, search, and view details
- **Shopping Cart** - Add, remove, and manage cart items
- **Order Management** - Place orders and track order history
- **User Profile** - Manage account information and settings
- **Responsive Design** - Optimized for various screen sizes

## Tech Stack

- **Frontend**: React Native with Expo
- **UI Library**: React Native Paper
- **Navigation**: React Navigation v6
- **State Management**: React Context API
- **Backend**: PHP with MySQL (existing ClickBasket backend)
- **Authentication**: Token-based with secure storage

## Quick Start

### Prerequisites
- Node.js (v16+)
- Expo CLI: `npm install -g @expo/cli`
- Expo Go app on your mobile device

### Installation
```bash
# Clone or navigate to the project
cd ClickBasketMobile

# Install dependencies
npm install

# Start development server
npx expo start
```

### Configuration
1. Update API URL in `src/config/api.js`:
```javascript
BASE_URL: 'https://yourdomain.com/clickbasket/api'
```

2. Ensure your PHP backend is running and accessible

### Running the App
1. Start the Expo development server: `npx expo start`
2. Scan QR code with Expo Go app (Android) or Camera app (iOS)
3. The app will load on your device

## Building APK

See [BUILD_APK_GUIDE.md](./BUILD_APK_GUIDE.md) for detailed instructions on building the APK file.

### Quick APK Build
```bash
# Install EAS CLI
npm install -g eas-cli

# Login to Expo
eas login

# Configure build
eas build:configure

# Build APK
eas build --platform android --profile preview
```

## Project Structure

```
ClickBasketMobile/
├── src/
│   ├── config/          # API configuration
│   ├── context/         # React Context providers
│   ├── screens/         # App screens/pages
│   ├── theme/           # App theme and styling
│   └── components/      # Reusable components (future)
├── assets/              # Images, icons, fonts
├── App.js              # Main app component
├── package.json        # Dependencies and scripts
└── app.json           # Expo configuration
```

## API Integration

The app connects to your existing PHP backend with these endpoints:

- **Authentication**: `/api/auth.php`
- **Products**: `/api/products.php`
- **Cart**: `/api/cart.php`
- **Orders**: `/api/orders.php`

All API calls include proper error handling and loading states.

## Key Features Implementation

### Authentication Flow
- Secure token storage using Expo SecureStore
- Automatic token validation on app start
- Seamless login/logout experience

### Product Management
- Product listing with pagination
- Category filtering and search
- Product detail views with images and reviews
- Add to cart functionality

### Shopping Cart
- Real-time cart updates
- Quantity management
- Cart persistence across app sessions
- Checkout process

### Order Management
- Order placement with billing information
- Order history and status tracking
- Order details view

## Customization

### Theming
Update colors and styling in `src/theme/theme.js`:
```javascript
export const theme = {
  colors: {
    primary: '#2196F3',    // Your brand color
    secondary: '#03DAC6',  // Secondary color
    // ... other colors
  }
};
```

### API Configuration
Update API settings in `src/config/api.js`:
```javascript
export const API_CONFIG = {
  BASE_URL: 'your-api-url',
  TIMEOUT: 10000,
  // ... other settings
};
```

## Development

### Adding New Screens
1. Create screen component in `src/screens/`
2. Add navigation route in `App.js`
3. Update navigation types if using TypeScript

### Adding New API Endpoints
1. Add endpoint to `src/config/api.js`
2. Create service functions using `apiService`
3. Handle responses in components

### State Management
The app uses React Context for state management:
- `AuthContext` - User authentication state
- `CartContext` - Shopping cart state

## Testing

### Development Testing
```bash
# Start with clear cache
npx expo start -c

# Test on different devices
npx expo start --tunnel  # For testing on external devices
```

### Production Testing
1. Build development APK
2. Install on physical devices
3. Test all core functionality
4. Verify API connectivity

## Deployment

### Development
- Use Expo Go for quick testing
- Share via QR code or Expo link

### Production
1. Build APK using EAS Build
2. Test thoroughly on multiple devices
3. Submit to Google Play Store (optional)

## Troubleshooting

### Common Issues

**API Connection Failed**
- Check API URL in config
- Verify backend is accessible
- Check CORS headers

**Build Errors**
- Clear cache: `npx expo start -c`
- Reinstall dependencies: `rm -rf node_modules && npm install`
- Check Expo CLI version

**App Crashes**
- Check error logs in development
- Verify all required permissions
- Test API responses

### Getting Help
- Check [Expo Documentation](https://docs.expo.dev/)
- Review [React Native Documentation](https://reactnative.dev/)
- Check GitHub issues or create new ones

## Contributing

1. Fork the repository
2. Create feature branch: `git checkout -b feature-name`
3. Commit changes: `git commit -am 'Add feature'`
4. Push to branch: `git push origin feature-name`
5. Submit pull request

## License

This project is part of the ClickBasket e-commerce platform. See the main project license for details.

---

**Happy coding! 🚀**
