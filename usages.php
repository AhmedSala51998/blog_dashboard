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
</head>
<body style="background:#f8f9fa;">
<div class="container-fluid">
  <div class="row">
    <!-- الشريط الجانبي -->
    <div class="col-md-2 sidebar p-3 bg-dark text-white">
      <h4 class="text-center"><i class="fas fa-blog"></i> لوحة التحكم</h4>
      <nav class="nav flex-column">
        <a class="nav-link text-white" href="index.php"><i class="fas fa-home"></i> الرئيسية</a>
        <a class="nav-link text-white" href="systems.php"><i class="fas fa-gavel"></i> الأنظمة</a>
        <a class="nav-link text-white" href="blogs.php"><i class="fas fa-newspaper"></i> المدونات</a>
        <a class="nav-link text-white" href="users.php"><i class="fas fa-users"></i> المستخدمين</a>
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
