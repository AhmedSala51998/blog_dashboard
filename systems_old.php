
<?php
require_once 'config.php';

// التحقق من تسجيل دخول المستخدم
requireLogin();

// معالجة طلبات الإضافة والحذف والتعديل
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // إضافة نظام جديد
    if (isset($_POST['add_system'])) {
        $title = cleanInput($_POST['system_title']);
        $description = cleanInput($_POST['system_description']);

        $sql = "INSERT INTO systems (title, description) VALUES (?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $title, $description);

        if (mysqli_stmt_execute($stmt)) {
            $system_id = mysqli_insert_id($conn);
            $_SESSION['message'] = "تم إضافة النظام بنجاح!";
            $_SESSION['message_type'] = "success";

            // معالجة المواد القانونية
            if (isset($_POST['articles']) && is_array($_POST['articles'])) {
                foreach ($_POST['articles'] as $article) {
                    if (!empty($article['title'])) {
                        $article_title = cleanInput($article['title']);
                        $article_content = cleanInput($article['content']);

                        $sql = "INSERT INTO articles (system_id, title, content) VALUES (?, ?, ?)";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "iss", $system_id, $article_title, $article_content);
                        mysqli_stmt_execute($stmt);

                        $article_id = mysqli_insert_id($conn);

                        // معالجة الأجزاء داخل المادة
                        if (isset($article['sections']) && is_array($article['sections'])) {
                            foreach ($article['sections'] as $section) {
                                if (!empty($section['title'])) {
                                    $section_title = cleanInput($section['title']);
                                    $section_content = cleanInput($section['content']);

                                    $sql = "INSERT INTO sections (article_id, title, content) VALUES (?, ?, ?)";
                                    $stmt = mysqli_prepare($conn, $sql);
                                    mysqli_stmt_bind_param($stmt, "iss", $article_id, $section_title, $section_content);
                                    mysqli_stmt_execute($stmt);
                                }
                            }
                        }
                    }
                }
            }
        } else {
            $_SESSION['message'] = "خطأ في إضافة النظام: " . mysqli_error($conn);
            $_SESSION['message_type'] = "danger";
        }
    }

    // حذف نظام
    if (isset($_POST['delete_system'])) {
        $system_id = cleanInput($_POST['system_id']);

        $sql = "DELETE FROM systems WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $system_id);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['message'] = "تم حذف النظام بنجاح!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "خطأ في حذف النظام: " . mysqli_error($conn);
            $_SESSION['message_type'] = "danger";
        }
    }

    // تعديل نظام
    if (isset($_POST['edit_system'])) {
        $system_id = cleanInput($_POST['system_id']);
        $title = cleanInput($_POST['system_title']);
        $description = cleanInput($_POST['system_description']);

        $sql = "UPDATE systems SET title = ?, description = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssi", $title, $description, $system_id);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['message'] = "تم تعديل النظام بنجاح!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "خطأ في تعديل النظام: " . mysqli_error($conn);
            $_SESSION['message_type'] = "danger";
        }
    }

    // حذف مادة
    if (isset($_POST['delete_article'])) {
        $article_id = cleanInput($_POST['article_id']);

        $sql = "DELETE FROM articles WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $article_id);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['message'] = "تم حذف المادة بنجاح!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "خطأ في حذف المادة: " . mysqli_error($conn);
            $_SESSION['message_type'] = "danger";
        }
    }

    // تعديل مادة
    if (isset($_POST['edit_article'])) {
        $article_id = cleanInput($_POST['article_id']);
        $title = cleanInput($_POST['article_title']);
        $content = cleanInput($_POST['article_content']);

        $sql = "UPDATE articles SET title = ?, content = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssi", $title, $content, $article_id);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['message'] = "تم تعديل المادة بنجاح!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "خطأ في تعديل المادة: " . mysqli_error($conn);
            $_SESSION['message_type'] = "danger";
        }
    }

    // حذف جزء
    if (isset($_POST['delete_section'])) {
        $section_id = cleanInput($_POST['section_id']);

        $sql = "DELETE FROM sections WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $section_id);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['message'] = "تم حذف الجزء بنجاح!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "خطأ في حذف الجزء: " . mysqli_error($conn);
            $_SESSION['message_type'] = "danger";
        }
    }

    // تعديل جزء
    if (isset($_POST['edit_section'])) {
        $section_id = cleanInput($_POST['section_id']);
        $title = cleanInput($_POST['section_title']);
        $content = cleanInput($_POST['section_content']);

        $sql = "UPDATE sections SET title = ?, content = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssi", $title, $content, $section_id);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['message'] = "تم تعديل الجزء بنجاح!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "خطأ في تعديل الجزء: " . mysqli_error($conn);
            $_SESSION['message_type'] = "danger";
        }
    }
    //************************ */
    // إضافة مادة قانونية مستقلة لنظام معين
    if (isset($_POST['add_article'])) {
        $system_id = cleanInput($_POST['system_id']);
        $title = cleanInput($_POST['article_title']);
        $content = cleanInput($_POST['article_content']);

        $sql = "INSERT INTO articles (system_id, title, content) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iss", $system_id, $title, $content);

        if (mysqli_stmt_execute($stmt)) {
            $article_id = mysqli_insert_id($conn);

            if (!empty($_POST['sections_title']) && !empty($_POST['sections_content'])) {
                $section_sql = "INSERT INTO sections (article_id, title, content) VALUES (?, ?, ?)";
                $section_stmt = mysqli_prepare($conn, $section_sql);

                foreach ($_POST['sections_title'] as $i => $sec_title) {
                    $sec_title = cleanInput($sec_title);
                    $sec_content = cleanInput($_POST['sections_content'][$i]);
                    mysqli_stmt_bind_param($section_stmt, "iss", $article_id, $sec_title, $sec_content);
                    mysqli_stmt_execute($section_stmt);
                }
            }

            $_SESSION['message'] = "تمت إضافة المادة والأجزاء بنجاح";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "خطأ أثناء إضافة المادة: " . mysqli_error($conn);
            $_SESSION['message_type'] = "danger";
        }
    }

}

// استعلام لجلب الأنظمة والقوانين
$sql = "SELECT * FROM systems ORDER BY created_at DESC";
$systems_result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الأنظمة والقوانين - لوحة تحكم المدونات</title>
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
            padding: 12px 20px;
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

        .system-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .system-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .system-body {
            padding: 20px;
        }

        .article-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border-right: 4px solid var(--primary-color);
        }

        .section-card {
            background-color: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            border-right: 4px solid var(--info-color);
            margin-right: 20px;
        }

        .btn-group-sm > .btn, .btn-sm {
            padding: .25rem .5rem;
            font-size: .875rem;
            border-radius: .2rem;
            margin-left: 5px;
        }

        .add-article-btn, .add-section-btn {
            margin-top: 10px;
            margin-bottom: 10px;
        }

        .article-form, .section-form {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            display: none;
        }

        .article-form.active, .section-form.active {
            display: block;
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
                    <a class="nav-link active" href="systems.php">
                        <i class="fas fa-gavel"></i> الأنظمة والقوانين
                    </a>
                    <a class="nav-link" href="blogs.php">
                        <i class="fas fa-newspaper"></i> المدونات
                    </a>
                    <?php if (isAdmin()): ?>
                    <a class="nav-link" href="users.php">
                        <i class="fas fa-users"></i> المستخدمين والصلاحيات
                    </a>
                    <?php endif; ?>
                    <a class="nav-link" href="entities.php">
                      <i class="fas fa-building"></i> الجهات المعنية
                    </a>
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
                        <h2>الأنظمة والقوانين</h2>
                        <div class="user-info">
                            <span>مرحباً، <?php echo $_SESSION['username']; ?></span>
                            <img src="https://picsum.photos/seed/user<?php echo $_SESSION['user_id']; ?>/40/40.jpg" alt="User Avatar">
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="content">
                    <?php showMessage(); ?>

                    <!-- Add System Button -->
                    <div class="mb-4">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSystemModal">
                            <i class="fas fa-plus"></i> إضافة نظام جديد
                        </button>
                    </div>

                    <!-- Systems List -->
                    <?php if (mysqli_num_rows($systems_result) > 0): ?>
                        <?php while ($system = mysqli_fetch_assoc($systems_result)): ?>
                            <div class="system-card">
                                <div class="system-header">
                                    <h4 class="mb-0"><?php echo $system['title']; ?></h4>
                                    <div>
                                        <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#editSystemModal<?php echo $system['id']; ?>">
                                            <i class="fas fa-edit"></i> تعديل
                                        </button>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="system_id" value="<?php echo $system['id']; ?>">
                                            <button type="submit" name="delete_system" class="btn btn-danger btn-sm" onclick="return confirm('هل أنت متأكد من حذف هذا النظام؟')">
                                                <i class="fas fa-trash"></i> حذف
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div class="system-body">
                                    <p><?php echo nl2br($system['description']); ?></p>
                                    <small class="text-muted">تم الإنشاء: <?php echo date('Y/m/d H:i', strtotime($system['created_at'])); ?></small>

                                    <!-- Articles -->
                                    <div class="mt-4">
                                        <h5>المواد القانونية</h5>

                                        <?php
                                        $sql = "SELECT * FROM articles WHERE system_id = ? ORDER BY id ASC";
                                        $stmt = mysqli_prepare($conn, $sql);
                                        mysqli_stmt_bind_param($stmt, "i", $system['id']);
                                        mysqli_stmt_execute($stmt);
                                        $articles_result = mysqli_stmt_get_result($stmt);

                                        if (mysqli_num_rows($articles_result) > 0):
                                            while ($article = mysqli_fetch_assoc($articles_result)):
                                        ?>
                                            <div class="article-card">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6><?php echo $article['title']; ?></h6>
                                                        <p><?php echo nl2br(substr($article['content'], 0, 200)) . (strlen($article['content']) > 200 ? '...' : ''); ?></p>
                                                    </div>
                                                    <div>
                                                        <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#editArticleModal<?php echo $article['id']; ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <form method="post" style="display: inline;">
                                                            <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                                                            <button type="submit" name="delete_article" class="btn btn-danger btn-sm" onclick="return confirm('هل أنت متأكد من حذف هذه المادة؟')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>

                                                <!-- Sections -->
                                                <?php
                                                $sql = "SELECT * FROM sections WHERE article_id = ? ORDER BY id ASC";
                                                $stmt = mysqli_prepare($conn, $sql);
                                                mysqli_stmt_bind_param($stmt, "i", $article['id']);
                                                mysqli_stmt_execute($stmt);
                                                $sections_result = mysqli_stmt_get_result($stmt);

                                                if (mysqli_num_rows($sections_result) > 0):
                                                    while ($section = mysqli_fetch_assoc($sections_result)):
                                                ?>
                                                    <div class="section-card">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div>
                                                                <h6><?php echo $section['title']; ?></h6>
                                                                <p><?php echo nl2br(substr($section['content'], 0, 150)) . (strlen($section['content']) > 150 ? '...' : ''); ?></p>
                                                            </div>
                                                            <div>
                                                                <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#editSectionModal<?php echo $section['id']; ?>">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <form method="post" style="display: inline;">
                                                                    <input type="hidden" name="section_id" value="<?php echo $section['id']; ?>">
                                                                    <button type="submit" name="delete_section" class="btn btn-danger btn-sm" onclick="return confirm('هل أنت متأكد من حذف هذا الجزء؟')">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php
                                                    endwhile;
                                                endif;
                                                ?>
                                            </div>
                                        <?php
                                            endwhile;
                                        else:
                                        ?>
                                            <p class="text-muted">لا توجد مواد قانونية لهذا النظام.</p>
                                        <?php endif; ?>

                                        <button type="button" class="btn btn-outline-primary add-article-btn" data-bs-toggle="modal" data-bs-target="#addArticleModal<?php echo $system['id']; ?>">
                                            <i class="fas fa-plus"></i> إضافة مادة قانونية
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Edit System Modal -->
                            <div class="modal fade" id="editSystemModal<?php echo $system['id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">تعديل النظام</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form method="post">
                                            <div class="modal-body">
                                                <input type="hidden" name="system_id" value="<?php echo $system['id']; ?>">
                                                <div class="mb-3">
                                                    <label for="system_title<?php echo $system['id']; ?>" class="form-label">عنوان النظام</label>
                                                    <input type="text" class="form-control" id="system_title<?php echo $system['id']; ?>" name="system_title" value="<?php echo $system['title']; ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="system_description<?php echo $system['id']; ?>" class="form-label">وصف النظام</label>
                                                    <textarea class="form-control" id="system_description<?php echo $system['id']; ?>" name="system_description" rows="4"><?php echo $system['description']; ?></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                                <button type="submit" name="edit_system" class="btn btn-primary">حفظ التغييرات</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Add Article Modal -->
                            <div class="modal fade" id="addArticleModal<?php echo $system['id']; ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">إضافة مادة قانونية</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form method="post">
                                            <div class="modal-body">
                                                <input type="hidden" name="system_id" value="<?php echo $system['id']; ?>">
                                                <div class="mb-3">
                                                    <label for="article_title<?php echo $system['id']; ?>" class="form-label">عنوان المادة</label>
                                                    <input type="text" class="form-control" id="article_title<?php echo $system['id']; ?>" name="article_title" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="article_content<?php echo $system['id']; ?>" class="form-label">محتوى المادة</label>
                                                    <textarea class="form-control" id="article_content<?php echo $system['id']; ?>" name="article_content" rows="4"></textarea>
                                                </div>

                                                <div class="mb-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <label class="form-label mb-0">الأجزاء</label>
                                                        <button type="button" class="btn btn-sm btn-outline-primary add-section-btn" data-system="<?php echo $system['id']; ?>">
                                                            <i class="fas fa-plus"></i> إضافة جزء
                                                        </button>
                                                    </div>
                                                    <div id="sections-container-<?php echo $system['id']; ?>">
                                                        <!-- Sections will be added here dynamically -->
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                                <button type="submit" name="add_article" class="btn btn-primary">إضافة المادة</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            لا توجد أنظمة أو قوانين مضافة بعد.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add System Modal -->
    <div class="modal fade" id="addSystemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">إضافة نظام جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" id="addSystemForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="system_title" class="form-label">عنوان النظام</label>
                            <input type="text" class="form-control" id="system_title" name="system_title" required>
                        </div>
                        <div class="mb-3">
                            <label for="system_description" class="form-label">وصف النظام</label>
                            <textarea class="form-control" id="system_description" name="system_description" rows="4"></textarea>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">المواد القانونية</label>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="addArticleBtn">
                                    <i class="fas fa-plus"></i> إضافة مادة
                                </button>
                            </div>
                            <div id="articles-container">
                                <!-- Articles will be added here dynamically -->
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" name="add_system" class="btn btn-primary">إضافة النظام</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Article Modal -->
    <?php
    $sql = "SELECT * FROM articles";
    $articles_result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($articles_result) > 0):
        while ($article = mysqli_fetch_assoc($articles_result)):
    ?>
        <div class="modal fade" id="editArticleModal<?php echo $article['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">تعديل المادة</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="post">
                        <div class="modal-body">
                            <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                            <div class="mb-3">
                                <label for="article_title<?php echo $article['id']; ?>" class="form-label">عنوان المادة</label>
                                <input type="text" class="form-control" id="article_title<?php echo $article['id']; ?>" name="article_title" value="<?php echo $article['title']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="article_content<?php echo $article['id']; ?>" class="form-label">محتوى المادة</label>
                                <textarea class="form-control" id="article_content<?php echo $article['id']; ?>" name="article_content" rows="4"><?php echo $article['content']; ?></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <button type="submit" name="edit_article" class="btn btn-primary">حفظ التغييرات</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php
        endwhile;
    endif;
    ?>

    <!-- Edit Section Modal -->
    <?php
    $sql = "SELECT * FROM sections";
    $sections_result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($sections_result) > 0):
        while ($section = mysqli_fetch_assoc($sections_result)):
    ?>
        <div class="modal fade" id="editSectionModal<?php echo $section['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">تعديل الجزء</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="post">
                        <div class="modal-body">
                            <input type="hidden" name="section_id" value="<?php echo $section['id']; ?>">
                            <div class="mb-3">
                                <label for="section_title<?php echo $section['id']; ?>" class="form-label">عنوان الجزء</label>
                                <input type="text" class="form-control" id="section_title<?php echo $section['id']; ?>" name="section_title" value="<?php echo $section['title']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="section_content<?php echo $section['id']; ?>" class="form-label">محتوى الجزء</label>
                                <textarea class="form-control" id="section_content<?php echo $section['id']; ?>" name="section_content" rows="4"><?php echo $section['content']; ?></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <button type="submit" name="edit_section" class="btn btn-primary">حفظ التغييرات</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php
        endwhile;
    endif;
    ?>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        $(document).ready(function() {
            let articleCount = 0;
            let sectionCount = {};

            // Add Article Button Click
            $('#addArticleBtn').click(function() {
                articleCount++;
                let articleHtml = `
                    <div class="article-form active" id="article-${articleCount}">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6>مادة ${articleCount}</h6>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-article" data-article="${articleCount}">
                                <i class="fas fa-times"></i> إزالة
                            </button>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">عنوان المادة</label>
                            <input type="text" class="form-control" name="articles[${articleCount}][title]" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">محتوى المادة</label>
                            <textarea class="form-control" name="articles[${articleCount}][content]" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">الأجزاء</label>
                                <button type="button" class="btn btn-sm btn-outline-primary add-section-btn" data-article="${articleCount}">
                                    <i class="fas fa-plus"></i> إضافة جزء
                                </button>
                            </div>
                            <div id="sections-container-${articleCount}">
                                <!-- Sections will be added here dynamically -->
                            </div>
                        </div>
                    </div>
                `;

                $('#articles-container').append(articleHtml);
                sectionCount[articleCount] = 0;
            });

            // Remove Article Button Click
            $(document).on('click', '.remove-article', function() {
                let articleId = $(this).data('article');
                $(`#article-${articleId}`).remove();
            });

            // Add Section Button Click
            $(document).on('click', '.add-section-btn', function() {
                let articleId = $(this).data('article');
                if (!sectionCount[articleId]) {
                    sectionCount[articleId] = 0;
                }
                console.log(sectionCount[articleId]);
                sectionCount[articleId]++;

                let sectionHtml = `
                    <div class="section-form active" id="section-${articleId}-${sectionCount[articleId]}">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6>جزء ${sectionCount[articleId]}</h6>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-section" data-article="${articleId}" data-section="${sectionCount[articleId]}">
                                <i class="fas fa-times"></i> إزالة
                            </button>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">عنوان الجزء</label>
                            <input type="text" class="form-control" name="articles[${articleId}][sections][${sectionCount[articleId]}][title]">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">محتوى الجزء</label>
                            <textarea class="form-control" name="articles[${articleId}][sections][${sectionCount[articleId]}][content]" rows="3"></textarea>
                        </div>
                    </div>
                `;

                $(`#sections-container-${articleId}`).append(sectionHtml);
                console.log("Added section to article:", articleId, "HTML:", sectionHtml);
            });

            // Remove Section Button Click
            $(document).on('click', '.remove-section', function() {
                let articleId = $(this).data('article');
                let sectionId = $(this).data('section');
                $(`#section-${articleId}-${sectionId}`).remove();
            });
        });
    </script>
   <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.add-section-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const systemId = btn.dataset.system;
                    const container = document.getElementById(`sections-container-${systemId}`);

                    const index = container.querySelectorAll('.section-item').length + 1;

                    const div = document.createElement('div');
                    div.className = 'section-item mb-2 input-group';
                    div.innerHTML = `
                        <span class="input-group-text">${index}</span>
                        <!-- العنوان (الرقم) مخزن في حقل مخفي -->
                        <input type="hidden" name="sections_title[]" value="${index}">
                        <input type="text" name="sections_content[]" class="form-control" placeholder="نص الجزء ${index}" required>
                        <button type="button" class="btn btn-danger remove-section">×</button>
                    `;
                    div.querySelector('.remove-section').addEventListener('click', () => div.remove());
                    container.appendChild(div);
                });
            });
        });
    </script>

</body>
</html>