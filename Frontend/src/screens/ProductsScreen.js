import React, { useState, useEffect } from 'react';
import {
  View,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  Image,
  RefreshControl,
} from 'react-native';
import {
  Text,
  Card,
  Searchbar,
  Button,
  Menu,
  Divider,
  ActivityIndicator,
  Chip,
} from 'react-native-paper';
import { Ionicons } from '@expo/vector-icons';
import { apiService, API_CONFIG } from '../config/api';
import { theme } from '../theme/theme';

export default function ProductsScreen({ navigation, route }) {
  const [products, setProducts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [searchQuery, setSearchQuery] = useState(route.params?.search || '');
  const [selectedCategory, setSelectedCategory] = useState(route.params?.categoryId || 0);
  const [pagination, setPagination] = useState({});
  const [page, setPage] = useState(1);
  const [menuVisible, setMenuVisible] = useState(false);

  useEffect(() => {
    loadCategories();
  }, []);

  useEffect(() => {
    loadProducts(1);
  }, [selectedCategory, searchQuery]);

  const loadCategories = async () => {
    try {
      const response = await apiService.get(API_CONFIG.ENDPOINTS.PRODUCTS, {
        action: 'categories',
      });

      if (response.success) {
        setCategories([{ id: 0, name: 'All Categories' }, ...response.categories]);
      }
    } catch (error) {
      console.error('Failed to load categories:', error);
    }
  };

  const loadProducts = async (pageNum = 1, append = false) => {
    try {
      setLoading(!append);
      const params = {
        action: 'list',
        page: pageNum,
        limit: 12,
      };

      if (selectedCategory > 0) {
        params.category_id = selectedCategory;
      }

      if (searchQuery.trim()) {
        params.search = searchQuery.trim();
      }

      const response = await apiService.get(API_CONFIG.ENDPOINTS.PRODUCTS, params);

      if (response.success) {
        if (append) {
          setProducts(prev => [...prev, ...response.products]);
        } else {
          setProducts(response.products);
        }
        setPagination(response.pagination);
        setPage(pageNum);
      }
    } catch (error) {
      console.error('Failed to load products:', error);
    } finally {
      setLoading(false);
    }
  };

  const onRefresh = async () => {
    setRefreshing(true);
    await loadProducts(1);
    setRefreshing(false);
  };

  const loadMore = () => {
    if (pagination.current_page < pagination.total_pages && !loading) {
      loadProducts(page + 1, true);
    }
  };

  const handleSearch = (query) => {
    setSearchQuery(query);
  };

  const handleCategorySelect = (categoryId) => {
    setSelectedCategory(categoryId);
    setMenuVisible(false);
  };

  const renderProduct = ({ item }) => (
    <TouchableOpacity
      style={styles.productItem}
      onPress={() => navigation.navigate('ProductDetail', { productId: item.id })}
    >
      <Card style={styles.productCard}>
        <Image
          source={{ uri: item.image_urls?.[0] || 'https://via.placeholder.com/200' }}
          style={styles.productImage}
          resizeMode="cover"
        />
        <Card.Content style={styles.productContent}>
          <Text numberOfLines={2} style={styles.productTitle}>
            {item.title}
          </Text>
          <Text style={styles.categoryText}>{item.category_name}</Text>
          <View style={styles.priceRatingRow}>
            <Text style={styles.productPrice}>₹{item.price}</Text>
            <View style={styles.ratingContainer}>
              <Ionicons name="star" size={14} color="#FFD700" />
              <Text style={styles.ratingText}>
                {item.avg_rating || 0} ({item.rating_count || 0})
              </Text>
            </View>
          </View>
        </Card.Content>
      </Card>
    </TouchableOpacity>
  );

  const renderFooter = () => {
    if (!loading || page === 1) return null;
    return (
      <View style={styles.loadingFooter}>
        <ActivityIndicator size="small" color={theme.colors.primary} />
      </View>
    );
  };

  const selectedCategoryName = categories.find(cat => cat.id === selectedCategory)?.name || 'All Categories';

  return (
    <View style={styles.container}>
      {/* Search and Filter Header */}
      <View style={styles.header}>
        <Searchbar
          placeholder="Search products..."
          onChangeText={handleSearch}
          value={searchQuery}
          style={styles.searchBar}
        />
        
        <View style={styles.filterRow}>
          <Menu
            visible={menuVisible}
            onDismiss={() => setMenuVisible(false)}
            anchor={
              <Button
                mode="outlined"
                onPress={() => setMenuVisible(true)}
                icon="chevron-down"
                contentStyle={styles.filterButton}
              >
                {selectedCategoryName}
              </Button>
            }
          >
            {categories.map((category) => (
              <Menu.Item
                key={category.id}
                onPress={() => handleCategorySelect(category.id)}
                title={category.name}
              />
            ))}
          </Menu>
        </View>
      </View>

      {/* Products List */}
      {loading && page === 1 ? (
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color={theme.colors.primary} />
          <Text style={styles.loadingText}>Loading products...</Text>
        </View>
      ) : (
        <FlatList
          data={products}
          renderItem={renderProduct}
          keyExtractor={(item) => item.id.toString()}
          numColumns={2}
          contentContainerStyle={styles.productsList}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
          }
          onEndReached={loadMore}
          onEndReachedThreshold={0.1}
          ListFooterComponent={renderFooter}
          ListEmptyComponent={
            !loading && (
              <View style={styles.emptyContainer}>
                <Ionicons name="search-outline" size={64} color={theme.colors.placeholder} />
                <Text style={styles.emptyText}>No products found</Text>
                <Text style={styles.emptySubtext}>
                  Try adjusting your search or filters
                </Text>
              </View>
            )
          }
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: theme.colors.background,
  },
  header: {
    padding: theme.spacing.md,
    backgroundColor: theme.colors.surface,
    elevation: 2,
  },
  searchBar: {
    marginBottom: theme.spacing.md,
  },
  filterRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  filterButton: {
    flexDirection: 'row-reverse',
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
  productsList: {
    padding: theme.spacing.sm,
  },
  productItem: {
    flex: 1,
    margin: theme.spacing.sm,
  },
  productCard: {
    elevation: 2,
  },
  productImage: {
    width: '100%',
    height: 150,
  },
  productContent: {
    padding: theme.spacing.md,
  },
  productTitle: {
    fontSize: 14,
    fontWeight: '500',
    marginBottom: theme.spacing.xs,
    minHeight: 35,
  },
  categoryText: {
    fontSize: 12,
    color: theme.colors.placeholder,
    marginBottom: theme.spacing.sm,
  },
  priceRatingRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  productPrice: {
    fontSize: 16,
    fontWeight: 'bold',
    color: theme.colors.primary,
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
  loadingFooter: {
    padding: theme.spacing.md,
    alignItems: 'center',
  },
  emptyContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    paddingTop: theme.spacing.xl * 2,
  },
  emptyText: {
    fontSize: 18,
    fontWeight: '500',
    color: theme.colors.text,
    marginTop: theme.spacing.md,
  },
  emptySubtext: {
    fontSize: 14,
    color: theme.colors.placeholder,
    marginTop: theme.spacing.sm,
    textAlign: 'center',
  },
});
