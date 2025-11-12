<?php
// Database configuration for ClickBasket
class Database {
    private $host = 'sql101.cpanelfree.com';
    private $db_name = 'cpfr_40391125_clickbasket';
    private $username = 'cpfr_40391125';
    private $password = 'Mm47a7Tjp6';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
            );
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}
?>
