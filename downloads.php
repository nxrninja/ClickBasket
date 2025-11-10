<?php
$page_title = 'My Downloads - ClickBasket';
$mobile_title = 'Downloads';

require_once 'config/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php?redirect=downloads.php');
}

$database = new Database();
$db = $database->getConnection();

// Get user downloads with product information
try {
    $downloads_query = "SELECT d.*, p.title, p.file_size, p.screenshots, o.order_number, o.created_at as purchase_date,
                        c.name as category_name
                        FROM downloads d
                        JOIN products p ON d.product_id = p.id
                        JOIN orders o ON d.order_id = o.id
                        LEFT JOIN categories c ON p.category_id = c.id
                        WHERE d.user_id = ? AND o.payment_status = 'completed'
                        ORDER BY d.created_at DESC";
    $downloads_stmt = $db->prepare($downloads_query);
    $downloads_stmt->execute([get_current_user_id()]);
    $downloads = $downloads_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $downloads = [];
}

// Handle download request
if (isset($_GET['download']) && isset($_GET['token'])) {
    $download_id = $_GET['download'];
    $token = $_GET['token'];
    
    try {
        // Verify download token and limits
        $verify_query = "SELECT d.*, p.file_path, p.title 
                         FROM downloads d
                         JOIN products p ON d.product_id = p.id
                         WHERE d.id = ? AND d.download_token = ? AND d.user_id = ?
                         AND (d.expires_at IS NULL OR d.expires_at > NOW())
                         AND d.download_count < d.max_downloads";
        $verify_stmt = $db->prepare($verify_query);
        $verify_stmt->execute([$download_id, $token, get_current_user_id()]);
        $download_info = $verify_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($download_info) {
            // Update download count
            $update_query = "UPDATE downloads SET download_count = download_count + 1 WHERE id = ?";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->execute([$download_id]);
            
            // Serve file download (in a real implementation, you'd serve the actual file)
            handle_success('Download started for: ' . $download_info['title']);
        } else {
            handle_error('Invalid download link or download limit exceeded.');
        }
    } catch (Exception $e) {
        handle_error('Download failed. Please try again.');
    }
}

include 'includes/header.php';
?>

<div class="container" style="padding: 2rem 0;">
    <!-- Page Header -->
    <div class="d-flex justify-between align-center mb-4">
        <div>
            <h1 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                <i class="fas fa-download"></i>
                My Downloads
            </h1>
            <p style="color: var(--text-secondary); margin: 0;">
                <?php echo count($downloads); ?> product<?php echo count($downloads) !== 1 ? 's' : ''; ?> available for download
            </p>
        </div>
        <div class="d-none d-md-block">
            <a href="<?php echo SITE_URL; ?>/orders.php" class="btn btn-secondary">
                <i class="fas fa-box"></i>
                View Orders
            </a>
        </div>
    </div>

    <!-- Download Stats -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h3 style="color: var(--primary-color); margin-bottom: 0.5rem;">
                        <?php echo count($downloads); ?>
                    </h3>
                    <p style="color: var(--text-secondary); margin: 0; font-size: 0.875rem;">Total Products</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h3 style="color: var(--success-color); margin-bottom: 0.5rem;">
                        <?php echo array_sum(array_column($downloads, 'download_count')); ?>
                    </h3>
                    <p style="color: var(--text-secondary); margin: 0; font-size: 0.875rem;">Total Downloads</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h3 style="color: var(--secondary-color); margin-bottom: 0.5rem;">
                        <?php 
                        $active_downloads = array_filter($downloads, function($d) {
                            return $d['download_count'] < $d['max_downloads'] && 
                                   (is_null($d['expires_at']) || strtotime($d['expires_at']) > time());
                        });
                        echo count($active_downloads);
                        ?>
                    </h3>
                    <p style="color: var(--text-secondary); margin: 0; font-size: 0.875rem;">Active Downloads</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Downloads List -->
    <?php if (!empty($downloads)): ?>
        <div class="downloads-grid">
            <?php foreach ($downloads as $download): ?>
                <?php
                $is_expired = !is_null($download['expires_at']) && strtotime($download['expires_at']) <= time();
                $is_limit_reached = $download['download_count'] >= $download['max_downloads'];
                $can_download = !$is_expired && !$is_limit_reached;
                ?>
                
                <div class="card download-card fade-in">
                    <div class="download-preview" style="background: linear-gradient(45deg, var(--primary-color), var(--secondary-color)); display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem; height: 200px;">
                        <i class="fas fa-<?php 
                            echo match($download['category_name']) {
                                'Web Templates' => 'code',
                                'Mobile Apps' => 'mobile-alt',
                                'Graphics & Design' => 'palette',
                                'Software Tools' => 'tools',
                                'E-books' => 'book',
                                default => 'file'
                            };
                        ?>"></i>
                    </div>
                    
                    <div class="card-body">
                        <div class="d-flex justify-between align-center mb-2">
                            <span style="background: var(--primary-color); color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;">
                                <?php echo htmlspecialchars($download['category_name']); ?>
                            </span>
                            <span class="download-status <?php echo $can_download ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $can_download ? 'Available' : ($is_expired ? 'Expired' : 'Limit Reached'); ?>
                            </span>
                        </div>
                        
                        <h4 style="color: var(--text-primary); margin-bottom: 1rem; line-height: 1.4;">
                            <?php echo htmlspecialchars($download['title']); ?>
                        </h4>
                        
                        <div class="download-info" style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 1rem;">
                            <div class="d-flex justify-between mb-1">
                                <span><i class="fas fa-calendar"></i> Purchased:</span>
                                <span><?php echo date('M j, Y', strtotime($download['purchase_date'])); ?></span>
                            </div>
                            <div class="d-flex justify-between mb-1">
                                <span><i class="fas fa-file"></i> Size:</span>
                                <span><?php echo htmlspecialchars($download['file_size']); ?></span>
                            </div>
                            <div class="d-flex justify-between mb-1">
                                <span><i class="fas fa-download"></i> Downloads:</span>
                                <span><?php echo $download['download_count']; ?> / <?php echo $download['max_downloads']; ?></span>
                            </div>
                            <?php if (!is_null($download['expires_at'])): ?>
                                <div class="d-flex justify-between">
                                    <span><i class="fas fa-clock"></i> Expires:</span>
                                    <span><?php echo date('M j, Y', strtotime($download['expires_at'])); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="download-progress mb-3">
                            <div style="background: var(--bg-secondary); border-radius: 0.5rem; height: 8px; overflow: hidden;">
                                <div style="background: var(--primary-color); height: 100%; width: <?php echo ($download['download_count'] / $download['max_downloads']) * 100; ?>%; transition: width 0.3s ease;"></div>
                            </div>
                            <small style="color: var(--text-muted); margin-top: 0.25rem; display: block;">
                                <?php echo $download['max_downloads'] - $download['download_count']; ?> downloads remaining
                            </small>
                        </div>
                        
                        <div class="download-actions" style="display: flex; gap: 0.5rem;">
                            <?php if ($can_download): ?>
                                <a href="?download=<?php echo $download['id']; ?>&token=<?php echo $download['download_token']; ?>" 
                                   class="btn btn-primary btn-sm flex-1">
                                    <i class="fas fa-download"></i>
                                    Download
                                </a>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-sm flex-1" disabled>
                                    <i class="fas fa-ban"></i>
                                    <?php echo $is_expired ? 'Expired' : 'Limit Reached'; ?>
                                </button>
                            <?php endif; ?>
                            
                            <a href="<?php echo SITE_URL; ?>/product.php?id=<?php echo $download['product_id']; ?>" 
                               class="btn btn-secondary btn-sm">
                                <i class="fas fa-eye"></i>
                                View
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Download Instructions -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 style="margin: 0;">
                    <i class="fas fa-info-circle"></i>
                    Download Instructions
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 style="color: var(--text-primary); margin-bottom: 0.5rem;">Download Limits</h6>
                        <ul style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 1rem;">
                            <li>Each product has a maximum download limit (usually 5 downloads)</li>
                            <li>Downloads may expire after a certain period (usually 30 days)</li>
                            <li>Make sure to save your files in a secure location</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 style="color: var(--text-primary); margin-bottom: 0.5rem;">Need Help?</h6>
                        <ul style="color: var(--text-secondary); font-size: 0.875rem; margin-bottom: 1rem;">
                            <li>Contact support if you're having download issues</li>
                            <li>Check your internet connection for large files</li>
                            <li>Use a download manager for better reliability</li>
                        </ul>
                    </div>
                </div>
                <div class="text-center">
                    <a href="<?php echo SITE_URL; ?>/contact.php" class="btn btn-secondary">
                        <i class="fas fa-headset"></i>
                        Contact Support
                    </a>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- No Downloads -->
        <div class="text-center" style="padding: 4rem 0;">
            <i class="fas fa-download" style="font-size: 4rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
            <h3 style="color: var(--text-secondary); margin-bottom: 1rem;">No Downloads Available</h3>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">
                Purchase products to access downloads. Your purchased items will appear here.
            </p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-shopping-bag"></i>
                    Browse Products
                </a>
                <a href="<?php echo SITE_URL; ?>/orders.php" class="btn btn-secondary">
                    <i class="fas fa-box"></i>
                    View Orders
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.downloads-grid {
    display: grid;
    gap: 1.5rem;
    grid-template-columns: 1fr;
}

@media (min-width: 640px) {
    .downloads-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1024px) {
    .downloads-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

.download-card {
    transition: all 0.3s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.download-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.download-card .card-body {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.download-actions {
    margin-top: auto;
}

.download-status {
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
}

.status-active {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success-color);
    border: 1px solid var(--success-color);
}

.status-inactive {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger-color);
    border: 1px solid var(--danger-color);
}

.download-preview {
    border-radius: 0.75rem 0.75rem 0 0;
}

.flex-1 {
    flex: 1;
}

.fade-in {
    animation: fadeInUp 0.5s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 767px) {
    .downloads-grid {
        grid-template-columns: 1fr;
    }
    
    .download-actions {
        flex-direction: column;
    }
    
    .download-actions .btn {
        margin-bottom: 0.5rem;
    }
    
    .download-actions .btn:last-child {
        margin-bottom: 0;
    }
}
</style>

<script>
// Auto-refresh download status every 60 seconds
setInterval(() => {
    // Check if there are any active downloads that might have status changes
    const activeDownloads = document.querySelectorAll('.status-active');
    if (activeDownloads.length > 0) {
        // Only refresh if user is actively viewing the page
        if (!document.hidden) {
            location.reload();
        }
    }
}, 60000);

// Show download progress for large files
document.querySelectorAll('a[href*="download="]').forEach(link => {
    link.addEventListener('click', function(e) {
        const btn = this;
        const originalText = btn.innerHTML;
        
        // Show downloading state
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Downloading...';
        btn.classList.add('disabled');
        
        // Reset after 3 seconds (in real implementation, this would be handled by actual download)
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.classList.remove('disabled');
        }, 3000);
    });
});
</script>

<?php include 'includes/footer.php'; ?>
