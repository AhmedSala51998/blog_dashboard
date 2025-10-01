
<?php
require_once 'config.php';

// التحقق من تسجيل دخول المستخدم
requireLogin();

// دالة للحصول على نظام بواسطة المعرف
function getReferenceSystemById($id) {
    global $conn;
    $id = cleanInput($id);
    $sql = "SELECT * FROM systems WHERE id = $id";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

// دالة للحصول على مادة بواسطة المعرف
function getReferenceArticleById($id) {
    global $conn;
    $id = cleanInput($id);
    $sql = "SELECT * FROM articles WHERE id = $id";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

// دالة للحصول على جزء بواسطة المعرف
function getReferenceSectionById($id) {
    global $conn;
    $id = cleanInput($id);
    $sql = "SELECT * FROM sections WHERE parent_id = $id";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

function getReferenceSubSectionById($id) {
    global $conn;
    $id = cleanInput($id);
    $sql = "SELECT * FROM sections WHERE id = $id";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

// معالجة طلبات الإضافة والحذف والتعديل
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // إضافة مدونة جديدة
    if (isset($_POST['add_blog'])) {
        $title = cleanInput($_POST['blog_title']);
        $content = cleanInput($_POST['blog_content']);
        $video_url = cleanInput($_POST['video_url']);
        $external_link = cleanInput($_POST['external_link']);
        $reference_system_id = !empty($_POST['reference_system_id']) ? implode(',', array_map('cleanInput', $_POST['reference_system_id'])) : null;
        $reference_article_id = !empty($_POST['reference_article_id']) ? implode(',', array_map('cleanInput', $_POST['reference_article_id'])) : null;
        $reference_section_id = !empty($_POST['reference_section_id']) ? implode(',', array_map('cleanInput', $_POST['reference_section_id'])) : null;
        $reference_subsection_id = !empty($_POST['reference_subsection_id']) ? implode(',', array_map('cleanInput', $_POST['reference_subsection_id'])) : null;

        // معالجة رفع الصورة
        $image_url = '';
        if (isset($_FILES['blog_image']) && $_FILES['blog_image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['blog_image']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);

            if (in_array(strtolower($filetype), $allowed)) {
                $new_filename = 'blog_' . time() . '.' . $filetype;
                $upload_path = 'uploads/images/' . $new_filename;

                if (!is_dir('uploads/images')) {
                    mkdir('uploads/images', 0777, true);
                }

                if (move_uploaded_file($_FILES['blog_image']['tmp_name'], $upload_path)) {
                    $image_url = $upload_path;
                }
            }
        }

        // معالجة رفع ملف PDF
        $pdf_path = '';
        if (isset($_FILES['blog_pdf']) && $_FILES['blog_pdf']['error'] == 0) {
            $allowed = ['pdf'];
            $filename = $_FILES['blog_pdf']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);

            if (in_array(strtolower($filetype), $allowed)) {
                $new_filename = 'blog_' . time() . '.' . $filetype;
                $upload_path = 'uploads/pdfs/' . $new_filename;

                if (!is_dir('uploads/pdfs')) {
                    mkdir('uploads/pdfs', 0777, true);
                }

                if (move_uploaded_file($_FILES['blog_pdf']['tmp_name'], $upload_path)) {
                    $pdf_path = $upload_path;

                    // استخراج النص من ملف PDF
                    require_once 'vendor/autoload.php'; // سنقوم بإنشاء هذا الملف لاحقاً
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf = $parser->parseFile($upload_path);
                    $pdf_text = $pdf->getText();

                    // إذا كان هناك نص مستخرج، أضفه إلى محتوى المدونة
                    if (!empty($pdf_text)) {
                        $content .= "\n\n--- محتوى مستخرج من ملف PDF ---\n" . $pdf_text;
                    }
                }
            }
        }

        $sql = "INSERT INTO blogs (title, content, pdf_path, video_url, image_url, external_link, reference_system_id, reference_article_id, reference_section_id , reference_subsection_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ? , ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssssssss", $title, $content, $pdf_path, $video_url, $image_url, $external_link, $reference_system_id, $reference_article_id, $reference_section_id , $reference_subsection_id);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['message'] = "تم إضافة المدونة بنجاح!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "خطأ في إضافة المدونة: " . mysqli_error($conn);
            $_SESSION['message_type'] = "danger";
        }
    }

    // حذف مدونة
    if (isset($_POST['delete_blog'])) {
        $blog_id = cleanInput($_POST['blog_id']);

        // الحصول على معلومات المدونة لحذف الملفات المرتبطة بها
        $sql = "SELECT pdf_path, image_url FROM blogs WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $blog_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $blog = mysqli_fetch_assoc($result);

        // حذف ملف PDF إذا وجد
        if (!empty($blog['pdf_path']) && file_exists($blog['pdf_path'])) {
            unlink($blog['pdf_path']);
        }

        // حذف الصورة إذا وجدت
        if (!empty($blog['image_url']) && file_exists($blog['image_url'])) {
            unlink($blog['image_url']);
        }

        $sql = "DELETE FROM blogs WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $blog_id);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['message'] = "تم حذف المدونة بنجاح!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "خطأ في حذف المدونة: " . mysqli_error($conn);
            $_SESSION['message_type'] = "danger";
        }
    }

    // تعديل مدونة
    if (isset($_POST['edit_blog'])) {
        $blog_id = cleanInput($_POST['blog_id']);
        $title = cleanInput($_POST['blog_title']);
        $content = cleanInput($_POST['blog_content']);
        $video_url = cleanInput($_POST['video_url']);
        $external_link = cleanInput($_POST['external_link']);
        $reference_system_id = !empty($_POST['reference_system_id']) ? implode(',', array_map('cleanInput', $_POST['reference_system_id'])) : null;
        $reference_article_id = !empty($_POST['reference_article_id']) ? implode(',', array_map('cleanInput', $_POST['reference_article_id'])) : null;
        $reference_section_id = !empty($_POST['reference_section_id']) ? implode(',', array_map('cleanInput', $_POST['reference_section_id'])) : null;
        $reference_subsection_id = !empty($_POST['reference_subsection_id']) ? implode(',', array_map('cleanInput', $_POST['reference_subsection_id'])) : null;

        // الحصول على معلومات المدونة الحالية
        $sql = "SELECT * FROM blogs WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $blog_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $blog = mysqli_fetch_assoc($result);

        // معالجة رفع الصورة الجديدة
        $image_url = $blog['image_url'];
        if (isset($_FILES['blog_image']) && $_FILES['blog_image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['blog_image']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);

            if (in_array(strtolower($filetype), $allowed)) {
                // حذف الصورة القديمة إذا وجدت
                if (!empty($image_url) && file_exists($image_url)) {
                    unlink($image_url);
                }

                $new_filename = 'blog_' . time() . '.' . $filetype;
                $upload_path = 'uploads/images/' . $new_filename;

                if (!is_dir('uploads/images')) {
                    mkdir('uploads/images', 0777, true);
                }

                if (move_uploaded_file($_FILES['blog_image']['tmp_name'], $upload_path)) {
                    $image_url = $upload_path;
                }
            }
        }

        // معالجة رفع ملف PDF الجديد
        $pdf_path = $blog['pdf_path'];
        if (isset($_FILES['blog_pdf']) && $_FILES['blog_pdf']['error'] == 0) {
            $allowed = ['pdf'];
            $filename = $_FILES['blog_pdf']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);

            if (in_array(strtolower($filetype), $allowed)) {
                // حذف ملف PDF القديم إذا وجد
                if (!empty($pdf_path) && file_exists($pdf_path)) {
                    unlink($pdf_path);
                }

                $new_filename = 'blog_' . time() . '.' . $filetype;
                $upload_path = 'uploads/pdfs/' . $new_filename;

                if (!is_dir('uploads/pdfs')) {
                    mkdir('uploads/pdfs', 0777, true);
                }

                if (move_uploaded_file($_FILES['blog_pdf']['tmp_name'], $upload_path)) {
                    $pdf_path = $upload_path;

                    // استخراج النص من ملف PDF
                    require_once 'vendor/autoload.php';
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf = $parser->parseFile($upload_path);
                    $pdf_text = $pdf->getText();

                    // إذا كان هناك نص مستخرج، أضفه إلى محتوى المدونة
                    if (!empty($pdf_text)) {
                        $content .= "\n\n--- محتوى مستخرج من ملف PDF ---\n" . $pdf_text;
                    }
                }
            }
        }

        $sql = "UPDATE blogs SET title = ?, content = ?, pdf_path = ?, video_url = ?, image_url = ?, external_link = ?, 
                reference_system_id = ?, reference_article_id = ?, reference_section_id = ? , reference_subsection_id = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssssssssi", $title, $content, $pdf_path, $video_url, $image_url, $external_link, 
                              $reference_system_id, $reference_article_id, $reference_section_id , $reference_subsection_id , $blog_id);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['message'] = "تم تعديل المدونة بنجاح!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "خطأ في تعديل المدونة: " . mysqli_error($conn);
            $_SESSION['message_type'] = "danger";
        }
    }

    // الحصول على المواد عند اختيار نظام
    if (isset($_POST['get_articles'])) {
        $system_ids = array_map('intval', $_POST['system_ids']);
        $system_ids_str = implode(',', $system_ids);

        $sql = "SELECT a.id, a.title, s.title AS system_title
                FROM articles a
                INNER JOIN systems s ON a.system_id = s.id
                WHERE a.system_id IN ($system_ids_str)
                ORDER BY a.title";
        $result = mysqli_query($conn, $sql);

        $articles = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $row['title_full'] = $row['title'] . " — " . $row['system_title'];
            $articles[] = $row;
        }

        echo json_encode($articles);
        exit();
    }


    // الحصول على الأجزاء عند اختيار مادة
    if (isset($_POST['get_sections'])) {
        $article_ids = array_map('intval', $_POST['article_ids']);
        $article_ids_str = implode(',', $article_ids);

        $sql = "SELECT sec.id, sec.title, a.title AS article_title, s.title AS system_title
                FROM sections sec
                INNER JOIN articles a ON sec.article_id = a.id
                INNER JOIN systems s ON a.system_id = s.id
                WHERE sec.article_id IN ($article_ids_str)
                ORDER BY sec.title";
        $result = mysqli_query($conn, $sql);

        $sections = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $row['title_full'] = $row['title'] . " — " . $row['article_title'] . " — " . $row['system_title'];
            $sections[] = $row;
        }

        echo json_encode($sections);
        exit();
    }


    // الحصول على الأجزاء الفرعية عند اختيار جزء
    if (isset($_POST['get_subsections'])) {
        $section_ids = array_map('intval', $_POST['section_ids']);
        $section_ids_str = implode(',', $section_ids);

        $sql = "SELECT sub.id, sub.title, parent.title AS parent_title, 
                    a.title AS article_title, s.title AS system_title
                FROM sections sub
                INNER JOIN sections parent ON sub.parent_id = parent.id
                INNER JOIN articles a ON parent.article_id = a.id
                INNER JOIN systems s ON a.system_id = s.id
                WHERE sub.parent_id IN ($section_ids_str)
                ORDER BY sub.title";
        $result = mysqli_query($conn, $sql);

        $subsections = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $row['title_full'] = $row['title'] . " — " . $row['parent_title'] . " — " . $row['article_title'] . " — " . $row['system_title'];
            $subsections[] = $row;
        }

        echo json_encode($subsections);
        exit();
    }

}

// استعلام لجلب المدونات
$sql = "SELECT b.*, s.title as system_title, a.title as article_title, sec.title as section_title 
        FROM blogs b 
        LEFT JOIN systems s ON b.reference_system_id = s.id 
        LEFT JOIN articles a ON b.reference_article_id = a.id 
        LEFT JOIN sections sec ON b.reference_section_id = sec.id 
        ORDER BY b.created_at DESC";
$blogs_result = mysqli_query($conn, $sql);

// استعلام لجلب الأنظمة والقوانين للاستدلال
$systems_sql = "SELECT id, title FROM systems ORDER BY title";
$systems_result = mysqli_query($conn, $systems_sql);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المدونات - لوحة تحكم المدونات</title>
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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

        .blog-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
            transition: transform 0.3s;
        }

        .blog-card:hover {
            transform: translateY(-5px);
        }

        .blog-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .blog-body {
            padding: 20px;
        }

        .blog-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }

        .blog-meta-item {
            background-color: #f8f9fa;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.9rem;
        }

        .blog-meta-item i {
            margin-left: 5px;
            color: var(--primary-color);
        }

        .blog-content {
            max-height: 150px;
            overflow: hidden;
            margin-bottom: 15px;
        }

        .blog-image {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .blog-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn-group-sm > .btn, .btn-sm {
            padding: .25rem .5rem;
            font-size: .875rem;
            border-radius: .2rem;
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

        .reference-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .preview-image {
            max-width: 100%;
            max-height: 200px;
            border-radius: 5px;
            margin-top: 10px;
        }

        .file-upload {
            position: relative;
            overflow: hidden;
            display: inline-block;
            cursor: pointer;
        }

        .file-upload input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
        }

        .file-info {
            margin-top: 10px;
            font-size: 0.9rem;
            color: var(--secondary-color);
        }
        .badge {
            max-width: 100%; /* أو px حسب الحاجة */
            white-space: normal; /* يخلي النص يكسر على أكثر من سطر */
            overflow-wrap: break-word; /* يكسر الكلمات الطويلة بدل ما تطلع */
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
                    <a class="nav-link active" href="blogs.php">
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
                        <h2>المدونات</h2>
                        <div class="user-info">
                            <span>مرحباً، <?php echo $_SESSION['username']; ?></span>
                            <img src="https://picsum.photos/seed/user<?php echo $_SESSION['user_id']; ?>/40/40.jpg" alt="User Avatar">
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="content">
                    <?php showMessage(); ?>

                    <!-- Add Blog Button -->
                    <div class="mb-4">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBlogModal">
                            <i class="fas fa-plus"></i> إضافة مدونة جديدة
                        </button>
                    </div>

                    <!-- Blogs List -->
                    <div class="row">
                        <?php if (mysqli_num_rows($blogs_result) > 0): ?>
                            <?php while ($blog = mysqli_fetch_assoc($blogs_result)): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="blog-card">
                                        <div class="blog-header">
                                            <h5><?php echo htmlspecialchars($blog['title']); ?></h5>
                                            <div>
                                                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#editBlogModal<?php echo $blog['id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="blog_id" value="<?php echo $blog['id']; ?>">
                                                    <button type="submit" name="delete_blog" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من حذف هذه المدونة؟');">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        <div class="blog-body">
                                            <?php if (!empty($blog['image_url'])): ?>
                                                <img src="<?php echo htmlspecialchars($blog['image_url']); ?>" alt="Blog Image" class="blog-image">
                                            <?php endif; ?>

                                            <div class="blog-content">
                                                <?php 
                                                $content = htmlspecialchars($blog['content']);
                                                if (strlen($content) > 300) {
                                                    echo substr($content, 0, 300) . '...';
                                                } else {
                                                    echo $content;
                                                }
                                                ?>
                                            </div>

                                            <div class="blog-meta">
                                                <?php 
                                                // استخراج الأنظمة
                                                $hierarchy = [];

                                                // الأنظمة
                                                if (!empty($blog['reference_system_id'])) {
                                                    $system_ids = explode(',', $blog['reference_system_id']);
                                                    foreach ($system_ids as $system_id) {
                                                        $system = getReferenceSystemById($system_id);
                                                        if ($system) {
                                                            $hierarchy[$system_id] = [
                                                                'title' => $system['title'],
                                                                'articles' => []
                                                            ];

                                                            // المواد المرتبطة بالنظام
                                                            if (!empty($blog['reference_article_id'])) {
                                                                $article_ids = explode(',', $blog['reference_article_id']);
                                                                foreach ($article_ids as $article_id) {
                                                                    $article = getReferenceArticleById($article_id);
                                                                    if ($article && $article['system_id'] == $system_id) { 
                                                                        $hierarchy[$system_id]['articles'][$article_id] = [
                                                                            'title' => $article['title'],
                                                                            'sections' => []
                                                                        ];

                                                                        // الأجزاء المرتبطة بالمادة
                                                                        if (!empty($blog['reference_section_id'])) {
                                                                            $section_ids = explode(',', $blog['reference_section_id']);
                                                                            foreach ($section_ids as $section_id) {
                                                                                $section = getReferenceSectionById($section_id);
                                                                                if ($section && $section['article_id'] == $article_id) {
                                                                                    $hierarchy[$system_id]['articles'][$article_id]['sections'][$section_id] = [
                                                                                        'title' => $section['title'],
                                                                                        'subsections' => []
                                                                                    ];

                                                                                    // الأجزاء الفرعية المرتبطة بالجزء
                                                                                    if (!empty($blog['reference_subsection_id'])) {
                                                                                        $subsection_ids = explode(',', $blog['reference_subsection_id']);
                                                                                        foreach ($subsection_ids as $sub_id) {
                                                                                            $subsection = getReferenceSubSectionById($sub_id);
                                                                                            if ($subsection && $subsection['parent_id'] == $section_id) {
                                                                                                $hierarchy[$system_id]['articles'][$article_id]['sections'][$section_id]['subsections'][$sub_id] = $subsection['title'];
                                                                                            }
                                                                                        }
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }

                                                ?>

                                                <?php foreach ($hierarchy as $system): ?>
                                                    <div class="blog-meta-item mb-2">
                                                        <i class="fas fa-gavel"></i>
                                                        <strong>النظام:</strong>
                                                        <span class="badge bg-primary me-1 mb-1"><?php echo htmlspecialchars($system['title']); ?></span>
                                                    </div>

                                                    <?php foreach ($system['articles'] as $article): ?>
                                                        <div class="blog-meta-item ps-3 mb-2">
                                                            <i class="fas fa-file-alt"></i>
                                                            <strong>المادة:</strong>
                                                            <span class="badge bg-success me-1 mb-1"><?php echo htmlspecialchars($article['title']); ?></span>
                                                        </div>

                                                        <?php foreach ($article['sections'] as $section): ?>
                                                            <div class="blog-meta-item ps-5 mb-2">
                                                                <i class="fas fa-list"></i>
                                                                <strong>الجزء:</strong>
                                                                <span class="badge bg-warning text-dark me-1 mb-1"><?php echo htmlspecialchars($section['title']); ?></span>
                                                            </div>

                                                            <?php foreach ($section['subsections'] as $sub): ?>
                                                                <div class="blog-meta-item ps-7 mb-2" style="padding-right: 90px;">
                                                                    <i class="fas fa-list"></i>
                                                                    <strong>الجزء الفرعي:</strong>
                                                                    <span class="badge bg-warning text-dark me-1 mb-1"><?php echo htmlspecialchars($sub); ?></span>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        <?php endforeach; ?>
                                                    <?php endforeach; ?>
                                                <?php endforeach; ?>





                                                <?php if (!empty($blog['pdf_path'])): ?>
                                                    <div class="blog-meta-item">
                                                        <i class="fas fa-file-pdf"></i>
                                                        ملف PDF
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($blog['video_url'])): ?>
                                                    <div class="blog-meta-item">
                                                        <i class="fas fa-video"></i>
                                                        فيديو
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($blog['external_link'])): ?>
                                                    <div class="blog-meta-item">
                                                        <i class="fas fa-link"></i>
                                                        رابط خارجي
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="blog-actions">
                                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewBlogModal<?php echo $blog['id']; ?>">
                                                    <i class="fas fa-eye"></i> عرض
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- View Blog Modal -->
                                <div class="modal fade" id="viewBlogModal<?php echo $blog['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title"><?php echo htmlspecialchars($blog['title']); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <?php if (!empty($blog['image_url'])): ?>
                                                    <img src="<?php echo htmlspecialchars($blog['image_url']); ?>" alt="Blog Image" class="img-fluid mb-3">
                                                <?php endif; ?>

                                                <div class="mb-3">
                                                    <?php echo nl2br(htmlspecialchars($blog['content'])); ?>
                                                </div>

                                                <?php if (!empty($blog['pdf_path'])): ?>
                                                    <div class="alert alert-info">
                                                        <i class="fas fa-file-pdf"></i> 
                                                        <a href="<?php echo htmlspecialchars($blog['pdf_path']); ?>" target="_blank" class="alert-link">عرض ملف PDF</a>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($blog['video_url'])): ?>
                                                    <div class="alert alert-info">
                                                        <i class="fas fa-video"></i> 
                                                        <a href="<?php echo htmlspecialchars($blog['video_url']); ?>" target="_blank" class="alert-link">مشاهدة الفيديو</a>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($blog['external_link'])): ?>
                                                    <div class="alert alert-info">
                                                        <i class="fas fa-link"></i> 
                                                        <a href="<?php echo htmlspecialchars($blog['external_link']); ?>" target="_blank" class="alert-link">زيارة الرابط الخارجي</a>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($blog['system_title']) || !empty($blog['article_title']) || !empty($blog['section_title'])): ?>
                                                    <div class="reference-section">
                                                             <?php 
                                                            // استخراج الأنظمة
                                                            $hierarchy = [];
                                                            // الأنظمة
                                                            if (!empty($blog['reference_system_id'])) {
                                                                $system_ids = explode(',', $blog['reference_system_id']);
                                                                foreach ($system_ids as $system_id) {
                                                                    $system = getReferenceSystemById($system_id);
                                                                    if ($system) {
                                                                        $hierarchy[$system_id] = [
                                                                            'title' => $system['title'],
                                                                            'articles' => []
                                                                        ];

                                                                        // المواد المرتبطة بالنظام
                                                                        if (!empty($blog['reference_article_id'])) {
                                                                            $article_ids = explode(',', $blog['reference_article_id']);
                                                                            foreach ($article_ids as $article_id) {
                                                                                $article = getReferenceArticleById($article_id);
                                                                                if ($article && $article['system_id'] == $system_id) { 
                                                                                    $hierarchy[$system_id]['articles'][$article_id] = [
                                                                                        'title' => $article['title'],
                                                                                        'sections' => []
                                                                                    ];

                                                                                    // الأجزاء المرتبطة بالمادة
                                                                                    if (!empty($blog['reference_section_id'])) {
                                                                                        $section_ids = explode(',', $blog['reference_section_id']);
                                                                                        foreach ($section_ids as $section_id) {
                                                                                            $section = getReferenceSectionById($section_id);
                                                                                            if ($section && $section['article_id'] == $article_id) {
                                                                                                $hierarchy[$system_id]['articles'][$article_id]['sections'][$section_id] = [
                                                                                                    'title' => $section['title'],
                                                                                                    'subsections' => []
                                                                                                ];

                                                                                                // الأجزاء الفرعية المرتبطة بالجزء
                                                                                                if (!empty($blog['reference_subsection_id'])) {
                                                                                                    $subsection_ids = explode(',', $blog['reference_subsection_id']);
                                                                                                    foreach ($subsection_ids as $sub_id) {
                                                                                                        $subsection = getReferenceSubSectionById($sub_id);
                                                                                                        if ($subsection && $subsection['parent_id'] == $section_id) {
                                                                                                            $hierarchy[$system_id]['articles'][$article_id]['sections'][$section_id]['subsections'][$sub_id] = $subsection['title'];
                                                                                                        }
                                                                                                    }
                                                                                                }
                                                                                            }
                                                                                        }
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }

                                                            ?>
                                                            <h6><i class="fas fa-link"></i> الاستدلال من الأنظمة والقوانين:</h6>

                                                           <?php foreach ($hierarchy as $system): ?>
                                                                <div class="blog-meta-item mb-2">
                                                                    <i class="fas fa-gavel"></i>
                                                                    <strong>النظام:</strong>
                                                                    <span class="badge bg-primary me-1 mb-1"><?php echo htmlspecialchars($system['title']); ?></span>
                                                                </div>

                                                                <?php foreach ($system['articles'] as $article): ?>
                                                                    <div class="blog-meta-item ps-3 mb-2">
                                                                        <i class="fas fa-file-alt"></i>
                                                                        <strong>المادة:</strong>
                                                                        <span class="badge bg-success me-1 mb-1"><?php echo htmlspecialchars($article['title']); ?></span>
                                                                    </div>

                                                                    <?php foreach ($article['sections'] as $section): ?>
                                                                        <div class="blog-meta-item ps-5 mb-2">
                                                                            <i class="fas fa-list"></i>
                                                                            <strong>الجزء:</strong>
                                                                            <span class="badge bg-warning text-dark me-1 mb-1"><?php echo htmlspecialchars($section['title']); ?></span>
                                                                        </div>

                                                                        <?php foreach ($section['subsections'] as $sub): ?>
                                                                            <div class="blog-meta-item ps-7 mb-2" style="padding-right: 90px;">
                                                                                <i class="fas fa-list"></i>
                                                                                <strong>الجزء الفرعي:</strong>
                                                                                <span class="badge bg-warning text-dark me-1 mb-1"><?php echo htmlspecialchars($sub); ?></span>
                                                                            </div>
                                                                        <?php endforeach; ?>
                                                                    <?php endforeach; ?>
                                                                <?php endforeach; ?>
                                                            <?php endforeach; ?>





                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Edit Blog Modal -->
                                <div class="modal fade" id="editBlogModal<?php echo $blog['id']; ?>" data-blog-id="<?php echo $blog['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">تعديل مدونة</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="post" enctype="multipart/form-data">
                                                    <input type="hidden" name="edit_blog" value="1">
                                                    <input type="hidden" name="blog_id" value="<?php echo $blog['id']; ?>">

                                                    <div class="mb-3">
                                                        <label for="edit_blog_title<?php echo $blog['id']; ?>" class="form-label">عنوان المدونة</label>
                                                        <input type="text" class="form-control" id="edit_blog_title<?php echo $blog['id']; ?>" name="blog_title" value="<?php echo htmlspecialchars($blog['title']); ?>" required>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="edit_blog_content<?php echo $blog['id']; ?>" class="form-label">محتوى المدونة</label>
                                                        <textarea class="form-control" id="edit_blog_content<?php echo $blog['id']; ?>" name="blog_content" rows="8" required><?php echo htmlspecialchars($blog['content']); ?></textarea>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="edit_blog_image<?php echo $blog['id']; ?>" class="form-label">صورة المدونة</label>
                                                        <div class="file-upload">
                                                            <span class="btn btn-outline-primary">
                                                                <i class="fas fa-upload"></i> اختيار صورة
                                                                <input type="file" id="edit_blog_image<?php echo $blog['id']; ?>" name="blog_image" accept="image/*">
                                                            </span>
                                                        </div>
                                                        <div class="file-info">
                                                            <?php if (!empty($blog['image_url'])): ?>
                                                                <div>الصورة الحالية: <?php echo basename($blog['image_url']); ?></div>
                                                                <img src="<?php echo htmlspecialchars($blog['image_url']); ?>" alt="Current Image" class="preview-image">
                                                            <?php else: ?>
                                                                <div>لا توجد صورة حالية</div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="edit_blog_pdf<?php echo $blog['id']; ?>" class="form-label">ملف PDF</label>
                                                        <div class="file-upload">
                                                            <span class="btn btn-outline-primary">
                                                                <i class="fas fa-upload"></i> اختيار ملف PDF
                                                                <input type="file" id="edit_blog_pdf<?php echo $blog['id']; ?>" name="blog_pdf" accept=".pdf">
                                                            </span>
                                                        </div>
                                                        <div class="file-info">
                                                            <?php if (!empty($blog['pdf_path'])): ?>
                                                                <div>الملف الحالي: <?php echo basename($blog['pdf_path']); ?></div>
                                                            <?php else: ?>
                                                                <div>لا يوجد ملف PDF حالي</div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="edit_video_url<?php echo $blog['id']; ?>" class="form-label">رابط الفيديو (اختياري)</label>
                                                        <input type="url" class="form-control" id="edit_video_url<?php echo $blog['id']; ?>" name="video_url" value="<?php echo htmlspecialchars($blog['video_url']); ?>">
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="edit_external_link<?php echo $blog['id']; ?>" class="form-label">رابط خارجي (اختياري)</label>
                                                        <input type="url" class="form-control" id="edit_external_link<?php echo $blog['id']; ?>" name="external_link" value="<?php echo htmlspecialchars($blog['external_link']); ?>">
                                                    </div>

                                                    <div class="reference-section">
                                                        <h6><i class="fas fa-link"></i> الاستدلال من الأنظمة والقوانين:</h6>

                                                        <div class="mb-3">
                                                            <label for="edit_reference_system<?php echo $blog['id']; ?>" class="form-label">اختر نظام/قانون</label>
                                                            <select class="form-select" id="edit_reference_system<?php echo $blog['id']; ?>" name="reference_system_id[]" multiple>
                                                                <option value="">-- اختر نظام/قانون --</option>
                                                                <?php 
                                                                mysqli_data_seek($systems_result, 0);
                                                                while ($system = mysqli_fetch_assoc($systems_result)): 
                                                                    // التحقق مما إذا كان النظام الحالي مختارًا
                                                                    $selected_system_ids = explode(',', $blog['reference_system_id']);
                                                                    $selected = in_array($system['id'], $selected_system_ids) ? 'selected' : '';
                                                                ?>
                                                                    <option value="<?php echo $system['id']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($system['title']); ?></option>
                                                                <?php endwhile; ?>
                                                            </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="edit_reference_article<?php echo $blog['id']; ?>" class="form-label">اختر مادة</label>
                                                            <select class="form-select" id="edit_reference_article<?php echo $blog['id']; ?>" name="reference_article_id[]" multiple>
                                                                <option value="">-- اختر مادة --</option>
                                                                <?php 
                                                                if (!empty($blog['reference_system_id'])) {
                                                                    // التعامل مع الاختيارات المتعددة للأنظمة
                                                                    $system_ids = explode(',', $blog['reference_system_id']);
                                                                    $placeholders = implode(',', array_fill(0, count($system_ids), '?'));

                                                                    // جلب المواد مع عنوان النظام
                                                                    $articles_sql = "
                                                                        SELECT a.id, a.title AS article_title, s.title AS system_title
                                                                        FROM articles a
                                                                        INNER JOIN systems s ON a.system_id = s.id
                                                                        WHERE a.system_id IN ($placeholders)
                                                                        ORDER BY s.title, a.title
                                                                    ";

                                                                    $stmt = mysqli_prepare($conn, $articles_sql);

                                                                    // ربط المعلمات
                                                                    $types = str_repeat('i', count($system_ids));
                                                                    mysqli_stmt_bind_param($stmt, $types, ...$system_ids);
                                                                    mysqli_stmt_execute($stmt);
                                                                    $articles_result = mysqli_stmt_get_result($stmt);

                                                                    while ($article = mysqli_fetch_assoc($articles_result)) {
                                                                        // التحقق مما إذا كانت المادة الحالية مختارة
                                                                        $selected_article_ids = explode(',', $blog['reference_article_id']);
                                                                        $selected = in_array($article['id'], $selected_article_ids) ? 'selected' : '';
                                                                        
                                                                        // عرض المادة + النظام جنب بعض
                                                                        echo "<option value='{$article['id']}' {$selected}>" 
                                                                            . htmlspecialchars($article['article_title']) 
                                                                            . " — " 
                                                                            . htmlspecialchars($article['system_title']) 
                                                                            . "</option>";
                                                                    }
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>


                                                        <div class="mb-3">
                                                            <label for="edit_reference_section<?php echo $blog['id']; ?>" class="form-label">اختر جزء</label>
                                                            <select class="form-select" id="edit_reference_section<?php echo $blog['id']; ?>" name="reference_section_id[]" multiple>
                                                                <option value="">-- اختر جزء --</option>
                                                                <?php 
                                                                if (!empty($blog['reference_article_id'])) {
                                                                    // التعامل مع الاختيارات المتعددة للمواد
                                                                    $article_ids = explode(',', $blog['reference_article_id']);
                                                                    $placeholders = implode(',', array_fill(0, count($article_ids), '?'));

                                                                    // جلب الأجزاء مع المادة والنظام
                                                                    $sections_sql = "
                                                                        SELECT sec.id, sec.title AS section_title, 
                                                                            art.title AS article_title, 
                                                                            sys.title AS system_title
                                                                        FROM sections sec
                                                                        INNER JOIN articles art ON sec.article_id = art.id
                                                                        INNER JOIN systems sys ON art.system_id = sys.id
                                                                        WHERE sec.article_id IN ($placeholders)
                                                                        ORDER BY sys.title, art.title, sec.title
                                                                    ";

                                                                    $stmt = mysqli_prepare($conn, $sections_sql);

                                                                    // ربط المعلمات
                                                                    $types = str_repeat('i', count($article_ids));
                                                                    mysqli_stmt_bind_param($stmt, $types, ...$article_ids);
                                                                    mysqli_stmt_execute($stmt);
                                                                    $sections_result = mysqli_stmt_get_result($stmt);

                                                                    while ($section = mysqli_fetch_assoc($sections_result)) {
                                                                        // التحقق مما إذا كان الجزء الحالي مختارًا
                                                                        $selected_section_ids = explode(',', $blog['reference_section_id']);
                                                                        $selected = in_array($section['id'], $selected_section_ids) ? 'selected' : '';
                                                                        
                                                                        echo "<option value='{$section['id']}' {$selected}>"
                                                                            . htmlspecialchars($section['section_title']) . " — "
                                                                            . htmlspecialchars($section['article_title']) . " — "
                                                                            . htmlspecialchars($section['system_title'])
                                                                            . "</option>";
                                                                    }
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label for="edit_reference_sub_section<?php echo $blog['id']; ?>" class="form-label">اختر جزء فرعي</label>
                                                            <select class="form-select" id="edit_reference_sub_section<?php echo $blog['id']; ?>" name="reference_subsection_id[]" multiple>
                                                                <option value="">-- اختر جزء فرعي --</option>
                                                                <?php 
                                                                if (!empty($blog['reference_section_id'])) {
                                                                    // الأجزاء المختارة مسبقًا
                                                                    $section_ids = explode(',', $blog['reference_section_id']);
                                                                    $placeholders = implode(',', array_fill(0, count($section_ids), '?'));

                                                                    // جلب الأجزاء الفرعية مع بيانات الجزء والمادة والنظام
                                                                    $sub_sections_sql = "
                                                                        SELECT sub.id, sub.title AS sub_title,
                                                                            parent.title AS parent_title,
                                                                            art.title AS article_title,
                                                                            sys.title AS system_title
                                                                        FROM sections sub
                                                                        INNER JOIN sections parent ON sub.parent_id = parent.id
                                                                        INNER JOIN articles art ON parent.article_id = art.id
                                                                        INNER JOIN systems sys ON art.system_id = sys.id
                                                                        WHERE sub.parent_id IN ($placeholders)
                                                                        ORDER BY sys.title, art.title, parent.title, sub.title
                                                                    ";

                                                                    $stmt = mysqli_prepare($conn, $sub_sections_sql);

                                                                    // ربط المعاملات
                                                                    $types = str_repeat('i', count($section_ids));
                                                                    mysqli_stmt_bind_param($stmt, $types, ...$section_ids);
                                                                    mysqli_stmt_execute($stmt);
                                                                    $sub_sections_result = mysqli_stmt_get_result($stmt);

                                                                    // الأجزاء الفرعية المختارة مسبقًا
                                                                    $selected_sub_section_ids = !empty($blog['reference_subsection_id']) 
                                                                        ? explode(',', $blog['reference_subsection_id']) 
                                                                        : [];

                                                                    while ($sub_section = mysqli_fetch_assoc($sub_sections_result)) {
                                                                        $selected = in_array($sub_section['id'], $selected_sub_section_ids) ? 'selected' : '';

                                                                        echo "<option value='{$sub_section['id']}' {$selected}>"
                                                                            . htmlspecialchars($sub_section['sub_title']) . " — "
                                                                            . htmlspecialchars($sub_section['parent_title']) . " — "
                                                                            . htmlspecialchars($sub_section['article_title']) . " — "
                                                                            . htmlspecialchars($sub_section['system_title'])
                                                                            . "</option>";
                                                                    }
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>


                                                    </div>

                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                                        <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> لا توجد مدونات حالياً.
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Blog Modal -->
    <div class="modal fade" id="addBlogModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">إضافة مدونة جديدة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="add_blog" value="1">

                        <div class="mb-3">
                            <label for="blog_title" class="form-label">عنوان المدونة</label>
                            <input type="text" class="form-control" id="blog_title" name="blog_title" required>
                        </div>

                        <div class="mb-3">
                            <label for="blog_content" class="form-label">محتوى المدونة</label>
                            <textarea class="form-control" id="blog_content" name="blog_content" rows="8" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="blog_image" class="form-label">صورة المدونة (اختياري)</label>
                            <div class="file-upload">
                                <span class="btn btn-outline-primary">
                                    <i class="fas fa-upload"></i> اختيار صورة
                                    <input type="file" id="blog_image" name="blog_image" accept="image/*">
                                </span>
                            </div>
                            <div class="file-info">
                                <div>لم يتم اختيار صورة بعد</div>
                                <img id="image_preview" src="" alt="Image Preview" class="preview-image" style="display: none;">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="blog_pdf" class="form-label">ملف PDF (اختياري)</label>
                            <div class="file-upload">
                                <span class="btn btn-outline-primary">
                                    <i class="fas fa-upload"></i> اختيار ملف PDF
                                    <input type="file" id="blog_pdf" name="blog_pdf" accept=".pdf">
                                </span>
                            </div>
                            <div class="file-info">
                                <div>لم يتم اختيار ملف PDF بعد</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="video_url" class="form-label">رابط الفيديو (اختياري)</label>
                            <input type="url" class="form-control" id="video_url" name="video_url">
                        </div>

                        <div class="mb-3">
                            <label for="external_link" class="form-label">رابط خارجي (اختياري)</label>
                            <input type="url" class="form-control" id="external_link" name="external_link">
                        </div>

                        <div class="reference-section">
                            <h6><i class="fas fa-link"></i> الاستدلال من الأنظمة والقوانين:</h6>

                            <div class="mb-3">
                                <label for="reference_system" class="form-label">اختر نظام/قانون</label>
                                <select class="form-select" id="reference_system" name="reference_system_id[]" multiple>
                                    <option disabled value="">-- اختر نظام/قانون --</option>
                                    <?php 
                                    mysqli_data_seek($systems_result, 0);
                                    while ($system = mysqli_fetch_assoc($systems_result)): 
                                    ?>
                                        <option value="<?php echo $system['id']; ?>"><?php echo htmlspecialchars($system['title']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="reference_article" class="form-label">اختر مادة</label>
                                <select class="form-select" id="reference_article" name="reference_article_id[]" multiple disabled>
                                    <option disabled value="">-- اختر مادة --</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="reference_section" class="form-label">اختر جزء</label>
                                <select class="form-select" id="reference_section" name="reference_section_id[]" multiple disabled>
                                    <option disabled value="">-- اختر جزء --</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="reference_subsection" class="form-label">اختر جزء فرعي</label>
                                <select class="form-select" id="reference_subsection" name="reference_subsection_id[]" multiple disabled>
                                    <option disabled value="">-- اختر جزء فرعي --</option>
                                </select>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-primary">إضافة المدونة</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    $('#addBlogModal').on('shown.bs.modal', function () {
        // النظام
        $('#reference_system').select2({
            dropdownParent: $('#addBlogModal .modal-content'),
            width: '100%'
        });

        // المواد (تمكين أولاً)
        $('#reference_article').prop('disabled', false).select2({
            dropdownParent: $('#addBlogModal .modal-content'),
            width: '100%'
        });

        // الأجزاء (تمكين أولاً)
        $('#reference_section').prop('disabled', false).select2({
            dropdownParent: $('#addBlogModal .modal-content'),
            width: '100%'
        });
        $('#reference_subsection').prop('disabled', false).select2({
            dropdownParent: $('#addBlogModal .modal-content'),
            width: '100%'
        });
    });

 </script>
 <script>
    $('.modal.fade').on('shown.bs.modal', function () {
        // جلب الـ blog ID من الـ data attribute
        let blogId = $(this).data('blog-id');

        // النظام
        $('#edit_reference_system' + blogId).select2({
            dropdownParent: $('#editBlogModal' + blogId + ' .modal-content'),
            width: '100%'
        });

        // المواد
        $('#edit_reference_article' + blogId).select2({
            dropdownParent: $('#editBlogModal' + blogId + ' .modal-content'),
            width: '100%'
        });

        // الأجزاء
        $('#edit_reference_section' + blogId).select2({
            dropdownParent: $('#editBlogModal' + blogId + ' .modal-content'),
            width: '100%'
        });
        $('#edit_reference_sub_section' + blogId).select2({
            dropdownParent: $('#editBlogModal' + blogId + ' .modal-content'),
            width: '100%'
        });
    });

 </script>
    <script>
        $(document).ready(function() {
            // معاينة الصورة عند اختيارها
            $('#blog_image').change(function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#image_preview').attr('src', e.target.result).show();
                        $('.file-info div').text('الصورة المختارة: ' + file.name);
                    }
                    reader.readAsDataURL(file);
                } else {
                    $('#image_preview').hide();
                    $('.file-info div').text('لم يتم اختيار صورة بعد');
                }
            });

            // عرض اسم ملف PDF عند اختياره
            $('#blog_pdf').change(function() {
                const file = this.files[0];
                if (file) {
                    $(this).closest('.mb-3').find('.file-info div').text('الملف المختار: ' + file.name);
                } else {
                    $(this).closest('.mb-3').find('.file-info div').text('لم يتم اختيار ملف PDF بعد');
                }
            });

            // تحميل المواد عند اختيار نظام
            $('#reference_system').change(function() {
                const system_ids = $(this).val() || [];
                const article_select = $('#reference_article');
                const section_select = $('#reference_section');
                const subsection_select = $('#reference_subsection');

                article_select.html('<option value="">-- اختر مادة --</option>');
                section_select.html('<option value="">-- اختر جزء --</option>');
                subsection_select.html('<option value="">-- اختر جزء فرعي --</option>');

                if (system_ids.length > 0) {
                    article_select.prop('disabled', false);

                    $.ajax({
                        url: 'blogs.php',
                        type: 'POST',
                        data: { get_articles: 1, system_ids: system_ids },
                        dataType: 'json',
                        success: function(data) {
                            if (data.length > 0) {
                                $.each(data, function(index, article) {
                                    article_select.append('<option value="' + article.id + '">' + article.title_full + '</option>');
                                });
                            } else {
                                article_select.append('<option value="">-- لا توجد مواد --</option>');
                            }
                        }
                    });
                } else {
                    article_select.prop('disabled', true);
                    section_select.prop('disabled', true);
                    subsection_select.prop('disabled', true);
                }
            });

            // تحميل الأجزاء عند اختيار مادة
            $('#reference_article').change(function() {
                const article_ids = $(this).val() || [];
                const section_select = $('#reference_section');
                const subsection_select = $('#reference_subsection');

                section_select.html('<option value="">-- اختر جزء --</option>');
                subsection_select.html('<option value="">-- اختر جزء فرعي --</option>');

                if (article_ids.length > 0) {
                    section_select.prop('disabled', false);

                    $.ajax({
                        url: 'blogs.php',
                        type: 'POST',
                        data: { get_sections: 1, article_ids: article_ids },
                        dataType: 'json',
                        success: function(data) {
                            if (data.length > 0) {
                                $.each(data, function(index, section) {
                                    section_select.append('<option value="' + section.id + '">' + section.title_full + '</option>');
                                });
                            } else {
                                section_select.append('<option value="">-- لا توجد أجزاء --</option>');
                            }
                        }
                    });
                } else {
                    section_select.prop('disabled', true);
                    subsection_select.prop('disabled', true);
                }
            });

            // تحميل الأجزاء الفرعية عند اختيار جزء
            $('#reference_section').change(function() {
                const section_ids = $(this).val() || [];
                const subsection_select = $('#reference_subsection');

                subsection_select.html('<option value="">-- اختر جزء فرعي --</option>');

                if (section_ids.length > 0) {
                    subsection_select.prop('disabled', false);

                    $.ajax({
                        url: 'blogs.php',
                        type: 'POST',
                        data: { get_subsections: 1, section_ids: section_ids },
                        dataType: 'json',
                        success: function(data) {
                            if (data.length > 0) {
                                $.each(data, function(index, subsection) {
                                    subsection_select.append('<option value="' + subsection.id + '">' + subsection.title_full + '</option>');
                                });
                            } else {
                                subsection_select.append('<option value="">-- لا توجد أجزاء فرعية --</option>');
                            }
                        }
                    });
                } else {
                    subsection_select.prop('disabled', true);
                }
            });


            // نفس الوظائف لنماذج التعديل
            $('.modal').on('show.bs.modal', function() {
                const modalId = $(this).attr('id');

                if (modalId && modalId.startsWith('editBlogModal')) {
                    const blogId = modalId.replace('editBlogModal', '');

                    // معاينة الصورة عند اختيارها
                    $('#edit_blog_image' + blogId).change(function() {
                        const file = this.files[0];
                        if (file) {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                $(this).closest('.mb-3').find('.preview-image').attr('src', e.target.result);
                                $(this).closest('.mb-3').find('.file-info div').html('الصورة الجديدة: ' + file.name);
                            }.bind(this);
                            reader.readAsDataURL(file);
                        }
                    });

                    // عرض اسم ملف PDF عند اختياره
                    $('#edit_blog_pdf' + blogId).change(function() {
                        const file = this.files[0];
                        if (file) {
                            $(this).closest('.mb-3').find('.file-info div').html('الملف الجديد: ' + file.name);
                        }
                    });

                    // تحميل المواد عند اختيار نظام
                    // تحميل المواد عند اختيار نظام (في التعديل)
                    $('#edit_reference_system' + blogId).change(function() {
                        const system_ids = $(this).val() || [];
                        const article_select = $('#edit_reference_article' + blogId);
                        const section_select = $('#edit_reference_section' + blogId);

                        article_select.html('<option value="">-- اختر مادة --</option>');
                        section_select.html('<option value="">-- اختر جزء --</option>');

                        if (system_ids.length > 0) {
                            article_select.prop('disabled', false);

                            $.ajax({
                                url: 'blogs.php',
                                type: 'POST',
                                data: { get_articles: 1, system_ids: system_ids },
                                dataType: 'json',
                                success: function(data) {
                                    if (data.length > 0) {
                                        $.each(data, function(index, article) {
                                            article_select.append('<option value="' + article.id + '">' + article.title_full + '</option>');
                                        });
                                    } else {
                                        article_select.append('<option value="">-- لا توجد مواد --</option>');
                                    }
                                }
                            });
                        } else {
                            article_select.prop('disabled', true);
                            section_select.prop('disabled', true);
                        }
                    });

                    // تحميل الأجزاء عند اختيار مادة
                    $('#edit_reference_article' + blogId).change(function() {
                        const article_ids = $(this).val() || [];
                        const section_select = $('#edit_reference_section' + blogId);
                        const subsection_select = $('#edit_reference_subsection' + blogId);

                        section_select.html('<option value="">-- اختر جزء --</option>');
                        subsection_select.html('<option value="">-- اختر جزء فرعي --</option>');

                        if (article_ids.length > 0) {
                            section_select.prop('disabled', false);

                            $.ajax({
                                url: 'blogs.php',
                                type: 'POST',
                                data: { get_sections: 1, article_ids: article_ids },
                                dataType: 'json',
                                success: function(data) {
                                    if (data.length > 0) {
                                        $.each(data, function(index, section) {
                                            section_select.append('<option value="' + section.id + '">' + section.title_full + '</option>');
                                        });
                                    } else {
                                        section_select.append('<option value="">-- لا توجد أجزاء --</option>');
                                    }
                                }
                            });
                        } else {
                            section_select.prop('disabled', true);
                            subsection_select.prop('disabled', true);
                        }
                    });

                    // تحميل الأجزاء الفرعية عند اختيار جزء
                    $('#edit_reference_section' + blogId).change(function() {
                        const section_ids = $(this).val() || [];
                        const subsection_select = $('#edit_reference_subsection' + blogId);

                        subsection_select.html('<option value="">-- اختر جزء فرعي --</option>');

                        if (section_ids.length > 0) {
                            subsection_select.prop('disabled', false);

                            $.ajax({
                                url: 'blogs.php',
                                type: 'POST',
                                data: { get_subsections: 1, section_ids: section_ids },
                                dataType: 'json',
                                success: function(data) {
                                    if (data.length > 0) {
                                        $.each(data, function(index, subsection) {
                                            subsection_select.append('<option value="' + subsection.id + '">' + subsection.title_full + '</option>');
                                        });
                                    } else {
                                        subsection_select.append('<option value="">-- لا توجد أجزاء فرعية --</option>');
                                    }
                                }
                            });
                        } else {
                            subsection_select.prop('disabled', true);
                        }
                    });

                }
            });
        });
    </script>
</body>
</html>