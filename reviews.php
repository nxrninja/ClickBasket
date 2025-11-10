<?php
$page_title = 'My Reviews - ClickBasket';
$mobile_title = 'My Reviews';

require_once 'config/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php?redirect=reviews.php');
}

$database = new Database();
$db = $database->getConnection();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get user reviews
try {
    $reviews_query = "SELECT pr.*, p.title as product_title, p.price, p.screenshots
                     FROM product_ratings pr
                     JOIN products p ON pr.product_id = p.id
                     WHERE pr.user_id = ?
                     ORDER BY pr.created_at DESC
                     LIMIT ? OFFSET ?";
    $reviews_stmt = $db->prepare($reviews_query);
    $reviews_stmt->execute([get_current_user_id(), $limit, $offset]);
    $user_reviews = $reviews_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total reviews count
    $count_query = "SELECT COUNT(*) as total FROM product_ratings WHERE user_id = ?";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute([get_current_user_id()]);
    $total_reviews = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_reviews / $limit);
    
} catch (Exception $e) {
    $user_reviews = [];
    $total_reviews = 0;
    $total_pages = 0;
}

include 'includes/header.php';
?>

<div class="container" style="padding: 2rem 0;">
    <!-- Page Header -->
    <div class="d-flex justify-between align-center mb-4">
        <div>
            <h1 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                <i class="fas fa-star"></i>
                My Reviews
            </h1>
            <p style="color: var(--text-secondary); margin: 0;">
                <?php echo $total_reviews; ?> review<?php echo $total_reviews !== 1 ? 's' : ''; ?> written
            </p>
        </div>
        <div class="d-none d-md-block">
            <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Shop More
            </a>
        </div>
    </div>

    <!-- Reviews List -->
    <?php if (!empty($user_reviews)): ?>
        <div class="reviews-list">
            <?php foreach ($user_reviews as $review): ?>
                <div class="card mb-3 review-card">
                    <div class="card-body">
                        <div class="row">
                            <!-- Product Image -->
                            <div class="col-md-2 col-3">
                                <?php 
                                $screenshots = json_decode($review['screenshots'] ?? '[]', true);
                                if (!empty($screenshots) && isset($screenshots[0])): 
                                ?>
                                    <div style="height: 80px; border-radius: 0.5rem; overflow: hidden; border: 1px solid var(--border-color); background: var(--bg-secondary);">
                                        <img src="<?php echo SITE_URL . '/' . $screenshots[0]; ?>" 
                                             alt="<?php echo htmlspecialchars($review['product_title']); ?>"
                                             style="width: 100%; height: 100%; object-fit: contain; display: block;">
                                    </div>
                                <?php else: ?>
                                    <div style="height: 80px; background: linear-gradient(45deg, var(--primary-color), var(--secondary-color)); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                                        <i class="fas fa-star"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Review Content -->
                            <div class="col-md-10 col-9">
                                <div style="padding-left: 1rem;">
                                    <div class="d-flex justify-between align-items-start mb-2">
                                        <div>
                                            <h5 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                                                <a href="<?php echo SITE_URL; ?>/product.php?id=<?php echo $review['product_id']; ?>" style="color: var(--text-primary); text-decoration: none;">
                                                    <?php echo htmlspecialchars($review['product_title']); ?>
                                                </a>
                                            </h5>
                                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                                <div style="color: #ffc107; font-size: 1.2rem;">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <?php if ($i <= $review['rating']): ?>
                                                            <span>★</span>
                                                        <?php else: ?>
                                                            <span style="color: #ddd;">★</span>
                                                        <?php endif; ?>
                                                    <?php endfor; ?>
                                                </div>
                                                <span style="color: var(--text-muted); font-size: 0.875rem;">
                                                    <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                                                </span>
                                                <?php if ($review['is_verified_purchase']): ?>
                                                    <span style="background: var(--success-color); color: white; font-size: 0.75rem; padding: 0.125rem 0.5rem; border-radius: 1rem;">
                                                        Verified Purchase
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div style="color: var(--primary-color); font-weight: bold;">
                                            <?php echo format_currency($review['price']); ?>
                                        </div>
                                    </div>
                                    
                                    <?php if ($review['review_title']): ?>
                                        <h6 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                                            <?php echo htmlspecialchars($review['review_title']); ?>
                                        </h6>
                                    <?php endif; ?>
                                    
                                    <?php if ($review['review_text']): ?>
                                        <p style="color: var(--text-secondary); margin-bottom: 1rem; line-height: 1.6;">
                                            <?php echo nl2br(htmlspecialchars($review['review_text'])); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="review-actions">
                                        <a href="<?php echo SITE_URL; ?>/product.php?id=<?php echo $review['product_id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye"></i>
                                            View Product
                                        </a>
                                        <button class="btn btn-secondary btn-sm" onclick="editReview(<?php echo $review['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                            Edit Review
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="text-center mt-4">
                <nav>
                    <div style="display: flex; justify-content: center; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>" class="btn btn-secondary btn-sm">
                                <i class="fas fa-chevron-left"></i>
                                Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="?page=<?php echo $i; ?>" 
                               class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>" class="btn btn-secondary btn-sm">
                                Next
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </nav>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <div class="text-center" style="padding: 4rem 0;">
            <i class="fas fa-star" style="font-size: 4rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
            <h3 style="color: var(--text-secondary); margin-bottom: 1rem;">No Reviews Yet</h3>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">
                You haven't written any reviews yet. Purchase products and share your experience!
            </p>
            <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary btn-lg">
                <i class="fas fa-shopping-bag"></i>
                Start Shopping
            </a>
        </div>
    <?php endif; ?>
</div>

<style>
.review-card {
    transition: all 0.3s ease;
    border-left: 4px solid var(--border-color);
}

.review-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
    border-left-color: var(--primary-color);
}

.review-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

@media (max-width: 767px) {
    .review-actions {
        margin-top: 1rem;
    }
    
    .review-actions .btn {
        font-size: 0.75rem;
        padding: 0.375rem 0.75rem;
    }
}
</style>

<script>
function editReview(reviewId) {
    // Placeholder for edit review functionality
    alert('Edit review functionality coming soon!');
}
</script>

<?php include 'includes/footer.php'; ?>
