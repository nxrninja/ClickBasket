import React, { useState, useEffect } from 'react';
import {
  View,
  StyleSheet,
  ScrollView,
  Image,
  Dimensions,
} from 'react-native';
import {
  Text,
  Button,
  Card,
  Title,
  Paragraph,
  Chip,
  ActivityIndicator,
} from 'react-native-paper';
import { Ionicons } from '@expo/vector-icons';
import { apiService, API_CONFIG } from '../config/api';
import { useCart } from '../context/CartContext';
import { theme } from '../theme/theme';

const { width } = Dimensions.get('window');

export default function ProductDetailScreen({ route, navigation }) {
  const { productId } = route.params;
  const [product, setProduct] = useState(null);
  const [loading, setLoading] = useState(true);
  const [addingToCart, setAddingToCart] = useState(false);
  const { addToCart } = useCart();

  useEffect(() => {
    loadProduct();
  }, [productId]);

  const loadProduct = async () => {
    try {
      setLoading(true);
      const response = await apiService.get(API_CONFIG.ENDPOINTS.PRODUCTS, {
        action: 'detail',
        id: productId,
      });

      if (response.success) {
        setProduct(response.product);
        navigation.setOptions({ title: response.product.title });
      }
    } catch (error) {
      console.error('Failed to load product:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleAddToCart = async () => {
    setAddingToCart(true);
    await addToCart(productId, 1);
    setAddingToCart(false);
  };

  if (loading) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color={theme.colors.primary} />
        <Text style={styles.loadingText}>Loading product...</Text>
      </View>
    );
  }

  if (!product) {
    return (
      <View style={styles.errorContainer}>
        <Ionicons name="alert-circle-outline" size={64} color={theme.colors.error} />
        <Text style={styles.errorText}>Product not found</Text>
        <Button mode="contained" onPress={() => navigation.goBack()}>
          Go Back
        </Button>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <ScrollView style={styles.scrollView}>
        {/* Product Images */}
        <View style={styles.imageContainer}>
          <Image
            source={{ uri: product.image_urls?.[0] || 'https://via.placeholder.com/400' }}
            style={styles.productImage}
            resizeMode="cover"
          />
        </View>

        {/* Product Info */}
        <Card style={styles.infoCard}>
          <Card.Content>
            <View style={styles.headerRow}>
              <View style={styles.titleContainer}>
                <Title style={styles.productTitle}>{product.title}</Title>
                <Chip mode="outlined" style={styles.categoryChip}>
                  {product.category_name}
                </Chip>
              </View>
              <Text style={styles.productPrice}>₹{product.price}</Text>
            </View>

            {/* Rating */}
            <View style={styles.ratingContainer}>
              <View style={styles.ratingRow}>
                <Ionicons name="star" size={20} color="#FFD700" />
                <Text style={styles.ratingText}>
                  {product.avg_rating || 0} ({product.rating_count || 0} reviews)
                </Text>
              </View>
            </View>

            {/* Description */}
            <View style={styles.descriptionContainer}>
              <Text style={styles.sectionTitle}>Description</Text>
              <Paragraph style={styles.description}>
                {product.description || 'No description available.'}
              </Paragraph>
            </View>

            {/* Reviews */}
            {product.recent_reviews && product.recent_reviews.length > 0 && (
              <View style={styles.reviewsContainer}>
                <Text style={styles.sectionTitle}>Recent Reviews</Text>
                {product.recent_reviews.slice(0, 3).map((review, index) => (
                  <View key={index} style={styles.reviewItem}>
                    <View style={styles.reviewHeader}>
                      <Text style={styles.reviewerName}>{review.user_name}</Text>
                      <View style={styles.reviewRating}>
                        {[...Array(5)].map((_, i) => (
                          <Ionicons
                            key={i}
                            name={i < review.rating ? 'star' : 'star-outline'}
                            size={14}
                            color="#FFD700"
                          />
                        ))}
                      </View>
                    </View>
                    {review.review && (
                      <Text style={styles.reviewText}>{review.review}</Text>
                    )}
                  </View>
                ))}
              </View>
            )}
          </Card.Content>
        </Card>
      </ScrollView>

      {/* Add to Cart Button */}
      <View style={styles.bottomContainer}>
        <Button
          mode="contained"
          onPress={handleAddToCart}
          loading={addingToCart}
          disabled={addingToCart}
          style={styles.addToCartButton}
          contentStyle={styles.addToCartContent}
          icon="cart-plus"
        >
          Add to Cart - ₹{product.price}
        </Button>
      </View>
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
  errorContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: theme.spacing.xl,
  },
  errorText: {
    marginTop: theme.spacing.md,
    marginBottom: theme.spacing.xl,
    fontSize: 18,
    textAlign: 'center',
  },
  scrollView: {
    flex: 1,
  },
  imageContainer: {
    backgroundColor: '#fff',
  },
  productImage: {
    width: width,
    height: width * 0.8,
  },
  infoCard: {
    margin: theme.spacing.md,
    elevation: 4,
  },
  headerRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: theme.spacing.md,
  },
  titleContainer: {
    flex: 1,
    marginRight: theme.spacing.md,
  },
  productTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    marginBottom: theme.spacing.sm,
  },
  categoryChip: {
    alignSelf: 'flex-start',
  },
  productPrice: {
    fontSize: 24,
    fontWeight: 'bold',
    color: theme.colors.primary,
  },
  ratingContainer: {
    marginBottom: theme.spacing.lg,
  },
  ratingRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  ratingText: {
    marginLeft: theme.spacing.sm,
    fontSize: 16,
    color: theme.colors.text,
  },
  descriptionContainer: {
    marginBottom: theme.spacing.lg,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    marginBottom: theme.spacing.md,
    color: theme.colors.text,
  },
  description: {
    fontSize: 16,
    lineHeight: 24,
    color: theme.colors.text,
  },
  reviewsContainer: {
    marginBottom: theme.spacing.lg,
  },
  reviewItem: {
    marginBottom: theme.spacing.md,
    padding: theme.spacing.md,
    backgroundColor: theme.colors.background,
    borderRadius: theme.borderRadius.sm,
  },
  reviewHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: theme.spacing.sm,
  },
  reviewerName: {
    fontWeight: '500',
    color: theme.colors.text,
  },
  reviewRating: {
    flexDirection: 'row',
  },
  reviewText: {
    fontSize: 14,
    color: theme.colors.text,
    lineHeight: 20,
  },
  bottomContainer: {
    padding: theme.spacing.md,
    backgroundColor: theme.colors.surface,
    elevation: 8,
  },
  addToCartButton: {
    borderRadius: theme.borderRadius.lg,
  },
  addToCartContent: {
    paddingVertical: theme.spacing.md,
  },
});
