<?php
require_once dirname(__DIR__) . '/config/database.php';

class POSFunctions
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->ensureTransactionSnapshotColumns();
        $this->ensureTransactionPaymentColumns();
        $this->ensurePurchasesTable();
    }

    private function columnExists($table, $column)
    {
        $query = "
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table
              AND COLUMN_NAME = :column
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':table', $table);
        $stmt->bindParam(':column', $column);
        $stmt->execute();
        return (int) $stmt->fetchColumn() > 0;
    }

    private function ensureTransactionSnapshotColumns()
    {
        if (!$this->columnExists('transaction_details', 'unit_price')) {
            $this->conn->exec("ALTER TABLE transaction_details ADD COLUMN unit_price DECIMAL(12,2) NULL AFTER qty");
            $this->conn->exec("UPDATE transaction_details SET unit_price = CASE WHEN qty > 0 THEN subtotal / qty ELSE 0 END WHERE unit_price IS NULL");
            $this->conn->exec("ALTER TABLE transaction_details MODIFY unit_price DECIMAL(12,2) NOT NULL DEFAULT 0");
        }

        if (!$this->columnExists('transaction_details', 'product_name')) {
            $this->conn->exec("ALTER TABLE transaction_details ADD COLUMN product_name VARCHAR(150) NULL AFTER product_id");
            $this->conn->exec("
                UPDATE transaction_details td
                LEFT JOIN products p ON td.product_id = p.id
                SET td.product_name = COALESCE(p.name, 'Produk tidak tersedia')
                WHERE td.product_name IS NULL OR td.product_name = ''
            ");
        }
    }

    private function ensureTransactionPaymentColumns()
    {
        if (!$this->columnExists('transactions', 'payment_method')) {
            $this->conn->exec("ALTER TABLE transactions ADD COLUMN payment_method VARCHAR(30) NOT NULL DEFAULT 'Tunai' AFTER total");
        }

        if (!$this->columnExists('transactions', 'paid_amount')) {
            $this->conn->exec("ALTER TABLE transactions ADD COLUMN paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER payment_method");
        }

        if (!$this->columnExists('transactions', 'change_amount')) {
            $this->conn->exec("ALTER TABLE transactions ADD COLUMN change_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER paid_amount");
        }
    }

    private function ensurePurchasesTable()
    {
        $this->conn->exec("
            CREATE TABLE IF NOT EXISTS purchases (
                id INT AUTO_INCREMENT PRIMARY KEY,
                supplier_name VARCHAR(150) NOT NULL,
                supplier_contact VARCHAR(100) NULL,
                product_id INT NOT NULL,
                product_name VARCHAR(150) NOT NULL,
                qty INT NOT NULL DEFAULT 0,
                unit_cost DECIMAL(12,2) NOT NULL DEFAULT 0,
                total_cost DECIMAL(12,2) NOT NULL DEFAULT 0,
                status ENUM('dipesan', 'diterima') NOT NULL DEFAULT 'dipesan',
                purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                received_date DATETIME NULL,
                INDEX idx_purchases_product (product_id),
                INDEX idx_purchases_status (status)
            )
        ");

        if (!$this->columnExists('purchases', 'supplier_contact')) {
            $this->conn->exec("ALTER TABLE purchases ADD COLUMN supplier_contact VARCHAR(100) NULL AFTER supplier_name");
        }
    }

    // PRODUCTS CRUD
    public function createProduct($name, $price, $stock, $category_id = null)
    {
        $query = "INSERT INTO products (name, price, stock, category_id) VALUES (:name, :price, :stock, :category_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':stock', $stock);
        $stmt->bindParam(':category_id', $category_id);
        return $stmt->execute();
    }

    public function getProducts()
    {
        $query = "SELECT p.*, c.name as category FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY c.name, p.name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProduct($id)
    {
        $query = "SELECT p.*, c.name as category FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateProduct($id, $name, $price, $stock, $category_id = null)
    {
        $query = "UPDATE products SET name=:name, price=:price, stock=:stock, category_id=:category_id WHERE id=:id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':stock', $stock);
        $stmt->bindParam(':category_id', $category_id);
        return $stmt->execute();
    }

    public function updateProductPriceStock($id, $price, $stock)
    {
        $query = "UPDATE products SET price = :price, stock = :stock WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':stock', $stock);
        return $stmt->execute();
    }

    public function deleteProduct($id)
    {
        $check_query = "SELECT COUNT(*) FROM transaction_details WHERE product_id = :id";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(':id', $id);
        $check_stmt->execute();

        if ((int) $check_stmt->fetchColumn() > 0) {
            return false;
        }

        $delete_product_query = "DELETE FROM products WHERE id = :id";
        $delete_product_stmt = $this->conn->prepare($delete_product_query);
        $delete_product_stmt->bindParam(':id', $id);
        return $delete_product_stmt->execute();
    }

    // CATEGORIES CRUD
    public function createCategory($name)
    {
        $query = "INSERT INTO categories (name) VALUES (:name)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        return $stmt->execute();
    }

    public function getCategories()
    {
        $query = "SELECT * FROM categories ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCategory($id)
    {
        $query = "SELECT * FROM categories WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateCategory($id, $name)
    {
        $query = "UPDATE categories SET name = :name WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $name);
        return $stmt->execute();
    }

    public function deleteCategory($id)
    {
        $query = "DELETE FROM categories WHERE id = :id AND id NOT IN (SELECT category_id FROM products WHERE category_id IS NOT NULL)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // STOCK MANAGEMENT
    public function reduceStock($product_id, $qty)
    {
        $query = "UPDATE products SET stock = stock - :qty WHERE id = :id AND stock >= :qty";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $product_id);
        $stmt->bindParam(':qty', $qty);
        return $stmt->execute();
    }

    public function addStock($product_id, $qty)
    {
        $query = "UPDATE products SET stock = stock + :qty WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $product_id);
        $stmt->bindParam(':qty', $qty);
        return $stmt->execute();
    }

    // EMPTY STOCK ALERTS
    public function getEmptyStockProducts()
    {
        $query = "SELECT p.*, c.name as category FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.stock <= 0 ORDER BY c.name, p.name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // MARKET BASKET PAIR ANALYSIS (simple: pairs bought together in same trans)
    public function getTopPairs($limit = 10)
    {
        $query = "
            SELECT 
                CONCAT(p1.name, ' + ', p2.name) as pair,
                COUNT(*) as freq
            FROM transaction_details td1
            JOIN transaction_details td2 ON td1.trans_id = td2.trans_id AND td1.product_id < td2.product_id
            JOIN products p1 ON td1.product_id = p1.id
            JOIN products p2 ON td2.product_id = p2.id
            GROUP BY td1.product_id, td2.product_id
            ORDER BY freq DESC
            LIMIT :limit
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLowStockProducts($threshold = 5)
    {
        $query = "SELECT p.*, c.name as category FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.stock > 0 AND p.stock <= :threshold ORDER BY p.stock ASC, p.name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':threshold', $threshold, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopSoldProducts($limit = 10, $start_date = null, $end_date = null)
    {
        $dateWhere = '';
        if ($start_date && $end_date) {
            $dateWhere = "WHERE t.trans_date BETWEEN :start_date AND :end_date";
        }
        $query = "
            SELECT
                COALESCE(td.product_name, p.name, 'Produk tidak tersedia') as product_name,
                SUM(td.qty) as total_qty,
                SUM(td.subtotal) as total_sales
            FROM transaction_details td
            JOIN transactions t ON td.trans_id = t.id
            LEFT JOIN products p ON td.product_id = p.id
            $dateWhere
            GROUP BY td.product_id, COALESCE(td.product_name, p.name, 'Produk tidak tersedia')
            ORDER BY total_qty DESC, total_sales DESC
            LIMIT :limit
        ";
        $stmt = $this->conn->prepare($query);
        if ($start_date && $end_date) {
            $stmt->bindValue(':start_date', $start_date);
            $stmt->bindValue(':end_date', $end_date);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // TRANSACTION FUNCTIONS
    public function createTransaction($total, $details, $payment_method = 'Tunai', $paid_amount = 0)
    {
        $change_amount = max(0, (float) $paid_amount - (float) $total);
        // Insert transaction
        $query = "INSERT INTO transactions (total, payment_method, paid_amount, change_amount) VALUES (:total, :payment_method, :paid_amount, :change_amount)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':total', $total);
        $stmt->bindParam(':payment_method', $payment_method);
        $stmt->bindParam(':paid_amount', $paid_amount);
        $stmt->bindParam(':change_amount', $change_amount);
        $stmt->execute();
        $trans_id = $this->conn->lastInsertId();

        // Insert details & reduce stock
        foreach ($details as $item) {
            $subtotal = $item['price'] * $item['qty'];
            $detail_query = "INSERT INTO transaction_details (trans_id, product_id, product_name, qty, unit_price, subtotal) VALUES (:trans_id, :prod_id, :product_name, :qty, :unit_price, :subtotal)";
            $detail_stmt = $this->conn->prepare($detail_query);
            $detail_stmt->bindParam(':trans_id', $trans_id);
            $detail_stmt->bindParam(':prod_id', $item['id']);
            $detail_stmt->bindParam(':product_name', $item['name']);
            $detail_stmt->bindParam(':qty', $item['qty']);
            $detail_stmt->bindParam(':unit_price', $item['price']);
            $detail_stmt->bindParam(':subtotal', $subtotal);
            $detail_stmt->execute();

            $this->reduceStock($item['id'], $item['qty']);
        }
        return $trans_id;
    }

    public function getRecentTransactions($limit = 10)
    {
        $query = "
            SELECT t.*, GROUP_CONCAT(COALESCE(td.product_name, p.name) SEPARATOR ', ') as items, COALESCE(SUM(td.qty), 0) as item_count
            FROM transactions t 
            LEFT JOIN transaction_details td ON t.id = td.trans_id 
            LEFT JOIN products p ON td.product_id = p.id 
            GROUP BY t.id 
            ORDER BY t.trans_date DESC 
            LIMIT :limit
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTransactionDetails($trans_id)
    {
        // Get main transaction
        $query = "SELECT * FROM transactions WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $trans_id);
        $stmt->execute();
        $trans = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$trans) {
            return null;
        }

        // Get transaction items
        $detail_query = "
            SELECT td.*, COALESCE(td.product_name, p.name, 'Produk tidak tersedia') as product_name
            FROM transaction_details td 
            LEFT JOIN products p ON td.product_id = p.id 
            WHERE td.trans_id = :trans_id
        ";
        $detail_stmt = $this->conn->prepare($detail_query);
        $detail_stmt->bindParam(':trans_id', $trans_id);
        $detail_stmt->execute();
        $items = $detail_stmt->fetchAll(PDO::FETCH_ASSOC);

        $trans['items'] = $items;
        $trans['item_count'] = count($items);

        return $trans;
    }

    public function deleteTransaction($trans_id)
    {
        $trans = $this->getTransactionDetails($trans_id);
        if (!$trans) {
            return false;
        }

        $this->conn->beginTransaction();
        try {
            foreach ($trans['items'] as $item) {
                if (!empty($item['product_id']) && (int) $item['qty'] > 0) {
                    $this->addStock($item['product_id'], (int) $item['qty']);
                }
            }

            $detail_stmt = $this->conn->prepare("DELETE FROM transaction_details WHERE trans_id = :trans_id");
            $detail_stmt->bindValue(':trans_id', $trans_id, PDO::PARAM_INT);
            $detail_stmt->execute();

            $trans_stmt = $this->conn->prepare("DELETE FROM transactions WHERE id = :id");
            $trans_stmt->bindValue(':id', $trans_id, PDO::PARAM_INT);
            $trans_stmt->execute();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    // STATS FOR REPORTS
    public function getTransCount()
    {
        return $this->conn->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
    }

    public function getTotalSales()
    {
        return $this->conn->query("SELECT COALESCE(SUM(total), 0) FROM transactions")->fetchColumn();
    }

    public function getDailySummary()
    {
        $query = "
            SELECT
                COUNT(*) as total_transactions,
                COALESCE(SUM(total), 0) as total_sales
            FROM transactions
            WHERE trans_date >= CURDATE() AND trans_date < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
        ";
        return $this->conn->query($query)->fetch(PDO::FETCH_ASSOC);
    }

    public function getSalesChartData($days = 7)
    {
        $query = "
            SELECT DATE(trans_date) as sales_date, COUNT(*) as transaction_count, COALESCE(SUM(total), 0) as total_sales
            FROM transactions
            WHERE trans_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
            GROUP BY DATE(trans_date)
            ORDER BY sales_date ASC
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':days', $days - 1, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSalesReport($start_date, $end_date)
    {
        $query = "
            SELECT *
            FROM transactions
            WHERE trans_date BETWEEN :start_date AND :end_date
            ORDER BY trans_date DESC
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':start_date', $start_date);
        $stmt->bindValue(':end_date', $end_date);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createPurchase($supplier_name, $supplier_contact, $product_id, $qty, $unit_cost, $status = 'dipesan')
    {
        $product = $this->getProduct($product_id);
        if (!$product) {
            return false;
        }

        $total_cost = (float) $qty * (float) $unit_cost;
        $received_date = $status === 'diterima' ? date('Y-m-d H:i:s') : null;
        $query = "
            INSERT INTO purchases (supplier_name, supplier_contact, product_id, product_name, qty, unit_cost, total_cost, status, received_date)
            VALUES (:supplier_name, :supplier_contact, :product_id, :product_name, :qty, :unit_cost, :total_cost, :status, :received_date)
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':supplier_name', $supplier_name);
        $stmt->bindValue(':supplier_contact', $supplier_contact);
        $stmt->bindValue(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->bindValue(':product_name', $product['name']);
        $stmt->bindValue(':qty', $qty, PDO::PARAM_INT);
        $stmt->bindValue(':unit_cost', $unit_cost);
        $stmt->bindValue(':total_cost', $total_cost);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':received_date', $received_date);
        $saved = $stmt->execute();

        if ($saved && $status === 'diterima') {
            $this->addStock($product_id, $qty);
        }

        return $saved;
    }

    public function getPurchase($id)
    {
        $query = "SELECT * FROM purchases WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updatePurchase($id, $supplier_name, $supplier_contact, $product_id, $qty, $unit_cost, $status)
    {
        $purchase = $this->getPurchase($id);
        $product = $this->getProduct($product_id);
        if (!$purchase || !$product) {
            return false;
        }

        $total_cost = (float) $qty * (float) $unit_cost;
        $received_date = $status === 'diterima' ? ($purchase['received_date'] ?: date('Y-m-d H:i:s')) : null;

        $this->conn->beginTransaction();
        try {
            if ($purchase['status'] === 'diterima') {
                $this->addStock($purchase['product_id'], -1 * (int) $purchase['qty']);
            }

            if ($status === 'diterima') {
                $this->addStock($product_id, $qty);
            }

            $query = "
                UPDATE purchases
                SET supplier_name = :supplier_name,
                    supplier_contact = :supplier_contact,
                    product_id = :product_id,
                    product_name = :product_name,
                    qty = :qty,
                    unit_cost = :unit_cost,
                    total_cost = :total_cost,
                    status = :status,
                    received_date = :received_date
                WHERE id = :id
            ";
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':supplier_name', $supplier_name);
            $stmt->bindValue(':supplier_contact', $supplier_contact);
            $stmt->bindValue(':product_id', $product_id, PDO::PARAM_INT);
            $stmt->bindValue(':product_name', $product['name']);
            $stmt->bindValue(':qty', $qty, PDO::PARAM_INT);
            $stmt->bindValue(':unit_cost', $unit_cost);
            $stmt->bindValue(':total_cost', $total_cost);
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':received_date', $received_date);
            $stmt->execute();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function deletePurchase($id)
    {
        $purchase = $this->getPurchase($id);
        if (!$purchase) {
            return false;
        }

        $this->conn->beginTransaction();
        try {
            if ($purchase['status'] === 'diterima') {
                $this->addStock($purchase['product_id'], -1 * (int) $purchase['qty']);
            }

            $stmt = $this->conn->prepare("DELETE FROM purchases WHERE id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function receivePurchase($id)
    {
        $query = "SELECT * FROM purchases WHERE id = :id AND status = 'dipesan'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $purchase = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$purchase) {
            return false;
        }

        $this->conn->beginTransaction();
        try {
            $update = $this->conn->prepare("UPDATE purchases SET status = 'diterima', received_date = NOW() WHERE id = :id");
            $update->bindValue(':id', $id, PDO::PARAM_INT);
            $update->execute();
            $this->addStock($purchase['product_id'], $purchase['qty']);
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function getPurchases($limit = 100)
    {
        $query = "SELECT * FROM purchases ORDER BY purchase_date DESC LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPurchaseTotal($start_date = null, $end_date = null)
    {
        if ($start_date && $end_date) {
            $stmt = $this->conn->prepare("SELECT COALESCE(SUM(total_cost), 0) FROM purchases WHERE purchase_date BETWEEN :start_date AND :end_date");
            $stmt->bindValue(':start_date', $start_date);
            $stmt->bindValue(':end_date', $end_date);
            $stmt->execute();
            return $stmt->fetchColumn();
        }

        return $this->conn->query("SELECT COALESCE(SUM(total_cost), 0) FROM purchases")->fetchColumn();
    }

    // USER MANAGEMENT
    public function authenticateUser($username, $password)
    {
        $query = "SELECT * FROM users WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }

    public function createUser($username, $password, $role)
    {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO users (username, password, role) VALUES (:username, :password, :role)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $hashed);
        $stmt->bindParam(':role', $role);
        return $stmt->execute();
    }

    public function getUsers()
    {
        $query = "SELECT id, username, role, created_at FROM users ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUser($id)
    {
        $query = "SELECT id, username, role, created_at FROM users WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateUser($id, $username, $role)
    {
        $query = "UPDATE users SET username = :username, role = :role WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':role', $role);
        return $stmt->execute();
    }

    public function updateUserPassword($id, $password)
    {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $query = "UPDATE users SET password = :password WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':password', $hashed);
        return $stmt->execute();
    }

    public function deleteUser($id)
    {
        $query = "DELETE FROM users WHERE id = :id AND role != 'admin'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}

?>
