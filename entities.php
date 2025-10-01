<?php
require_once 'config.php';
requireAdmin();

// إضافة جهة جديدة
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['add_entity'])) {
        $title = cleanInput($_POST['title']);
        $stmt = mysqli_prepare($conn, "INSERT INTO concerned_entities (title) VALUES (?)");
        mysqli_stmt_bind_param($stmt, "s", $title);
        mysqli_stmt_execute($stmt);
    }

    // تعديل جهة
    if (isset($_POST['edit_entity'])) {
        $id = (int)$_POST['id'];
        $title = cleanInput($_POST['title']);
        $stmt = mysqli_prepare($conn, "UPDATE concerned_entities SET title=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "si", $title, $id);
        mysqli_stmt_execute($stmt);
    }

    // حذف جهة
    if (isset($_POST['delete_entity'])) {
        $id = (int)$_POST['id'];
        $stmt = mysqli_prepare($conn, "DELETE FROM concerned_entities WHERE id=?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
    }
}

$result = mysqli_query($conn, "SELECT * FROM concerned_entities ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الجهات المعنيه - لوحة تحكم المدونات</title>
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
                <a class="nav-link" href="users.php">
                    <i class="fas fa-users"></i> المستخدمين والصلاحيات
                </a>
                <a class="nav-link active" href="entities.php">
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
                    <h2>الجهات المعنيه</h2>
                    <div class="user-info">
                        <span>مرحباً، <?php echo $_SESSION['username']; ?></span>
                        <img src="https://picsum.photos/seed/user<?php echo $_SESSION['user_id']; ?>/40/40.jpg" alt="User Avatar">
                    </div>
                </div>
            </div>
<div class="content">
        <?php showMessage(); ?>

        <!-- Add User Button -->
        <div class="mb-4">
                <!-- زر إضافة -->
            <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="fas fa-plus"></i> إضافة جهة
            </button>
        </div>


  <!-- الجدول -->
  <div class="card">
    <div class="card-body">
      <?php if(mysqli_num_rows($result) > 0): ?>
      <table class="table table-hover">
        <thead>
          <tr>
            <th>رقم المعرف</th>
            <th>الاسم</th>
            <th>تاريخ الإضافة</th>
            <th>إجراءات</th>
          </tr>
        </thead>
        <tbody>
          <?php while($row = mysqli_fetch_assoc($result)): ?>
          <tr>
           <td><?= htmlspecialchars($row['id']); ?></td>
            <td><?= htmlspecialchars($row['title']); ?></td>
            <td><?= date('Y/m/d H:i', strtotime($row['created_at'])); ?></td>
            <td>
              <!-- تعديل -->
              <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#edit<?= $row['id']; ?>">تعديل</button>
              <!-- حذف -->
              <form method="post" style="display:inline;" onsubmit="return confirm('تأكيد الحذف؟');">
                <input type="hidden" name="id" value="<?= $row['id']; ?>">
                <button type="submit" name="delete_entity" class="btn btn-sm btn-outline-danger">حذف</button>
              </form>
            </td>
          </tr>

          <!-- نافذة تعديل -->
          <div class="modal fade" id="edit<?= $row['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
              <div class="modal-content">
                <form method="post">
                  <div class="modal-header">
                    <h5 class="modal-title">تعديل الجهة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <input type="hidden" name="id" value="<?= $row['id']; ?>">
                    <label class="form-label">اسم الجهة</label>
                    <input type="text" name="title" value="<?= htmlspecialchars($row['title']); ?>" class="form-control" required>
                  </div>
                  <div class="modal-footer">
                    <button type="submit" name="edit_entity" class="btn btn-primary">حفظ</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
          <?php endwhile; ?>
        </tbody>
      </table>
      <?php else: ?>
        <div class="alert alert-info">لا توجد جهات مضافة.</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- نافذة إضافة -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header">
          <h5 class="modal-title">إضافة جهة</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <label class="form-label">اسم الجهة</label>
          <input type="text" name="title" class="form-control" required>
        </div>
        <div class="modal-footer">
          <button type="submit" name="add_entity" class="btn btn-primary">حفظ</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>
</html>
