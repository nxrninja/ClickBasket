<?php
// Database connection is handled by the calling script

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $name;
    public $email;
    public $password;
    public $phone;
    public $is_verified;
    public $reset_token;
    public $reset_expires;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Register new user
    public function register() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET name=:name, email=:email, password=:password, phone=:phone";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->phone = htmlspecialchars(strip_tags($this->phone));

        // Hash password
        $password_hash = password_hash($this->password, PASSWORD_DEFAULT);

        // Bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $password_hash);
        $stmt->bindParam(":phone", $this->phone);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    // Login user
    public function login() {
        $query = "SELECT id, name, email, password, is_verified 
                  FROM " . $this->table_name . " 
                  WHERE email = :email LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && password_verify($this->password, $row['password'])) {
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->email = $row['email'];
            $this->is_verified = $row['is_verified'];
            return true;
        }

        return false;
    }

    // Check if email exists
    public function emailExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    // Get user by ID
    public function getUserById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->email = $row['email'];
            $this->phone = $row['phone'];
            $this->is_verified = $row['is_verified'];
            $this->created_at = $row['created_at'];
            return true;
        }

        return false;
    }

    // Update user profile
    public function updateProfile() {
        $query = "UPDATE " . $this->table_name . " 
                  SET name=:name, phone=:phone 
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->phone = htmlspecialchars(strip_tags($this->phone));

        // Bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Change password
    public function changePassword($new_password) {
        $query = "UPDATE " . $this->table_name . " 
                  SET password=:password 
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        // Hash new password
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

        // Bind values
        $stmt->bindParam(":password", $password_hash);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Generate password reset token
    public function generateResetToken() {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $query = "UPDATE " . $this->table_name . " 
                  SET reset_token=:token, reset_expires=:expires 
                  WHERE email=:email";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->bindParam(":expires", $expires);
        $stmt->bindParam(":email", $this->email);

        if ($stmt->execute()) {
            return $token;
        }

        return false;
    }

    // Verify reset token
    public function verifyResetToken($token) {
        $query = "SELECT id, email FROM " . $this->table_name . " 
                  WHERE reset_token=:token AND reset_expires > NOW() LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $token);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->id = $row['id'];
            $this->email = $row['email'];
            return true;
        }

        return false;
    }

    // Reset password with token
    public function resetPassword($token, $new_password) {
        if (!$this->verifyResetToken($token)) {
            return false;
        }

        $query = "UPDATE " . $this->table_name . " 
                  SET password=:password, reset_token=NULL, reset_expires=NULL 
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        // Hash new password
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

        // Bind values
        $stmt->bindParam(":password", $password_hash);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Validate user input
    public static function validateRegistration($data) {
        $errors = [];

        // Name validation
        if (empty($data['name']) || strlen(trim($data['name'])) < 2) {
            $errors['name'] = 'Name must be at least 2 characters long';
        }

        // Email validation
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address';
        }

        // Password validation
        if (empty($data['password']) || strlen($data['password']) < 6) {
            $errors['password'] = 'Password must be at least 6 characters long';
        }

        // Confirm password validation
        if (empty($data['confirm_password']) || $data['password'] !== $data['confirm_password']) {
            $errors['confirm_password'] = 'Passwords do not match';
        }

        return $errors;
    }

    public static function validateLogin($data) {
        $errors = [];

        // Email validation
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address';
        }

        // Password validation
        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        }

        return $errors;
    }
}
?>
