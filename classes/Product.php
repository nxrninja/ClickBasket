<?php
// Database connection is handled by the calling script

class Product {
    private $conn;
    private $table_name = "products";

    public $id;
    public $title;
    public $slug;
    public $description;
    public $short_description;
    public $price;
    public $category_id;
    public $file_path;
    public $file_size;
    public $demo_url;
    public $screenshots;
    public $tags;
    public $downloads_count;
    public $is_active;
    public $is_featured;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get all products with pagination and filters
    public function getProducts($page = 1, $limit = 12, $filters = []) {
        $offset = ($page - 1) * $limit;
        
        $where_conditions = ["p.is_active = 1"];
        $params = [];

        // Category filter
        if (!empty($filters['category'])) {
            $where_conditions[] = "c.slug = :category";
            $params[':category'] = $filters['category'];
        }

        // Search filter
        if (!empty($filters['search'])) {
            $where_conditions[] = "(p.title LIKE :search OR p.description LIKE :search OR p.tags LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        // Price range filter
        if (!empty($filters['min_price'])) {
            $where_conditions[] = "p.price >= :min_price";
            $params[':min_price'] = $filters['min_price'];
        }
        if (!empty($filters['max_price'])) {
            $where_conditions[] = "p.price <= :max_price";
            $params[':max_price'] = $filters['max_price'];
        }

        $where_clause = implode(' AND ', $where_conditions);

        // Order by
        $order_by = "p.created_at DESC";
        if (!empty($filters['sort'])) {
            switch ($filters['sort']) {
                case 'price_low':
                    $order_by = "p.price ASC";
                    break;
                case 'price_high':
                    $order_by = "p.price DESC";
                    break;
                case 'popular':
                    $order_by = "p.downloads_count DESC";
                    break;
                case 'newest':
                    $order_by = "p.created_at DESC";
                    break;
            }
        }

        $query = "SELECT p.*, c.name as category_name, c.slug as category_slug
                  FROM " . $this->table_name . " p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE " . $where_clause . "
                  ORDER BY " . $order_by . "
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get total products count for pagination
    public function getTotalProducts($filters = []) {
        $where_conditions = ["p.is_active = 1"];
        $params = [];

        // Category filter
        if (!empty($filters['category'])) {
            $where_conditions[] = "c.slug = :category";
            $params[':category'] = $filters['category'];
        }

        // Search filter
        if (!empty($filters['search'])) {
            $where_conditions[] = "(p.title LIKE :search OR p.description LIKE :search OR p.tags LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        // Price range filter
        if (!empty($filters['min_price'])) {
            $where_conditions[] = "p.price >= :min_price";
            $params[':min_price'] = $filters['min_price'];
        }
        if (!empty($filters['max_price'])) {
            $where_conditions[] = "p.price <= :max_price";
            $params[':max_price'] = $filters['max_price'];
        }

        $where_clause = implode(' AND ', $where_conditions);

        $query = "SELECT COUNT(*) as total
                  FROM " . $this->table_name . " p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE " . $where_clause;

        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // Get product by ID
    public function getProductById($id) {
        $query = "SELECT p.*, c.name as category_name, c.slug as category_slug
                  FROM " . $this->table_name . " p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.id = :id AND p.is_active = 1
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->id = $row['id'];
            $this->title = $row['title'];
            $this->slug = $row['slug'];
            $this->description = $row['description'];
            $this->short_description = $row['short_description'];
            $this->price = $row['price'];
            $this->category_id = $row['category_id'];
            $this->file_path = $row['file_path'];
            $this->file_size = $row['file_size'];
            $this->demo_url = $row['demo_url'];
            $this->screenshots = $row['screenshots'];
            $this->tags = $row['tags'];
            $this->downloads_count = $row['downloads_count'];
            $this->is_active = $row['is_active'];
            $this->is_featured = $row['is_featured'];
            $this->created_at = $row['created_at'];
            return $row;
        }

        return false;
    }

    // Get related products
    public function getRelatedProducts($product_id, $category_id, $limit = 4) {
        $query = "SELECT p.*, c.name as category_name
                  FROM " . $this->table_name . " p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.id != :product_id 
                  AND p.category_id = :category_id 
                  AND p.is_active = 1
                  ORDER BY p.downloads_count DESC, p.created_at DESC
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get featured products
    public function getFeaturedProducts($limit = 8) {
        $query = "SELECT p.*, c.name as category_name
                  FROM " . $this->table_name . " p
                  LEFT JOIN categories c ON p.category_id = c.id
                  WHERE p.is_active = 1 AND p.is_featured = 1
                  ORDER BY p.created_at DESC
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Increment download count
    public function incrementDownloadCount() {
        $query = "UPDATE " . $this->table_name . " 
                  SET downloads_count = downloads_count + 1 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }

    // Create product (Admin)
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET title=:title, slug=:slug, description=:description, 
                      short_description=:short_description, price=:price, 
                      category_id=:category_id, file_path=:file_path, 
                      file_size=:file_size, demo_url=:demo_url, 
                      screenshots=:screenshots, tags=:tags, 
                      is_active=:is_active, is_featured=:is_featured";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->slug = $this->generateSlug($this->title);
        $this->description = htmlspecialchars($this->description);
        $this->short_description = htmlspecialchars(strip_tags($this->short_description));
        $this->tags = htmlspecialchars(strip_tags($this->tags));

        // Bind values
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':slug', $this->slug);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':short_description', $this->short_description);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':category_id', $this->category_id);
        $stmt->bindParam(':file_path', $this->file_path);
        $stmt->bindParam(':file_size', $this->file_size);
        $stmt->bindParam(':demo_url', $this->demo_url);
        $stmt->bindParam(':screenshots', $this->screenshots);
        $stmt->bindParam(':tags', $this->tags);
        $stmt->bindParam(':is_active', $this->is_active);
        $stmt->bindParam(':is_featured', $this->is_featured);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    // Update product (Admin)
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET title=:title, slug=:slug, description=:description, 
                      short_description=:short_description, price=:price, 
                      category_id=:category_id, demo_url=:demo_url, 
                      screenshots=:screenshots, tags=:tags, 
                      is_active=:is_active, is_featured=:is_featured
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->slug = $this->generateSlug($this->title);
        $this->description = htmlspecialchars($this->description);
        $this->short_description = htmlspecialchars(strip_tags($this->short_description));
        $this->tags = htmlspecialchars(strip_tags($this->tags));

        // Bind values
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':slug', $this->slug);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':short_description', $this->short_description);
        $stmt->bindParam(':price', $this->price);
        $stmt->bindParam(':category_id', $this->category_id);
        $stmt->bindParam(':demo_url', $this->demo_url);
        $stmt->bindParam(':screenshots', $this->screenshots);
        $stmt->bindParam(':tags', $this->tags);
        $stmt->bindParam(':is_active', $this->is_active);
        $stmt->bindParam(':is_featured', $this->is_featured);
        $stmt->bindParam(':id', $this->id);

        return $stmt->execute();
    }

    // Delete product (Admin)
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }

    // Generate slug from title
    private function generateSlug($title) {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Check if slug exists and make it unique
        $original_slug = $slug;
        $counter = 1;
        
        while ($this->slugExists($slug)) {
            $slug = $original_slug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    // Check if slug exists
    private function slugExists($slug) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE slug = :slug";
        if ($this->id) {
            $query .= " AND id != :id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':slug', $slug);
        if ($this->id) {
            $stmt->bindParam(':id', $this->id);
        }
        
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Get price ranges for filters
    public static function getPriceRanges($db) {
        $query = "SELECT MIN(price) as min_price, MAX(price) as max_price 
                  FROM products WHERE is_active = 1";
        $stmt = $db->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
