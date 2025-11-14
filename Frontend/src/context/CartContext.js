import React, { createContext, useContext, useEffect, useState } from 'react';
import { apiService, API_CONFIG } from '../config/api';
import { useAuth } from './AuthContext';
import Toast from 'react-native-toast-message';

const CartContext = createContext({});

export const useCart = () => {
  const context = useContext(CartContext);
  if (!context) {
    throw new Error('useCart must be used within a CartProvider');
  }
  return context;
};

export const CartProvider = ({ children }) => {
  const [cartItems, setCartItems] = useState([]);
  const [cartCount, setCartCount] = useState(0);
  const [loading, setLoading] = useState(false);
  const { user } = useAuth();

  useEffect(() => {
    if (user) {
      fetchCart();
      fetchCartCount();
    } else {
      setCartItems([]);
      setCartCount(0);
    }
  }, [user]);

  const fetchCart = async () => {
    try {
      setLoading(true);
      const response = await apiService.get(API_CONFIG.ENDPOINTS.CART, {
        action: 'get',
      });

      if (response.success) {
        setCartItems(response.items || []);
        setCartCount(response.count || 0);
      }
    } catch (error) {
      console.error('Failed to fetch cart:', error);
    } finally {
      setLoading(false);
    }
  };

  const fetchCartCount = async () => {
    try {
      const response = await apiService.get(API_CONFIG.ENDPOINTS.CART, {
        action: 'count',
      });

      if (response.success) {
        setCartCount(response.count || 0);
      }
    } catch (error) {
      console.error('Failed to fetch cart count:', error);
    }
  };

  const addToCart = async (productId, quantity = 1) => {
    try {
      const response = await apiService.post(API_CONFIG.ENDPOINTS.CART, {
        action: 'add',
        product_id: productId,
        quantity,
      });

      if (response.success) {
        await fetchCart();
        await fetchCartCount();
        Toast.show({
          type: 'success',
          text1: 'Added to Cart',
          text2: response.message,
        });
        return { success: true };
      } else {
        throw new Error(response.message);
      }
    } catch (error) {
      const message = error.message || 'Failed to add to cart';
      Toast.show({
        type: 'error',
        text1: 'Error',
        text2: message,
      });
      return { success: false, message };
    }
  };

  const updateCartItem = async (productId, quantity) => {
    try {
      const response = await apiService.post(API_CONFIG.ENDPOINTS.CART, {
        action: 'update',
        product_id: productId,
        quantity,
      });

      if (response.success) {
        await fetchCart();
        await fetchCartCount();
        return { success: true };
      } else {
        throw new Error(response.message);
      }
    } catch (error) {
      const message = error.message || 'Failed to update cart';
      Toast.show({
        type: 'error',
        text1: 'Error',
        text2: message,
      });
      return { success: false, message };
    }
  };

  const removeFromCart = async (productId) => {
    try {
      const response = await apiService.post(API_CONFIG.ENDPOINTS.CART, {
        action: 'remove',
        product_id: productId,
      });

      if (response.success) {
        await fetchCart();
        await fetchCartCount();
        Toast.show({
          type: 'success',
          text1: 'Removed from Cart',
          text2: response.message,
        });
        return { success: true };
      } else {
        throw new Error(response.message);
      }
    } catch (error) {
      const message = error.message || 'Failed to remove from cart';
      Toast.show({
        type: 'error',
        text1: 'Error',
        text2: message,
      });
      return { success: false, message };
    }
  };

  const clearCart = async () => {
    try {
      const response = await apiService.post(API_CONFIG.ENDPOINTS.CART, {
        action: 'clear',
      });

      if (response.success) {
        setCartItems([]);
        setCartCount(0);
        Toast.show({
          type: 'success',
          text1: 'Cart Cleared',
          text2: response.message,
        });
        return { success: true };
      } else {
        throw new Error(response.message);
      }
    } catch (error) {
      const message = error.message || 'Failed to clear cart';
      Toast.show({
        type: 'error',
        text1: 'Error',
        text2: message,
      });
      return { success: false, message };
    }
  };

  const getCartTotal = () => {
    return cartItems.reduce((total, item) => total + (item.price * item.quantity), 0);
  };

  const value = {
    cartItems,
    cartCount,
    loading,
    fetchCart,
    addToCart,
    updateCartItem,
    removeFromCart,
    clearCart,
    getCartTotal,
  };

  return (
    <CartContext.Provider value={value}>
      {children}
    </CartContext.Provider>
  );
};
