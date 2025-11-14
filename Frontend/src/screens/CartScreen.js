import React, { useState, useEffect } from 'react';
import {
  View,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  Image,
  Alert,
} from 'react-native';
import {
  Text,
  Card,
  Button,
  IconButton,
  Divider,
  Title,
  ActivityIndicator,
} from 'react-native-paper';
import { Ionicons } from '@expo/vector-icons';
import { useCart } from '../context/CartContext';
import { theme } from '../theme/theme';

export default function CartScreen({ navigation }) {
  const {
    cartItems,
    loading,
    updateCartItem,
    removeFromCart,
    clearCart,
    getCartTotal,
    fetchCart,
  } = useCart();

  useEffect(() => {
    fetchCart();
  }, []);

  const handleQuantityChange = async (productId, currentQuantity, change) => {
    const newQuantity = currentQuantity + change;
    if (newQuantity <= 0) {
      handleRemoveItem(productId);
    } else {
      await updateCartItem(productId, newQuantity);
    }
  };

  const handleRemoveItem = (productId) => {
    Alert.alert(
      'Remove Item',
      'Are you sure you want to remove this item from your cart?',
      [
        { text: 'Cancel', style: 'cancel' },
        { text: 'Remove', style: 'destructive', onPress: () => removeFromCart(productId) },
      ]
    );
  };

  const handleClearCart = () => {
    Alert.alert(
      'Clear Cart',
      'Are you sure you want to remove all items from your cart?',
      [
        { text: 'Cancel', style: 'cancel' },
        { text: 'Clear All', style: 'destructive', onPress: clearCart },
      ]
    );
  };

  const handleCheckout = () => {
    if (cartItems.length === 0) return;
    navigation.navigate('Checkout');
  };

  const renderCartItem = ({ item }) => (
    <Card style={styles.cartItem}>
      <View style={styles.itemContent}>
        <Image
          source={{ uri: item.image || 'https://via.placeholder.com/80' }}
          style={styles.itemImage}
          resizeMode="cover"
        />
        
        <View style={styles.itemDetails}>
          <Text numberOfLines={2} style={styles.itemTitle}>
            {item.title}
          </Text>
          <Text style={styles.itemPrice}>₹{item.price}</Text>
          
          <View style={styles.quantityContainer}>
            <IconButton
              icon="minus"
              size={20}
              onPress={() => handleQuantityChange(item.product_id, item.quantity, -1)}
              style={styles.quantityButton}
            />
            <Text style={styles.quantityText}>{item.quantity}</Text>
            <IconButton
              icon="plus"
              size={20}
              onPress={() => handleQuantityChange(item.product_id, item.quantity, 1)}
              style={styles.quantityButton}
            />
          </View>
        </View>
        
        <View style={styles.itemActions}>
          <Text style={styles.itemTotal}>₹{item.total}</Text>
          <IconButton
            icon="delete"
            size={20}
            onPress={() => handleRemoveItem(item.product_id)}
            iconColor={theme.colors.error}
          />
        </View>
      </View>
    </Card>
  );

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color={theme.colors.primary} />
        <Text style={styles.loadingText}>Loading cart...</Text>
      </View>
    );
  }

  if (cartItems.length === 0) {
    return (
      <View style={styles.emptyContainer}>
        <Ionicons name="cart-outline" size={80} color={theme.colors.placeholder} />
        <Title style={styles.emptyTitle}>Your cart is empty</Title>
        <Text style={styles.emptyText}>
          Add some products to get started
        </Text>
        <Button
          mode="contained"
          onPress={() => navigation.navigate('Products')}
          style={styles.shopButton}
        >
          Start Shopping
        </Button>
      </View>
    );
  }

  const subtotal = getCartTotal();
  const shipping = 0; // Free shipping
  const total = subtotal + shipping;

  return (
    <View style={styles.container}>
      {/* Cart Header */}
      <View style={styles.header}>
        <Title>Shopping Cart ({cartItems.length} items)</Title>
        <Button
          mode="text"
          onPress={handleClearCart}
          textColor={theme.colors.error}
          compact
        >
          Clear All
        </Button>
      </View>

      {/* Cart Items */}
      <FlatList
        data={cartItems}
        renderItem={renderCartItem}
        keyExtractor={(item) => item.product_id.toString()}
        contentContainerStyle={styles.cartList}
        showsVerticalScrollIndicator={false}
      />

      {/* Cart Summary */}
      <Card style={styles.summaryCard}>
        <Card.Content>
          <View style={styles.summaryRow}>
            <Text style={styles.summaryLabel}>Subtotal:</Text>
            <Text style={styles.summaryValue}>₹{subtotal.toFixed(2)}</Text>
          </View>
          <View style={styles.summaryRow}>
            <Text style={styles.summaryLabel}>Shipping:</Text>
            <Text style={styles.summaryValue}>
              {shipping === 0 ? 'Free' : `₹${shipping.toFixed(2)}`}
            </Text>
          </View>
          <Divider style={styles.divider} />
          <View style={styles.summaryRow}>
            <Text style={styles.totalLabel}>Total:</Text>
            <Text style={styles.totalValue}>₹{total.toFixed(2)}</Text>
          </View>
          
          <Button
            mode="contained"
            onPress={handleCheckout}
            style={styles.checkoutButton}
            contentStyle={styles.checkoutButtonContent}
          >
            Proceed to Checkout
          </Button>
        </Card.Content>
      </Card>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: theme.colors.background,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    marginTop: theme.spacing.md,
    color: theme.colors.placeholder,
  },
  emptyContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: theme.spacing.xl,
  },
  emptyTitle: {
    marginTop: theme.spacing.md,
    textAlign: 'center',
  },
  emptyText: {
    marginTop: theme.spacing.sm,
    textAlign: 'center',
    color: theme.colors.placeholder,
  },
  shopButton: {
    marginTop: theme.spacing.xl,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: theme.spacing.md,
    backgroundColor: theme.colors.surface,
  },
  cartList: {
    padding: theme.spacing.md,
  },
  cartItem: {
    marginBottom: theme.spacing.md,
    elevation: 2,
  },
  itemContent: {
    flexDirection: 'row',
    padding: theme.spacing.md,
  },
  itemImage: {
    width: 80,
    height: 80,
    borderRadius: theme.borderRadius.sm,
  },
  itemDetails: {
    flex: 1,
    marginLeft: theme.spacing.md,
  },
  itemTitle: {
    fontSize: 16,
    fontWeight: '500',
    marginBottom: theme.spacing.xs,
  },
  itemPrice: {
    fontSize: 14,
    color: theme.colors.primary,
    fontWeight: '500',
    marginBottom: theme.spacing.sm,
  },
  quantityContainer: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  quantityButton: {
    margin: 0,
  },
  quantityText: {
    marginHorizontal: theme.spacing.sm,
    fontSize: 16,
    fontWeight: '500',
    minWidth: 30,
    textAlign: 'center',
  },
  itemActions: {
    alignItems: 'flex-end',
    justifyContent: 'space-between',
  },
  itemTotal: {
    fontSize: 16,
    fontWeight: 'bold',
    color: theme.colors.primary,
  },
  summaryCard: {
    margin: theme.spacing.md,
    elevation: 4,
  },
  summaryRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: theme.spacing.sm,
  },
  summaryLabel: {
    fontSize: 16,
    color: theme.colors.text,
  },
  summaryValue: {
    fontSize: 16,
    fontWeight: '500',
  },
  divider: {
    marginVertical: theme.spacing.md,
  },
  totalLabel: {
    fontSize: 18,
    fontWeight: 'bold',
    color: theme.colors.text,
  },
  totalValue: {
    fontSize: 18,
    fontWeight: 'bold',
    color: theme.colors.primary,
  },
  checkoutButton: {
    marginTop: theme.spacing.lg,
  },
  checkoutButtonContent: {
    paddingVertical: theme.spacing.sm,
  },
});
