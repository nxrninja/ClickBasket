import React, { createContext, useContext, useEffect, useState } from 'react';
import { apiService, API_CONFIG } from '../config/api';
import Toast from 'react-native-toast-message';

const AuthContext = createContext({});

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    checkAuthStatus();
  }, []);

  const checkAuthStatus = async () => {
    try {
      const token = await apiService.getToken();
      if (token) {
        // Verify token with backend
        const response = await apiService.post(API_CONFIG.ENDPOINTS.AUTH, {
          action: 'verify',
          token: token,
        });

        if (response.success) {
          setUser(response.user);
        } else {
          await apiService.removeToken();
        }
      }
    } catch (error) {
      console.error('Auth check failed:', error);
      await apiService.removeToken();
    } finally {
      setLoading(false);
    }
  };

  const login = async (email, password) => {
    try {
      setLoading(true);
      const response = await apiService.post(API_CONFIG.ENDPOINTS.AUTH, {
        action: 'login',
        email: email.toLowerCase().trim(),
        password,
      });

      if (response.success) {
        await apiService.setToken(response.token);
        setUser(response.user);
        Toast.show({
          type: 'success',
          text1: 'Login Successful',
          text2: 'Welcome back!',
        });
        return { success: true };
      } else {
        throw new Error(response.message);
      }
    } catch (error) {
      const message = error.message || 'Login failed';
      Toast.show({
        type: 'error',
        text1: 'Login Failed',
        text2: message,
      });
      return { success: false, message };
    } finally {
      setLoading(false);
    }
  };

  const register = async (userData) => {
    try {
      setLoading(true);
      const response = await apiService.post(API_CONFIG.ENDPOINTS.AUTH, {
        action: 'register',
        first_name: userData.firstName.trim(),
        last_name: userData.lastName.trim(),
        email: userData.email.toLowerCase().trim(),
        phone: userData.phone.trim(),
        password: userData.password,
      });

      if (response.success) {
        await apiService.setToken(response.token);
        setUser(response.user);
        Toast.show({
          type: 'success',
          text1: 'Registration Successful',
          text2: 'Welcome to ClickBasket!',
        });
        return { success: true };
      } else {
        throw new Error(response.message);
      }
    } catch (error) {
      const message = error.message || 'Registration failed';
      Toast.show({
        type: 'error',
        text1: 'Registration Failed',
        text2: message,
      });
      return { success: false, message };
    } finally {
      setLoading(false);
    }
  };

  const logout = async () => {
    try {
      await apiService.removeToken();
      setUser(null);
      Toast.show({
        type: 'success',
        text1: 'Logged Out',
        text2: 'See you soon!',
      });
    } catch (error) {
      console.error('Logout error:', error);
    }
  };

  const updateUser = (userData) => {
    setUser(prevUser => ({ ...prevUser, ...userData }));
  };

  const value = {
    user,
    loading,
    login,
    register,
    logout,
    updateUser,
    checkAuthStatus,
  };

  return (
    <AuthContext.Provider value={value}>
      {children}
    </AuthContext.Provider>
  );
};
