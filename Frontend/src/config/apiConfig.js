// Multiple API Configuration Options for Different Testing Scenarios
import { Platform } from 'react-native';

// Get your computer's IP address for network testing
const getLocalIP = () => {
  // You can update this with your actual IP address
  return '192.168.0.104';
};

// Configuration for different environments
const API_CONFIGS = {
  // For Expo Go on the same computer (simulator/emulator)
  LOCAL: {
    BASE_URL: 'http://pali.c0m.in/ClickBasket/api',
    DESCRIPTION: 'Use this for Expo Go on the same computer'
  },
  
  // For physical device on the same network
  NETWORK: {
    BASE_URL: `http://pali.c0m.in/ClickBasket/api`,
    DESCRIPTION: 'Use this for physical device on same WiFi network'
  },
  
  // For tunnel testing (when network doesn't work)
  TUNNEL: {
    BASE_URL: 'http://pali.c0m.in/ClickBasket/api',
    DESCRIPTION: 'Use this with ngrok tunnel for external access'
  },
  
  // For production (cPanel hosted)
  PRODUCTION: {
    BASE_URL: 'https://pali.c0m.in/mobile_app/Mobile_app_Clickbasket/api',
    DESCRIPTION: 'Production server on cPanel',
    DATABASE: {
      HOST: 'sql101.cpanelfree.com',
      NAME: 'cpfr_40391125_mobile_clickbasket',
      USER: 'cpfr_40391125',
      PASSWORD: 'Mm47a7Tjp6' // Make sure this is your MySQL password
    }
  }
};

// Choose the configuration based on your testing scenario
// Change this to switch between different configurations
const CURRENT_CONFIG = 'PRODUCTION'; // Options: 'LOCAL', 'NETWORK', 'TUNNEL', 'PRODUCTION'

export const API_CONFIG = {
  BASE_URL: API_CONFIGS[CURRENT_CONFIG].BASE_URL,
  
  ENDPOINTS: {
    AUTH: '/auth.php',
    PRODUCTS: '/products.php',
    CART: '/cart.php',
    ORDERS: '/orders.php',
  },
  
  TIMEOUT: 15000, // Increased timeout for better reliability
};

// Log current configuration for debugging
console.log(`🔧 Using ${CURRENT_CONFIG} API Config:`, API_CONFIG.BASE_URL);
console.log(`📝 Description: ${API_CONFIGS[CURRENT_CONFIG].DESCRIPTION}`);

export { API_CONFIGS, CURRENT_CONFIG };
