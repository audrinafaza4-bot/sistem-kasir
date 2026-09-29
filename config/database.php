<?php
/**
 * Database configuration for Audri POS
 * Laragon MySQL: host=127.0.0.1 port=3306, user=root, pass=''
 */

class Database
{
    private $host = 'localhost';
    private $db_name = 'audri_pos';
    private $username = 'root';
    private $password = '';
    public $conn;

    public function getConnection()
    {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>