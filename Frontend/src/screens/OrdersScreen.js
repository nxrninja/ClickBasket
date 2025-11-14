import React, { useState, useEffect } from 'react';
import { View, StyleSheet, FlatList } from 'react-native';
import { Text, Card, Chip, ActivityIndicator } from 'react-native-paper';
import { apiService, API_CONFIG } from '../config/api';
import { theme } from '../theme/theme';

export default function OrdersScreen() {
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadOrders();
  }, []);

  const loadOrders = async () => {
    try {
      const response = await apiService.get(API_CONFIG.ENDPOINTS.ORDERS, { action: 'list' });
      if (response.success) {
        setOrders(response.orders || []);
      }
    } catch (error) {
      console.error('Failed to load orders:', error);
    } finally {
      setLoading(false);
    }
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'pending': return theme.colors.warning;
      case 'processing': return theme.colors.info;
      case 'completed': return theme.colors.success;
      case 'cancelled': return theme.colors.error;
      default: return theme.colors.placeholder;
    }
  };

  const renderOrder = ({ item }) => (
    <Card style={styles.orderCard}>
      <Card.Content>
        <View style={styles.orderHeader}>
          <Text style={styles.orderId}>#{item.order_id}</Text>
          <Chip mode="outlined" textStyle={{ color: getStatusColor(item.status) }}>
            {item.status.toUpperCase()}
          </Chip>
        </View>
        <Text style={styles.orderDate}>{new Date(item.created_at).toLocaleDateString()}</Text>
        <Text style={styles.orderTotal}>Total: ₹{item.total_amount}</Text>
        <Text style={styles.itemCount}>{item.items?.length || 0} items</Text>
      </Card.Content>
    </Card>
  );

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <FlatList
        data={orders}
        renderItem={renderOrder}
        keyExtractor={(item) => item.id.toString()}
        contentContainerStyle={styles.ordersList}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: theme.colors.background },
  loadingContainer: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  ordersList: { padding: theme.spacing.md },
  orderCard: { marginBottom: theme.spacing.md },
  orderHeader: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: theme.spacing.sm },
  orderId: { fontSize: 16, fontWeight: 'bold' },
  orderDate: { color: theme.colors.placeholder, marginBottom: theme.spacing.sm },
  orderTotal: { fontSize: 16, fontWeight: 'bold', color: theme.colors.primary },
  itemCount: { color: theme.colors.placeholder },
});
