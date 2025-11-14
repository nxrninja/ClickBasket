// Import API Configuration
import { API_CONFIG } from './apiConfig';
export { API_CONFIG };

// API Helper class
class ApiService {
  constructor() {
    this.baseURL = API_CONFIG.BASE_URL;
    this.timeout = API_CONFIG.TIMEOUT;
  }

  async request(endpoint, options = {}) {
    const url = `${this.baseURL}${endpoint}`;
    
    console.log('🌐 API Request:', url);
    
    const config = {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        ...options.headers,
      },
      ...options,
    };

    // Add authorization header if token exists
    const token = await this.getToken();
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }

    console.log('📤 Request config:', JSON.stringify(config, null, 2));

    try {
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), this.timeout);

      const response = await fetch(url, {
        ...config,
        signal: controller.signal,
      });

      clearTimeout(timeoutId);
      
      console.log('📥 Response status:', response.status);

      const data = await response.json();
      console.log('📥 Response data:', JSON.stringify(data, null, 2));

      if (!response.ok) {
        throw new Error(data.message || `HTTP error! status: ${response.status}`);
      }

      return data;
    } catch (error) {
      console.error('❌ API Error:', error);
      if (error.name === 'AbortError') {
        throw new Error('Request timeout - Check if XAMPP is running and accessible');
      }
      if (error.message.includes('Network request failed')) {
        throw new Error('Network request failed - Check your IP address and XAMPP server');
      }
      throw error;
    }
  }

  async get(endpoint, params = {}) {
    const queryString = new URLSearchParams(params).toString();
    const url = queryString ? `${endpoint}?${queryString}` : endpoint;
    return this.request(url);
  }

  async post(endpoint, data = {}) {
    return this.request(endpoint, {
      method: 'POST',
      body: JSON.stringify(data),
    });
  }

  async put(endpoint, data = {}) {
    return this.request(endpoint, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
  }

  async delete(endpoint) {
    return this.request(endpoint, {
      method: 'DELETE',
    });
  }

  // Token management
  async getToken() {
    try {
      const { getItemAsync } = await import('expo-secure-store');
      return await getItemAsync('userToken');
    } catch (error) {
      console.error('Error getting token:', error);
      return null;
    }
  }

  async setToken(token) {
    try {
      const { setItemAsync } = await import('expo-secure-store');
      await setItemAsync('userToken', token);
    } catch (error) {
      console.error('Error setting token:', error);
    }
  }

  async removeToken() {
    try {
      const { deleteItemAsync } = await import('expo-secure-store');
      await deleteItemAsync('userToken');
    } catch (error) {
      console.error('Error removing token:', error);
    }
  }
}

export const apiService = new ApiService();
