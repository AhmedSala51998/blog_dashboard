<?php
require_once 'config.php';
requireAdmin();

// إضافة استخدام جديد
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['add_usage'])) {
        $title = cleanInput($_POST['title']);
        $stmt = mysqli_prepare($conn, "INSERT INTO usages (title) VALUES (?)");
        mysqli_stmt_bind_param($stmt, "s", $title);
        mysqli_stmt_execute($stmt);
    }

    // تعديل استخدام
    if (isset($_POST['edit_usage'])) {
        $id = (int)$_POST['id'];
        $title = cleanInput($_POST['title']);
        $stmt = mysqli_prepare($conn, "UPDATE usages SET title=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "si", $title, $id);
        mysqli_stmt_execute($stmt);
    }

    // حذف استخدام
    if (isset($_POST['delete_usage'])) {
        $id = (int)$_POST['id'];
        $stmt = mysqli_prepare($conn, "DELETE FROM usages WHERE id=?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
    }
}

$result = mysqli_query($conn, "SELECT * FROM usages ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>الاستخدامات - لوحة التحكم</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
<body style="background:#f8f9fa;">
<div class="container-fluid">
  <div class="row">
    
    <!-- الشريط الجانبي -->
    <div class="col-md-2 sidebar p-3 bg-dark text-white">
      <h4 class="text-center"><i class="fas fa-blog"></i> لوحة التحكم</h4>
      <nav class="nav flex-column">
        <a class="nav-link text-white" href="index.php"><i class="fas fa-home"></i> الرئيسية</a>
        <a class="nav-link text-white" href="systems.php"><i class="fas fa-gavel"></i> الأنظمة والقوانين</a>
        <a class="nav-link text-white" href="blogs.php"><i class="fas fa-newspaper"></i> المدونات</a>
        <a class="nav-link text-white" href="users.php"><i class="fas fa-users"></i> المستخدمين والصلاحيات</a>
        <a class="nav-link text-white" href="entities.php"><i class="fas fa-building"></i> الجهات المعنية</a>
        <a class="nav-link active bg-primary text-white" href="usages.php"><i class="fas fa-cogs"></i> الاستخدامات</a>
        <a class="nav-link text-white" href="index.php?logout=true"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
      </nav>
    </div>

    <!-- المحتوى الرئيسي -->
    <div class="col-md-10">
      <div class="bg-white shadow-sm p-3 mb-4 d-flex justify-content-between">
        <h2>الاستخدامات</h2>
        <div>مرحباً، <?= $_SESSION['username']; ?></div>
      </div>

      <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="fas fa-plus"></i> إضافة استخدام
      </button>

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
                  <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#edit<?= $row['id']; ?>">تعديل</button>
                  <form method="post" style="display:inline;" onsubmit="return confirm('تأكيد الحذف؟');">
                    <input type="hidden" name="id" value="<?= $row['id']; ?>">
                    <button type="submit" name="delete_usage" class="btn btn-sm btn-outline-danger">حذف</button>
                  </form>
                </td>
              </tr>

              <!-- نافذة تعديل -->
              <div class="modal fade" id="edit<?= $row['id']; ?>" tabindex="-1">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <form method="post">
                      <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">تعديل الاستخدام</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <input type="hidden" name="id" value="<?= $row['id']; ?>">
                        <label class="form-label">اسم الاستخدام</label>
                        <input type="text" name="title" value="<?= htmlspecialchars($row['title']); ?>" class="form-control" required>
                      </div>
                      <div class="modal-footer">
                        <button type="submit" name="edit_usage" class="btn btn-primary">حفظ</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
              <?php endwhile; ?>
            </tbody>
          </table>
          <?php else: ?>
          <div class="alert alert-info">لا توجد استخدامات مضافة.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- نافذة إضافة -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title">إضافة استخدام</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <label class="form-label">اسم الاستخدام</label>
          <input type="text" name="title" class="form-control" required>
        </div>
        <div class="modal-footer">
          <button type="submit" name="add_usage" class="btn btn-primary">حفظ</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
