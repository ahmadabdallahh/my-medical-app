<?php

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;
    private $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_PERSISTENT => true,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];

    public function __construct() {
        // استخدام متغيرات البيئة للمعلومات الحساسة
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->db_name = $_ENV['DB_NAME'] ?? 'medical_booking';
        $this->username = $_ENV['DB_USERNAME'] ?? 'root';
        $this->password = $_ENV['DB_PASSWORD'] ?? '';
    }

    public function getConnection() {
        if ($this->conn === null) {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4";
                $this->conn = new PDO($dsn, $this->username, $this->password, $this->options);

                // تعيين المنطقة الزمنية
                $this->conn->exec("SET time_zone = '+02:00'");

            } catch(PDOException $exception) {
                // تسجيل الخطأ بشكل آمن بدون كشف تفاصيل حساسة
                error_log("Database connection error: " . $exception->getMessage());
                throw new Exception("خطأ في الاتصال بقاعدة البيانات");
            }
        }
        return $this->conn;
    }

    public function closeConnection() {
        $this->conn = null;
    }
}
