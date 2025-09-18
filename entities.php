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
  <title>الجهات المعنية</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
</head>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 sidebar p-0">
            <div class="p-3 text-center">
                <h4><i class="fas fa-blog"></i> لوحة التحكم</h4>
            </div>
            <nav class="nav flex-column p-3">
                <a class="nav-link" href="home.php">
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
                <a class="nav-link" href="home.php?logout=true">
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
  <h2 class="mb-4">الجهات المعنية</h2>

  <!-- زر إضافة -->
  <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModal">
    <i class="fas fa-plus"></i> إضافة جهة
  </button>

  <!-- الجدول -->
  <div class="card">
    <div class="card-body">
      <?php if(mysqli_num_rows($result) > 0): ?>
      <table class="table table-hover">
        <thead>
          <tr>
            <th>الاسم</th>
            <th>تاريخ الإضافة</th>
            <th>إجراءات</th>
          </tr>
        </thead>
        <tbody>
          <?php while($row = mysqli_fetch_assoc($result)): ?>
          <tr>
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
