
<?php
require_once 'config.php';

// التحقق من صلاحيات المسؤول
requireAdmin();

// معالجة طلبات الإضافة والحذف والتعديل
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // إضافة مستخدم جديد
    if (isset($_POST['add_user'])) {
        $username = cleanInput($_POST['username']);
        $email = cleanInput($_POST['email']);
        $password = $_POST['password'];
        $role = cleanInput($_POST['role']);

        // تشفير كلمة المرور
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssss", $username, $email, $hashed_password, $role);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['message'] = "تم إضافة المستخدم بنجاح!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "خطأ في إضافة المستخدم: " . mysqli_error($conn);
            $_SESSION['message_type'] = "danger";
        }
    }

    // حذف مستخدم
    if (isset($_POST['delete_user'])) {
        $user_id = cleanInput($_POST['user_id']);

        // لا يمكن حذف المستخدم الحالي
        if ($user_id == $_SESSION['user_id']) {
            $_SESSION['message'] = "لا يمكنك حذف حسابك الحالي!";
            $_SESSION['message_type'] = "danger";
        } else {
            $sql = "DELETE FROM users WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $user_id);

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['message'] = "تم حذف المستخدم بنجاح!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "خطأ في حذف المستخدم: " . mysqli_error($conn);
                $_SESSION['message_type'] = "danger";
            }
        }
    }

    // تعديل مستخدم
    if (isset($_POST['edit_user'])) {
        $user_id = cleanInput($_POST['user_id']);
        $username = cleanInput($_POST['username']);
        $email = cleanInput($_POST['email']);
        $role = cleanInput($_POST['role']);

        $sql = "UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssi", $username, $email, $role, $user_id);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['message'] = "تم تعديل المستخدم بنجاح!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "خطأ في تعديل المستخدم: " . mysqli_error($conn);
            $_SESSION['message_type'] = "danger";
        }
    }

    // تغيير كلمة المرور
    if (isset($_POST['change_password'])) {
        $user_id = cleanInput($_POST['user_id']);
        $new_password = $_POST['new_password'];

        // تشفير كلمة المرور الجديدة
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $sql = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $hashed_password, $user_id);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['message'] = "تم تغيير كلمة المرور بنجاح!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "خطأ في تغيير كلمة المرور: " . mysqli_error($conn);
            $_SESSION['message_type'] = "danger";
        }
    }
}

// استعلام لجلب المستخدمين
$sql = "SELECT * FROM users ORDER BY created_at DESC";
$users_result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المستخدمين والصلاحيات - لوحة تحكم المدونات</title>
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --info-color: #0dcaf0;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }

        .sidebar {
            min-height: 100vh;
            background-color: var(--dark-color);
            color: white;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 5px;
            border-radius: 5px;
            margin-bottom: 5px;
            transition: all 0.3s;
        }

        .sidebar .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .sidebar .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }

        .sidebar .nav-link i {
            margin-left: 10px;
        }

        .top-navbar {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 15px 0;
        }

        .content {
            padding: 20px;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 15px 20px;
            font-weight: 600;
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-left: 10px;
        }

        .user-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .user-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .user-body {
            padding: 20px;
        }

        .role-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 20px;
        }

        .role-admin {
            background-color: var(--danger-color);
        }

        .role-editor {
            background-color: var(--warning-color);
            color: var(--dark-color);
        }

        .role-user {
            background-color: var(--info-color);
            color: var(--dark-color);
        }

        .btn-group-sm > .btn, .btn-sm {
            padding: .25rem .5rem;
            font-size: .875rem;
            border-radius: .2rem;
            margin-left: 5px;
        }

        .form-control, .form-select {
            border-radius: 5px;
        }

        .modal-header {
            background-color: var(--primary-color);
            color: white;
        }

        .modal-header .btn-close {
            filter: invert(1);
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 15px;
        }

        .user-details {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .user-details .info {
            margin-right: 15px;
        }

        .user-details .info h5 {
            margin-bottom: 5px;
        }

        .user-details .info p {
            margin-bottom: 0;
            color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-3 text-center">
                    <h4><i class="fas fa-blog"></i> لوحة التحكم</h4>
                </div>
                <nav class="nav flex-column p-3">
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-home"></i> الرئيسية
                    </a>
                    <a class="nav-link" href="systems.php">
                        <i class="fas fa-gavel"></i> الأنظمة والقوانين
                    </a>
                    <a class="nav-link" href="blogs.php">
                        <i class="fas fa-newspaper"></i> المدونات
                    </a>
                    <a class="nav-link active" href="users.php">
                        <i class="fas fa-users"></i> المستخدمين والصلاحيات
                    </a>
                    <a class="nav-link" href="entities.php">
                      <i class="fas fa-building"></i> الجهات المعنية
                    </a>
                    <a class="nav-link" href="usages.php"><i class="fas fa-cogs"></i> الاستخدامات</a>
                    <a class="nav-link" href="index.php?logout=true">
                        <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                <!-- Top Navbar -->
                <div class="top-navbar">
                    <div class="d-flex justify-content-between align-items-center px-4">
                        <h2>المستخدمين والصلاحيات</h2>
                        <div class="user-info">
                            <span>مرحباً، <?php echo $_SESSION['username']; ?></span>
                            <img src="https://picsum.photos/seed/user<?php echo $_SESSION['user_id']; ?>/40/40.jpg" alt="User Avatar">
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="content">
                    <?php showMessage(); ?>

                    <!-- Add User Button -->
                    <div class="mb-4">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="fas fa-plus"></i> إضافة مستخدم جديد
                        </button>
                    </div>

                    <!-- Users List -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">قائمة المستخدمين</h5>
                        </div>
                        <div class="card-body">
                            <?php if (mysqli_num_rows($users_result) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>المستخدم</th>
                                                <th>البريد الإلكتروني</th>
                                                <th>الصلاحية</th>
                                                <th>تاريخ الإنشاء</th>
                                                <th>الإجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                                                <tr>
                                                    <td>
                                                        <div class="user-details">
                                                            <img src="https://picsum.photos/seed/user<?php echo $user['id']; ?>/40/40.jpg" alt="User Avatar" class="rounded-circle">
                                                            <div class="info">
                                                                <h6><?php echo $user['username']; ?></h6>
                                                                <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                                                    <span class="badge bg-info">أنت</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><?php echo $user['email']; ?></td>
                                                    <td>
                                                        <span class="role-badge role-<?php echo $user['role']; ?>">
                                                            <?php
                                                            switch ($user['role']) {
                                                                case 'admin':
                                                                    echo 'مسؤول';
                                                                    break;
                                                                case 'editor':
                                                                    echo 'محرر';
                                                                    break;
                                                                case 'user':
                                                                    echo 'مستخدم';
                                                                    break;
                                                            }
                                                            ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('Y/m/d H:i', strtotime($user['created_at'])); ?></td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo $user['id']; ?>">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#changePasswordModal<?php echo $user['id']; ?>">
                                                                <i class="fas fa-key"></i>
                                                            </button>
                                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                                <form method="post" style="display: inline;">
                                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                    <button type="submit" name="delete_user" class="btn btn-sm btn-outline-danger" onclick="return confirm('هل أنت متأكد من حذف هذا المستخدم؟');">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>

                                                <!-- Edit User Modal -->
                                                <div class="modal fade" id="editUserModal<?php echo $user['id']; ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">تعديل المستخدم</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form method="post">
                                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                    <div class="mb-3">
                                                                        <label for="edit_username<?php echo $user['id']; ?>" class="form-label">اسم المستخدم</label>
                                                                        <input type="text" class="form-control" id="edit_username<?php echo $user['id']; ?>" name="username" value="<?php echo $user['username']; ?>" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="edit_email<?php echo $user['id']; ?>" class="form-label">البريد الإلكتروني</label>
                                                                        <input type="email" class="form-control" id="edit_email<?php echo $user['id']; ?>" name="email" value="<?php echo $user['email']; ?>" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="edit_role<?php echo $user['id']; ?>" class="form-label">الصلاحية</label>
                                                                        <select class="form-select" id="edit_role<?php echo $user['id']; ?>" name="role" required>
                                                                            <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>مسؤول</option>
                                                                            <option value="editor" <?php echo $user['role'] == 'editor' ? 'selected' : ''; ?>>محرر</option>
                                                                            <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>مستخدم</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="d-grid gap-2">
                                                                        <button type="submit" name="edit_user" class="btn btn-primary">حفظ التغييرات</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Change Password Modal -->
                                                <div class="modal fade" id="changePasswordModal<?php echo $user['id']; ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">تغيير كلمة المرور</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <form method="post">
                                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                    <div class="mb-3">
                                                                        <label for="new_password<?php echo $user['id']; ?>" class="form-label">كلمة المرور الجديدة</label>
                                                                        <input type="password" class="form-control" id="new_password<?php echo $user['id']; ?>" name="new_password" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label for="confirm_password<?php echo $user['id']; ?>" class="form-label">تأكيد كلمة المرور</label>
                                                                        <input type="password" class="form-control" id="confirm_password<?php echo $user['id']; ?>" required>
                                                                        <div class="form-text">كلمة المرور يجب أن تكون 8 أحرف على الأقل.</div>
                                                                    </div>
                                                                    <div class="d-grid gap-2">
                                                                        <button type="submit" name="change_password" class="btn btn-primary">تغيير كلمة المرور</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    لا يوجد مستخدمين حالياً.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">إضافة مستخدم جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post">
                        <div class="mb-3">
                            <label for="username" class="form-label">اسم المستخدم</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">البريد الإلكتروني</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">كلمة المرور</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">كلمة المرور يجب أن تكون 8 أحرف على الأقل.</div>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">الصلاحية</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="admin">مسؤول</option>
                                <option value="editor">محرر</option>
                                <option value="user">مستخدم</option>
                            </select>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" name="add_user" class="btn btn-primary">إضافة المستخدم</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Password Validation Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Validate password confirmation
            const changePasswordForms = document.querySelectorAll('form[name="change_password"]');
            changePasswordForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const newPassword = this.querySelector('input[name="new_password"]').value;
                    const confirmPassword = this.querySelector('input[id^="confirm_password"]').value;

                    if (newPassword !== confirmPassword) {
                        e.preventDefault();
                        alert('كلمة المرور وتأكيدها غير متطابقين!');
                    } else if (newPassword.length < 8) {
                        e.preventDefault();
                        alert('كلمة المرور يجب أن تكون 8 أحرف على الأقل!');
                    }
                });
            });

            // Validate password length for add user form
            const addUserForm = document.querySelector('form[name="add_user"]');
            if (addUserForm) {
                addUserForm.addEventListener('submit', function(e) {
                    const password = this.querySelector('input[name="password"]').value;

                    if (password.length < 8) {
                        e.preventDefault();
                        alert('كلمة المرور يجب أن تكون 8 أحرف على الأقل!');
                    }
                });
            }
        });
    </script>
</body>
</html>
