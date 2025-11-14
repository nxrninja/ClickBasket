import React, { useState, useEffect } from 'react';
import {
  View,
  StyleSheet,
  ScrollView,
  RefreshControl,
  FlatList,
  TouchableOpacity,
  Image,
} from 'react-native';
import {
  Text,
  Card,
  Title,
  Paragraph,
  Button,
  Searchbar,
  Chip,
} from 'react-native-paper';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { apiService, API_CONFIG } from '../config/api';
import { useAuth } from '../context/AuthContext';
import { useCart } from '../context/CartContext';
import { theme } from '../theme/theme';

export default function HomeScreen({ navigation }) {
  const [featuredProducts, setFeaturedProducts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [searchQuery, setSearchQuery] = useState('');
  const [loading, setLoading] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const { user } = useAuth();
  const { cartCount } = useCart();

  useEffect(() => {
    loadHomeData();
  }, []);

  const loadHomeData = async () => {
    try {
      setLoading(true);
      const [featuredResponse, categoriesResponse] = await Promise.all([
        apiService.get(API_CONFIG.ENDPOINTS.PRODUCTS, {
          action: 'featured',
          limit: 6,
        }),
        apiService.get(API_CONFIG.ENDPOINTS.PRODUCTS, {
          action: 'categories',
        }),
      ]);

      if (featuredResponse.success) {
        setFeaturedProducts(featuredResponse.products || []);
      }

      if (categoriesResponse.success) {
        setCategories(categoriesResponse.categories || []);
      }
    } catch (error) {
      console.error('Failed to load home data:', error);
    } finally {
      setLoading(false);
    }
  };

  const onRefresh = async () => {
    setRefreshing(true);
    await loadHomeData();
    setRefreshing(false);
  };

  const handleSearch = () => {
    if (searchQuery.trim()) {
      navigation.navigate('Products', { search: searchQuery.trim() });
    }
  };

  const handleCategoryPress = (category) => {
    navigation.navigate('Products', { categoryId: category.id, categoryName: category.name });
  };

  const renderFeaturedProduct = ({ item }) => (
    <TouchableOpacity
      style={styles.featuredProduct}
      onPress={() => navigation.navigate('ProductDetail', { productId: item.id })}
    >
      <Card style={styles.productCard}>
        <Image
          source={{ uri: item.image_urls?.[0] || 'https://via.placeholder.com/150' }}
          style={styles.productImage}
          resizeMode="cover"
        />
        <Card.Content style={styles.productContent}>
          <Text numberOfLines={2} style={styles.productTitle}>
            {item.title}
          </Text>
          <Text style={styles.productPrice}>₹{item.price}</Text>
          <View style={styles.ratingContainer}>
            <Ionicons name="star" size={14} color="#FFD700" />
            <Text style={styles.ratingText}>
              {item.avg_rating || 0} ({item.rating_count || 0})
            </Text>
          </View>
        </Card.Content>
      </Card>
    </TouchableOpacity>
  );

  const renderCategory = ({ item }) => (
    <TouchableOpacity
      style={styles.categoryItem}
      onPress={() => handleCategoryPress(item)}
    >
      <Card style={styles.categoryCard}>
        <Card.Content style={styles.categoryContent}>
          <Ionicons name="grid-outline" size={24} color={theme.colors.primary} />
          <Text style={styles.categoryName}>{item.name}</Text>
        </Card.Content>
      </Card>
    </TouchableOpacity>
  );

  return (
    <ScrollView
      style={styles.container}
      refreshControl={
        <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
      }
    >
      {/* Header */}
      <LinearGradient
        colors={[theme.colors.primary, theme.colors.secondary]}
        style={styles.header}
      >
        <View style={styles.headerContent}>
          <View>
            <Text style={styles.welcomeText}>Welcome back,</Text>
            <Title style={styles.userName}>{user?.first_name || 'User'}!</Title>
          </View>
          <TouchableOpacity
            style={styles.cartButton}
            onPress={() => navigation.navigate('Cart')}
          >
            <Ionicons name="cart-outline" size={24} color="#fff" />
            {cartCount > 0 && (
              <View style={styles.cartBadge}>
                <Text style={styles.cartBadgeText}>{cartCount}</Text>
              </View>
            )}
          </TouchableOpacity>
        </View>

        {/* Search Bar */}
        <Searchbar
          placeholder="Search products..."
          onChangeText={setSearchQuery}
          value={searchQuery}
          onSubmitEditing={handleSearch}
          style={styles.searchBar}
          iconColor={theme.colors.primary}
        />
      </LinearGradient>

      {/* Categories */}
      <View style={styles.section}>
        <Title style={styles.sectionTitle}>Categories</Title>
        <FlatList
          data={categories}
          renderItem={renderCategory}
          keyExtractor={(item) => item.id.toString()}
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={styles.categoriesList}
        />
      </View>

      {/* Featured Products */}
      <View style={styles.section}>
        <View style={styles.sectionHeader}>
          <Title style={styles.sectionTitle}>Featured Products</Title>
          <Button
            mode="text"
            onPress={() => navigation.navigate('Products')}
            compact
          >
            View All
          </Button>
        </View>
        <FlatList
          data={featuredProducts}
          renderItem={renderFeaturedProduct}
          keyExtractor={(item) => item.id.toString()}
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={styles.productsList}
        />
      </View>

      {/* Quick Actions */}
      <View style={styles.section}>
        <Title style={styles.sectionTitle}>Quick Actions</Title>
        <View style={styles.quickActions}>
          <TouchableOpacity
            style={styles.quickAction}
            onPress={() => navigation.navigate('Orders')}
          >
            <Ionicons name="receipt-outline" size={32} color={theme.colors.primary} />
            <Text style={styles.quickActionText}>My Orders</Text>
          </TouchableOpacity>
          <TouchableOpacity
            style={styles.quickAction}
            onPress={() => navigation.navigate('Products')}
          >
            <Ionicons name="grid-outline" size={32} color={theme.colors.primary} />
            <Text style={styles.quickActionText}>All Products</Text>
          </TouchableOpacity>
          <TouchableOpacity
            style={styles.quickAction}
            onPress={() => navigation.navigate('Profile')}
          >
            <Ionicons name="person-outline" size={32} color={theme.colors.primary} />
            <Text style={styles.quickActionText}>Profile</Text>
          </TouchableOpacity>
        </View>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: theme.colors.background,
  },
  header: {
    padding: theme.spacing.md,
    paddingTop: theme.spacing.xl,
  },
  headerContent: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: theme.spacing.md,
  },
  welcomeText: {
    color: '#fff',
    opacity: 0.9,
  },
  userName: {
    color: '#fff',
    fontWeight: 'bold',
  },
  cartButton: {
    position: 'relative',
  },
  cartBadge: {
    position: 'absolute',
    top: -8,
    right: -8,
    backgroundColor: theme.colors.error,
    borderRadius: 10,
    minWidth: 20,
    height: 20,
    justifyContent: 'center',
    alignItems: 'center',
  },
  cartBadgeText: {
    color: '#fff',
    fontSize: 12,
    fontWeight: 'bold',
  },
  searchBar: {
    backgroundColor: '#fff',
    elevation: 2,
  },
  section: {
    padding: theme.spacing.md,
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: theme.spacing.md,
  },
  sectionTitle: {
    color: theme.colors.text,
    marginBottom: theme.spacing.md,
  },
  categoriesList: {
    paddingRight: theme.spacing.md,
  },
  categoryItem: {
    marginRight: theme.spacing.md,
  },
  categoryCard: {
    width: 100,
    elevation: 2,
  },
  categoryContent: {
    alignItems: 'center',
    padding: theme.spacing.md,
  },
  categoryName: {
    marginTop: theme.spacing.sm,
    textAlign: 'center',
    fontSize: 12,
  },
  productsList: {
    paddingRight: theme.spacing.md,
  },
  featuredProduct: {
    marginRight: theme.spacing.md,
  },
  productCard: {
    width: 150,
    elevation: 2,
  },
  productImage: {
    width: '100%',
    height: 120,
  },
  productContent: {
    padding: theme.spacing.sm,
  },
  productTitle: {
    fontSize: 14,
    fontWeight: '500',
    marginBottom: theme.spacing.xs,
  },
  productPrice: {
    fontSize: 16,
    fontWeight: 'bold',
    color: theme.colors.primary,
    marginBottom: theme.spacing.xs,
  },
  ratingContainer: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  ratingText: {
    marginLeft: theme.spacing.xs,
    fontSize: 12,
    color: theme.colors.placeholder,
  },
  quickActions: {
    flexDirection: 'row',
    justifyContent: 'space-around',
  },
  quickAction: {
    alignItems: 'center',
    padding: theme.spacing.md,
  },
  quickActionText: {
    marginTop: theme.spacing.sm,
    fontSize: 12,
    textAlign: 'center',
  },
});
