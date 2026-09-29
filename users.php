<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/includes/functions.php';
$pos = new POSFunctions();
$message = '';
$message_type = 'success';

if ($_POST) {
    $action = $_POST['action'];

    if ($action == 'create') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $role = $_POST['role'];

        if ($pos->createUser($username, $password, $role)) {
            $message = 'User berhasil ditambahkan!';
        } else {
            $message = 'Gagal menambahkan user. Username mungkin sudah ada.';
            $message_type = 'danger';
        }
    } elseif ($action == 'update') {
        $id = $_POST['id'];
        $username = trim($_POST['username']);
        $role = $_POST['role'];

        if ($pos->updateUser($id, $username, $role)) {
            $message = 'User berhasil diupdate!';
        } else {
            $message = 'Gagal mengupdate user.';
            $message_type = 'danger';
        }
    } elseif ($action == 'update_password') {
        $id = $_POST['id'];
        $password = $_POST['password'];

        if ($pos->updateUserPassword($id, $password)) {
            $message = 'Password berhasil diupdate!';
        } else {
            $message = 'Gagal mengupdate password.';
            $message_type = 'danger';
        }
    } elseif ($action == 'delete') {
        $id = $_POST['id'];

        if ($pos->deleteUser($id)) {
            $message = 'User berhasil dihapus!';
        } else {
            $message = 'Gagal menghapus user.';
            $message_type = 'danger';
        }
    }
}

$users = $pos->getUsers();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - Audri POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --accent-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #1abc9c;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar-custom {
            background: linear-gradient(90deg, var(--primary-color) 0%, #34495e 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-custom .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            letter-spacing: 1px;
        }

        .navbar-custom .nav-link {
            margin: 0 10px;
            transition: all 0.3s ease;
            border-radius: 5px;
        }

        .navbar-custom .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .page-header {
            background: linear-gradient(135deg, var(--info-color) 0%, #16a085 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .page-header p {
            opacity: 0.9;
            margin: 0;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            background: linear-gradient(90deg, var(--primary-color) 0%, #34495e 100%);
            color: white;
            border-radius: 12px 12px 0 0 !important;
            padding: 20px;
            border: none;
        }

        .card-header h5 {
            margin: 0;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            padding: 25px;
        }

        .table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 0;
        }

        .table thead {
            background-color: #f8f9fa;
        }

        .table thead th {
            border: none;
            color: var(--primary-color);
            font-weight: 700;
            padding: 15px;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .table tbody td {
            vertical-align: middle;
            padding: 15px;
            border-bottom: 1px solid #ecf0f1;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .btn-action {
            padding: 8px 15px;
            font-weight: 600;
            border-radius: 6px;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.85rem;
        }

        .badge-role {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-admin {
            background-color: var(--warning-color);
            color: white;
        }

        .badge-pekerja {
            background-color: var(--info-color);
            color: white;
        }

        .modal-content {
            border-radius: 12px;
            border: none;
        }

        .form-control {
            border-radius: 8px;
            padding: 10px 15px;
        }

        .form-select {
            border-radius: 8px;
            padding: 10px 15px;
        }

        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 1.75rem;
            }

            .table-responsive {
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
        <div class="container-lg">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-shop"></i> Mutiara Tani
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">
                            <i class="bi bi-boxes"></i> Produk
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="transactions.php">
                            <i class="bi bi-receipt"></i> Transaksi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="bi bi-graph-up"></i> Laporan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="expenses.php">
                            <i class="bi bi-truck"></i> Supplier
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="users.php">
                            <i class="bi bi-people"></i> Kelola User
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container-lg">
            <h1><i class="bi bi-people"></i> Kelola User</h1>
            <p>Kelola akun pengguna sistem POS</p>
        </div>
    </div>

    <!-- Main Container -->
    <div class="container-lg" style="padding-bottom: 40px;">
        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-info-circle"></i> <?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Add User Button -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4>Daftar User</h4>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-plus-circle"></i> Tambah User
            </button>
        </div>

        <!-- Users Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><i class="bi bi-hash"></i> ID</th>
                                <th><i class="bi bi-person"></i> Username</th>
                                <th><i class="bi bi-shield"></i> Role</th>
                                <th><i class="bi bi-calendar"></i> Dibuat</th>
                                <th style="text-align: center;"><i class="bi bi-gear"></i> Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><strong><?= $user['id'] ?></strong></td>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $user['role'] == 'admin' ? 'admin' : 'pekerja' ?>">
                                            <?= ucfirst($user['role']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                                    <td style="text-align: center;">
                                        <button class="btn-action btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editUserModal" onclick="editUser(<?= $user['id'] ?>, '<?= $user['username'] ?>', '<?= $user['role'] ?>')">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <button class="btn-action btn btn-sm btn-outline-warning me-1" data-bs-toggle="modal" data-bs-target="#changePasswordModal" onclick="changePassword(<?= $user['id'] ?>)">
                                            <i class="bi bi-key"></i> Password
                                        </button>
                                        <?php if ($user['role'] != 'admin'): ?>
                                        <button class="btn-action btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal" onclick="deleteUser(<?= $user['id'] ?>, '<?= $user['username'] ?>')">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Tambah User Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" required>
                                <option value="pekerja">Pekerja</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Tambah User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="editUserId">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" id="editUsername" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" id="editRole" required>
                                <option value="pekerja">Pekerja</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-key"></i> Ubah Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_password">
                        <input type="hidden" name="id" id="passwordUserId">
                        <div class="mb-3">
                            <label class="form-label">Password Baru</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">Ubah Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-trash"></i> Hapus User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="deleteUserId">
                        <p>Apakah Anda yakin ingin menghapus user <strong id="deleteUsername"></strong>?</p>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> Tindakan ini tidak dapat dibatalkan!
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Hapus User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editUser(id, username, role) {
            document.getElementById('editUserId').value = id;
            document.getElementById('editUsername').value = username;
            document.getElementById('editRole').value = role;
        }

        function changePassword(id) {
            document.getElementById('passwordUserId').value = id;
        }

        function deleteUser(id, username) {
            document.getElementById('deleteUserId').value = id;
            document.getElementById('deleteUsername').textContent = username;
        }
    </script>
</body>

</html>
