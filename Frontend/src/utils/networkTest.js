// Network Testing Utility
import { API_CONFIG } from '../config/apiConfig';

export const testNetworkConnection = async () => {
  console.log('🔍 Testing network connection...');
  console.log('🌐 Testing URL:', API_CONFIG.BASE_URL);
  
  try {
    const testUrl = `${API_CONFIG.BASE_URL}/auth.php`;
    console.log('📡 Attempting to reach:', testUrl);
    
    const response = await fetch(testUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        action: 'test'
      }),
      timeout: 5000
    });
    
    console.log('✅ Network test response status:', response.status);
    
    if (response.status === 200 || response.status === 400) {
      console.log('✅ Server is reachable!');
      return { success: true, message: 'Server is reachable' };
    } else {
      console.log('⚠️ Server responded with unexpected status:', response.status);
      return { success: false, message: `Server responded with status: ${response.status}` };
    }
  } catch (error) {
    console.error('❌ Network test failed:', error);
    
    if (error.message.includes('Network request failed')) {
      return { 
        success: false, 
        message: 'Cannot reach server. Check if:\n1. XAMPP is running\n2. Your device is on the same WiFi network\n3. IP address is correct' 
      };
    }
    
    return { success: false, message: error.message };
  }
};

export const getNetworkDiagnostics = () => {
  return {
    currentConfig: API_CONFIG.BASE_URL,
    suggestions: [
      '1. Make sure XAMPP Apache is running',
      '2. Check if your device is on the same WiFi network',
      '3. Verify the IP address in apiConfig.js',
      '4. Try switching to LOCAL config for emulator testing',
      '5. Consider using ngrok tunnel for external access'
    ]
  };
};
