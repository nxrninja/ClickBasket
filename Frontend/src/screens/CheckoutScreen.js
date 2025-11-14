import React, { useState } from 'react';
import { View, StyleSheet, ScrollView, Alert } from 'react-native';
import { TextInput, Button, Card, Title, Text, RadioButton } from 'react-native-paper';
import { apiService, API_CONFIG } from '../config/api';
import { useCart } from '../context/CartContext';
import { useAuth } from '../context/AuthContext';
import { theme } from '../theme/theme';

export default function CheckoutScreen({ navigation }) {
  const [billingData, setBillingData] = useState({
    billing_name: '',
    billing_email: '',
    billing_phone: '',
    billing_address: '',
    payment_method: 'cod',
  });
  const [loading, setLoading] = useState(false);
  const { user } = useAuth();
  const { getCartTotal, clearCart } = useCart();

  React.useEffect(() => {
    if (user) {
      setBillingData(prev => ({
        ...prev,
        billing_name: `${user.first_name} ${user.last_name}`,
        billing_email: user.email,
        billing_phone: user.phone || '',
      }));
    }
  }, [user]);

  const handleInputChange = (field, value) => {
    setBillingData(prev => ({ ...prev, [field]: value }));
  };

  const handlePlaceOrder = async () => {
    if (!billingData.billing_name || !billingData.billing_email || !billingData.billing_phone) {
      Alert.alert('Error', 'Please fill in all required fields');
      return;
    }

    try {
      setLoading(true);
      const response = await apiService.post(API_CONFIG.ENDPOINTS.ORDERS, {
        action: 'create',
        ...billingData,
      });

      if (response.success) {
        Alert.alert(
          'Order Placed!',
          `Your order ${response.order_id} has been placed successfully.`,
          [{ text: 'OK', onPress: () => {
            clearCart();
            navigation.navigate('Orders');
          }}]
        );
      }
    } catch (error) {
      Alert.alert('Error', error.message || 'Failed to place order');
    } finally {
      setLoading(false);
    }
  };

  return (
    <ScrollView style={styles.container}>
      <Card style={styles.card}>
        <Card.Content>
          <Title>Billing Information</Title>
          
          <TextInput
            label="Full Name *"
            value={billingData.billing_name}
            onChangeText={(value) => handleInputChange('billing_name', value)}
            mode="outlined"
            style={styles.input}
          />
          
          <TextInput
            label="Email *"
            value={billingData.billing_email}
            onChangeText={(value) => handleInputChange('billing_email', value)}
            mode="outlined"
            keyboardType="email-address"
            style={styles.input}
          />
          
          <TextInput
            label="Phone *"
            value={billingData.billing_phone}
            onChangeText={(value) => handleInputChange('billing_phone', value)}
            mode="outlined"
            keyboardType="phone-pad"
            style={styles.input}
          />
          
          <TextInput
            label="Address"
            value={billingData.billing_address}
            onChangeText={(value) => handleInputChange('billing_address', value)}
            mode="outlined"
            multiline
            numberOfLines={3}
            style={styles.input}
          />
        </Card.Content>
      </Card>

      <Card style={styles.card}>
        <Card.Content>
          <Title>Payment Method</Title>
          <RadioButton.Group
            onValueChange={(value) => handleInputChange('payment_method', value)}
            value={billingData.payment_method}
          >
            <View style={styles.radioItem}>
              <RadioButton value="cod" />
              <Text>Cash on Delivery</Text>
            </View>
          </RadioButton.Group>
        </Card.Content>
      </Card>

      <Card style={styles.card}>
        <Card.Content>
          <Title>Order Summary</Title>
          <View style={styles.summaryRow}>
            <Text>Total Amount:</Text>
            <Text style={styles.totalAmount}>₹{getCartTotal().toFixed(2)}</Text>
          </View>
        </Card.Content>
      </Card>

      <Button
        mode="contained"
        onPress={handlePlaceOrder}
        loading={loading}
        style={styles.placeOrderButton}
      >
        Place Order
      </Button>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, padding: theme.spacing.md },
  card: { marginBottom: theme.spacing.md },
  input: { marginBottom: theme.spacing.md },
  radioItem: { flexDirection: 'row', alignItems: 'center', marginVertical: theme.spacing.sm },
  summaryRow: { flexDirection: 'row', justifyContent: 'space-between' },
  totalAmount: { fontSize: 18, fontWeight: 'bold', color: theme.colors.primary },
  placeOrderButton: { marginVertical: theme.spacing.lg },
});
