<?php
/**
 * Category Update Script for ClickBasket
 * This script updates the existing categories to comprehensive e-commerce categories
 * 
 * IMPORTANT: This script should only be run once and will replace all existing categories
 * Make sure to backup your database before running this script
 */

$page_title = 'Update Categories - ClickBasket Admin';
require_once '../config/config.php';

// Check if admin is logged in
if (!is_admin_logged_in()) {
    redirect('login.php');
}

// Check if user is super admin (for safety)
if (get_admin_role() !== 'super_admin') {
    die('Access denied. Only super admin can run this script.');
}

$database = new Database();
$db = $database->getConnection();
$message = '';
$error = '';
$categories_updated = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_update'])) {
    try {
        // Start transaction
        $db->beginTransaction();
        
        // First, backup existing categories
        $backup_query = "CREATE TABLE IF NOT EXISTS categories_backup_" . date('Y_m_d_H_i_s') . " AS SELECT * FROM categories";
        $db->exec($backup_query);
        
        // Clear existing categories
        $db->exec("DELETE FROM categories");
        
        // Reset auto increment
        $db->exec("ALTER TABLE categories AUTO_INCREMENT = 1");
        
        // Prepare insert statement
        $insert_query = "INSERT INTO categories (name, slug, description, is_active) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($insert_query);
        
        // E-commerce categories data
        $ecommerce_categories = [
            // Fashion & Apparel
            ['Fashion & Apparel', 'fashion-apparel', 'Clothing, shoes, accessories, and fashion items for men, women, and children', 1],
            ['Men\'s Clothing', 'mens-clothing', 'Shirts, pants, suits, jackets, and men\'s fashion wear', 1],
            ['Women\'s Clothing', 'womens-clothing', 'Dresses, tops, bottoms, outerwear, and women\'s fashion', 1],
            ['Kids & Baby', 'kids-baby', 'Children\'s clothing, baby items, toys, and accessories', 1],
            ['Shoes & Footwear', 'shoes-footwear', 'Athletic shoes, dress shoes, boots, sandals, and footwear', 1],
            ['Bags & Accessories', 'bags-accessories', 'Handbags, wallets, jewelry, watches, and fashion accessories', 1],
            
            // Electronics & Technology
            ['Electronics', 'electronics', 'Consumer electronics, gadgets, and technology products', 1],
            ['Smartphones & Tablets', 'smartphones-tablets', 'Mobile phones, tablets, and mobile accessories', 1],
            ['Computers & Laptops', 'computers-laptops', 'Desktop computers, laptops, and computer accessories', 1],
            ['Audio & Headphones', 'audio-headphones', 'Speakers, headphones, earbuds, and audio equipment', 1],
            ['Gaming', 'gaming', 'Video games, gaming consoles, and gaming accessories', 1],
            ['Smart Home', 'smart-home', 'Smart devices, home automation, and IoT products', 1],
            
            // Home & Garden
            ['Home & Garden', 'home-garden', 'Home improvement, furniture, decor, and garden supplies', 1],
            ['Furniture', 'furniture', 'Living room, bedroom, office, and outdoor furniture', 1],
            ['Home Decor', 'home-decor', 'Wall art, lighting, rugs, curtains, and decorative items', 1],
            ['Kitchen & Dining', 'kitchen-dining', 'Cookware, appliances, dinnerware, and kitchen tools', 1],
            ['Garden & Outdoor', 'garden-outdoor', 'Gardening tools, outdoor furniture, and patio accessories', 1],
            ['Tools & Hardware', 'tools-hardware', 'Hand tools, power tools, and hardware supplies', 1],
            
            // Health & Beauty
            ['Health & Beauty', 'health-beauty', 'Personal care, cosmetics, and wellness products', 1],
            ['Skincare', 'skincare', 'Face care, body care, and skincare treatments', 1],
            ['Makeup & Cosmetics', 'makeup-cosmetics', 'Foundation, lipstick, eyeshadow, and beauty products', 1],
            ['Hair Care', 'hair-care', 'Shampoo, conditioner, styling products, and hair tools', 1],
            ['Health & Wellness', 'health-wellness', 'Vitamins, supplements, and health monitoring devices', 1],
            ['Fragrances', 'fragrances', 'Perfumes, colognes, and body sprays', 1],
            
            // Sports & Outdoors
            ['Sports & Outdoors', 'sports-outdoors', 'Athletic gear, outdoor equipment, and fitness products', 1],
            ['Fitness Equipment', 'fitness-equipment', 'Exercise machines, weights, and home gym equipment', 1],
            ['Outdoor Recreation', 'outdoor-recreation', 'Camping, hiking, fishing, and outdoor adventure gear', 1],
            ['Team Sports', 'team-sports', 'Equipment for football, basketball, soccer, and other team sports', 1],
            ['Water Sports', 'water-sports', 'Swimming, surfing, diving, and water activity equipment', 1],
            ['Athletic Apparel', 'athletic-apparel', 'Sportswear, activewear, and athletic shoes', 1],
            
            // Books & Media
            ['Books & Media', 'books-media', 'Books, movies, music, and educational content', 1],
            ['Books', 'books', 'Fiction, non-fiction, textbooks, and digital books', 1],
            ['Movies & TV', 'movies-tv', 'DVDs, Blu-rays, and digital video content', 1],
            ['Music', 'music', 'CDs, vinyl records, and digital music', 1],
            ['Video Games', 'video-games', 'Console games, PC games, and gaming software', 1],
            
            // Automotive
            ['Automotive', 'automotive', 'Car parts, accessories, and automotive supplies', 1],
            ['Car Electronics', 'car-electronics', 'GPS, dash cams, stereos, and car tech accessories', 1],
            ['Car Care', 'car-care', 'Cleaning supplies, maintenance products, and car care tools', 1],
            ['Car Accessories', 'car-accessories', 'Seat covers, floor mats, organizers, and interior accessories', 1],
            
            // Food & Beverages
            ['Food & Beverages', 'food-beverages', 'Gourmet foods, snacks, and specialty beverages', 1],
            ['Gourmet Food', 'gourmet-food', 'Premium foods, specialty ingredients, and artisanal products', 1],
            ['Snacks & Candy', 'snacks-candy', 'Chips, cookies, chocolate, and confectionery', 1],
            ['Beverages', 'beverages', 'Coffee, tea, soft drinks, and specialty beverages', 1],
            
            // Pet Supplies
            ['Pet Supplies', 'pet-supplies', 'Pet food, toys, accessories, and care products', 1],
            ['Dog Supplies', 'dog-supplies', 'Dog food, toys, leashes, and canine accessories', 1],
            ['Cat Supplies', 'cat-supplies', 'Cat food, litter, toys, and feline accessories', 1],
            ['Pet Health', 'pet-health', 'Pet medications, supplements, and health products', 1],
            
            // Office & Business
            ['Office & Business', 'office-business', 'Office supplies, business equipment, and professional tools', 1],
            ['Office Supplies', 'office-supplies', 'Pens, paper, folders, and general office materials', 1],
            ['Business Equipment', 'business-equipment', 'Printers, scanners, shredders, and office machines', 1],
            ['Professional Services', 'professional-services', 'Business consulting, software, and professional tools', 1],
            
            // Arts & Crafts
            ['Arts & Crafts', 'arts-crafts', 'Art supplies, craft materials, and creative tools', 1],
            ['Art Supplies', 'art-supplies', 'Paints, brushes, canvases, and drawing materials', 1],
            ['Craft Materials', 'craft-materials', 'Fabric, yarn, beads, and crafting supplies', 1],
            ['Sewing & Quilting', 'sewing-quilting', 'Sewing machines, fabric, patterns, and quilting supplies', 1],
            
            // Jewelry & Watches
            ['Jewelry & Watches', 'jewelry-watches', 'Fine jewelry, fashion jewelry, and timepieces', 1],
            ['Fine Jewelry', 'fine-jewelry', 'Gold, silver, diamonds, and precious stone jewelry', 1],
            ['Fashion Jewelry', 'fashion-jewelry', 'Costume jewelry, fashion accessories, and trendy pieces', 1],
            ['Watches', 'watches', 'Luxury watches, smart watches, and timepieces', 1],
            
            // Baby & Maternity
            ['Baby & Maternity', 'baby-maternity', 'Baby products, maternity wear, and parenting essentials', 1],
            ['Baby Gear', 'baby-gear', 'Strollers, car seats, high chairs, and baby equipment', 1],
            ['Baby Clothing', 'baby-clothing', 'Infant and toddler clothing, shoes, and accessories', 1],
            ['Maternity', 'maternity', 'Maternity clothing, nursing supplies, and pregnancy products', 1],
            
            // Toys & Games
            ['Toys & Games', 'toys-games', 'Children\'s toys, board games, and educational games', 1],
            ['Educational Toys', 'educational-toys', 'Learning toys, STEM toys, and developmental games', 1],
            ['Action Figures', 'action-figures', 'Collectible figures, dolls, and character toys', 1],
            ['Board Games', 'board-games', 'Family games, strategy games, and puzzle games', 1],
            
            // Digital Products (keeping some digital focus)
            ['Digital Products', 'digital-products', 'Software, digital downloads, and online services', 1],
            ['Software', 'software', 'Computer software, mobile apps, and digital tools', 1],
            ['Digital Art', 'digital-art', 'Digital graphics, templates, and design resources', 1],
            ['Online Courses', 'online-courses', 'Educational content, tutorials, and skill development', 1]
        ];
        
        // Insert all categories
        foreach ($ecommerce_categories as $category) {
            $stmt->execute($category);
        }
        
        // Commit transaction
        $db->commit();
        
        $categories_updated = true;
        $message = 'Successfully updated ' . count($ecommerce_categories) . ' e-commerce categories!';
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollback();
        $error = 'Error updating categories: ' . $e->getMessage();
    }
}

// Get current category count
try {
    $count_query = "SELECT COUNT(*) as count FROM categories";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute();
    $current_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch (Exception $e) {
    $current_count = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-layout { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 250px; background: var(--bg-primary); border-right: 1px solid var(--border-color); position: fixed; height: 100vh; overflow-y: auto; }
        .admin-main { flex: 1; margin-left: 250px; background: var(--bg-secondary); }
        .admin-header { background: var(--bg-primary); border-bottom: 1px solid var(--border-color); padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .sidebar-brand { padding: 1.5rem; border-bottom: 1px solid var(--border-color); text-align: center; }
        .nav-link { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.5rem; color: var(--text-secondary); text-decoration: none; transition: all 0.3s ease; }
        .nav-link:hover, .nav-link.active { background: var(--primary-color); color: white; }
        .warning-box { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 0.5rem; padding: 1.5rem; margin-bottom: 2rem; }
        .success-box { background: #d1edff; border: 1px solid #74b9ff; border-radius: 0.5rem; padding: 1.5rem; margin-bottom: 2rem; }
        .error-box { background: #ffe0e0; border: 1px solid #ff7675; border-radius: 0.5rem; padding: 1.5rem; margin-bottom: 2rem; }
        .preview-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem; margin-top: 1rem; }
        .category-preview { background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 0.5rem; padding: 1rem; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <div class="admin-sidebar">
            <div class="sidebar-brand">
                <h3 style="color: var(--primary-color); margin: 0;"><i class="fas fa-shopping-basket"></i> ClickBasket</h3>
                <p style="color: var(--text-muted); font-size: 0.875rem; margin: 0.5rem 0 0;">Admin Panel</p>
            </div>
            <nav style="padding: 1rem 0;">
                <a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="products.php" class="nav-link"><i class="fas fa-box"></i> Products</a>
                <a href="users.php" class="nav-link"><i class="fas fa-users"></i> Users</a>
                <a href="orders.php" class="nav-link"><i class="fas fa-shopping-cart"></i> Orders</a>
                <a href="categories.php" class="nav-link"><i class="fas fa-tags"></i> Categories</a>
                <a href="../index.php" class="nav-link"><i class="fas fa-external-link-alt"></i> View Website</a>
                <a href="logout.php" class="nav-link" style="color: var(--danger-color);"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
        
        <div class="admin-main">
            <div class="admin-header">
                <h1 style="color: var(--text-primary); margin: 0;">Update Categories to E-commerce</h1>
                <a href="categories.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Categories
                </a>
            </div>
            
            <div style="padding: 2rem;">
                <?php if ($message): ?>
                    <div class="success-box">
                        <h4 style="color: #00b894; margin-bottom: 0.5rem;"><i class="fas fa-check-circle"></i> Success!</h4>
                        <p style="margin: 0;"><?php echo htmlspecialchars($message); ?></p>
                        <div style="margin-top: 1rem;">
                            <a href="categories.php" class="btn btn-primary">View Updated Categories</a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="error-box">
                        <h4 style="color: #e74c3c; margin-bottom: 0.5rem;"><i class="fas fa-exclamation-triangle"></i> Error!</h4>
                        <p style="margin: 0;"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (!$categories_updated): ?>
                    <div class="warning-box">
                        <h4 style="color: #f39c12; margin-bottom: 0.5rem;"><i class="fas fa-exclamation-triangle"></i> Important Warning!</h4>
                        <p><strong>This action will replace ALL existing categories with comprehensive e-commerce categories.</strong></p>
                        <ul style="margin: 1rem 0;">
                            <li>Current categories: <strong><?php echo $current_count; ?></strong></li>
                            <li>New categories: <strong>64 comprehensive e-commerce categories</strong></li>
                            <li>Existing products will have their category associations removed (can be reassigned later)</li>
                            <li>A backup of current categories will be created automatically</li>
                        </ul>
                        <p><strong>Make sure to backup your database before proceeding!</strong></p>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 style="margin: 0;"><i class="fas fa-list"></i> New E-commerce Categories Preview</h5>
                        </div>
                        <div class="card-body">
                            <p>The following categories will be created:</p>
                            <div class="preview-grid">
                                <div class="category-preview">
                                    <h6 style="color: var(--primary-color);">Fashion & Apparel (6 categories)</h6>
                                    <small>Men's Clothing, Women's Clothing, Kids & Baby, Shoes & Footwear, Bags & Accessories</small>
                                </div>
                                <div class="category-preview">
                                    <h6 style="color: var(--primary-color);">Electronics & Technology (6 categories)</h6>
                                    <small>Smartphones & Tablets, Computers & Laptops, Audio & Headphones, Gaming, Smart Home</small>
                                </div>
                                <div class="category-preview">
                                    <h6 style="color: var(--primary-color);">Home & Garden (6 categories)</h6>
                                    <small>Furniture, Home Decor, Kitchen & Dining, Garden & Outdoor, Tools & Hardware</small>
                                </div>
                                <div class="category-preview">
                                    <h6 style="color: var(--primary-color);">Health & Beauty (6 categories)</h6>
                                    <small>Skincare, Makeup & Cosmetics, Hair Care, Health & Wellness, Fragrances</small>
                                </div>
                                <div class="category-preview">
                                    <h6 style="color: var(--primary-color);">Sports & Outdoors (6 categories)</h6>
                                    <small>Fitness Equipment, Outdoor Recreation, Team Sports, Water Sports, Athletic Apparel</small>
                                </div>
                                <div class="category-preview">
                                    <h6 style="color: var(--primary-color);">Books & Media (5 categories)</h6>
                                    <small>Books, Movies & TV, Music, Video Games</small>
                                </div>
                                <div class="category-preview">
                                    <h6 style="color: var(--primary-color);">Automotive (4 categories)</h6>
                                    <small>Car Electronics, Car Care, Car Accessories</small>
                                </div>
                                <div class="category-preview">
                                    <h6 style="color: var(--primary-color);">Food & Beverages (4 categories)</h6>
                                    <small>Gourmet Food, Snacks & Candy, Beverages</small>
                                </div>
                                <div class="category-preview">
                                    <h6 style="color: var(--primary-color);">Pet Supplies (4 categories)</h6>
                                    <small>Dog Supplies, Cat Supplies, Pet Health</small>
                                </div>
                                <div class="category-preview">
                                    <h6 style="color: var(--primary-color);">Office & Business (4 categories)</h6>
                                    <small>Office Supplies, Business Equipment, Professional Services</small>
                                </div>
                                <div class="category-preview">
                                    <h6 style="color: var(--primary-color);">Arts & Crafts (4 categories)</h6>
                                    <small>Art Supplies, Craft Materials, Sewing & Quilting</small>
                                </div>
                                <div class="category-preview">
                                    <h6 style="color: var(--primary-color);">Jewelry & Watches (4 categories)</h6>
                                    <small>Fine Jewelry, Fashion Jewelry, Watches</small>
                                </div>
                                <div class="category-preview">
                                    <h6 style="color: var(--primary-color);">Baby & Maternity (4 categories)</h6>
                                    <small>Baby Gear, Baby Clothing, Maternity</small>
                                </div>
                                <div class="category-preview">
                                    <h6 style="color: var(--primary-color);">Toys & Games (4 categories)</h6>
                                    <small>Educational Toys, Action Figures, Board Games</small>
                                </div>
                                <div class="category-preview">
                                    <h6 style="color: var(--primary-color);">Digital Products (4 categories)</h6>
                                    <small>Software, Digital Art, Online Courses</small>
                                </div>
                            </div>
                            
                            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border-color);">
                                <form method="POST" onsubmit="return confirm('Are you sure you want to update all categories? This action cannot be undone!');">
                                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                        <input type="checkbox" id="confirm_backup" required>
                                        <label for="confirm_backup">I have backed up my database</label>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                        <input type="checkbox" id="confirm_understand" required>
                                        <label for="confirm_understand">I understand this will replace all existing categories</label>
                                    </div>
                                    <button type="submit" name="confirm_update" class="btn btn-danger">
                                        <i class="fas fa-sync-alt"></i> Update Categories to E-commerce
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
