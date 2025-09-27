
<?php
require_once 'config.php';

// التحقق من تسجيل دخول المستخدم
requireLogin();

// دالة معالجة الأجزاء بشكل متكرر
function processSections($sections, $article_id, $parent_id = null) {
    global $conn;

    foreach ($sections as $section) {
        if (!empty($section['title'])) {
            $section_title = cleanInput($section['title']);
            $section_content = cleanInput($section['content']);
            $entity_id = !empty($section['entity_id']) ? cleanInput($section['entity_id']) : null;
            $usage_id = !empty($section['usage_id']) ? cleanInput($section['usage_id']) : null;

            $sql = "INSERT INTO sections (article_id, parent_id, title, content, entity_id, usage_id) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iissii", $article_id, $parent_id, $section_title, $section_content, $entity_id, $usage_id);
            mysqli_stmt_execute($stmt);

            $section_id = mysqli_insert_id($conn);

            // معالجة مراجع الجزء
            if (!empty($section['references']) && is_array($section['references'])) {
                foreach ($section['references'] as $reference_id) {
                    $sql = "INSERT INTO section_references (section_id, referenced_section_id) VALUES (?, ?)";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "ii", $section_id, $reference_id);
                    mysqli_stmt_execute($stmt);
                }
            }

            // معالجة الأجزاء الفرعية
            if (isset($section['subsections']) && is_array($section['subsections'])) {
                processSections($section['subsections'], $article_id, $section_id);
            }
        }
    }
}

// دالة لجلب الأجزاء بشكل متكرر
function getSectionsRecursive($article_id, $parent_id = null, $level = 0) {
    global $conn;

    $sections = [];

    $sql = "SELECT * FROM sections WHERE article_id = ? AND parent_id " . ($parent_id === null ? "IS NULL" : "= ?");
    $stmt = mysqli_prepare($conn, $sql);

    if ($parent_id === null) {
        mysqli_stmt_bind_param($stmt, "i", $article_id);
    } else {
        mysqli_stmt_bind_param($stmt, "ii", $article_id, $parent_id);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($section = mysqli_fetch_assoc($result)) {
        $section['level'] = $level;
        $section['subsections'] = getSectionsRecursive($article_id, $section['id'], $level + 1);
        $sections[] = $section;
    }

    return $sections;
}

// دالة لعرض الأجزاء بشكل متكرر
function displaySectionsRecursive($sections, $article_id) {
    foreach ($sections as $section) {
        $margin = $section['level'] * 20;
        
        // الحصول على الجهة المعنية
        $entity_name = '';
        if (!empty($section['entity_id'])) {
            $entity = getEntityById($section['entity_id']);
            if ($entity) {
                $entity_name = $entity['title'];
            }
        }
        
        // الحصول على الاستخدام
        $usage_name = '';
        if (!empty($section['usage_id'])) {
            $usage = getUsageById($section['usage_id']);
            if ($usage) {
                $usage_name = $usage['title'];
            }
        }
        
        // الحصول على الأجزاء المرتبطة
        $references = getSectionReferences($section['id']);
        $references_text = '';
        if (!empty($references)) {
            $references_titles = [];
            foreach ($references as $ref) {
                $ref_section = getSectionById($ref['referenced_section_id']);
                if ($ref_section) {
                    $references_titles[] = $ref_section['title'];
                }
            }
            if (!empty($references_titles)) {
                $references_text = implode(', ', array_slice($references_titles, 0, 3));
                if (count($references_titles) > 3) {
                    $references_text .= ' و ' . (count($references_titles) - 3) . ' أخرى';
                }
            }
        }
        
        echo '<div class="section-card mb-3" style="margin-right: ' . $margin . 'px;">';
        echo '<div class="card">';
        echo '<div class="card-body">';
        echo '<div class="d-flex justify-content-between align-items-start mb-2">';
        echo '<h5 class="card-title">' . $section['title'] . '</h5>';
        echo '<div>';
        echo '<button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#editSectionModal' . $section['id'] . '">';
        echo '<i class="fas fa-edit"></i>';
        echo '</button> ';
        echo '<form method="post" style="display: inline;">';
        echo '<input type="hidden" name="section_id" value="' . $section['id'] . '">';
        echo '<button type="submit" name="delete_section" class="btn btn-danger btn-sm" onclick="return confirm(\'هل أنت متأكد من حذف هذا الجزء؟\')">';
        echo '<i class="fas fa-trash"></i>';
        echo '</button>';
        echo '</form>';
        echo '</div>';
        echo '</div>';
        
        echo '<p class="card-text">' . nl2br(substr($section['content'], 0, 150)) . (strlen($section['content']) > 150 ? '...' : '') . '</p>';
        
        // عرض الجهة المعنية والأجزاء المرتبطة
        echo '<div class="row mt-3">';
        
        if (!empty($entity_name)) {
            echo '<div class="col-md-6 mb-2">';
            echo '<div class="d-flex align-items-center">';
            echo '<span class="badge bg-primary me-2">الجهة المعنية</span>';
            echo '<span>' . $entity_name . '</span>';
            echo '</div>';
            echo '</div>';
        }

        if (!empty($usage_name)) {
            echo '<div class="col-md-6 mb-2">';
            echo '<div class="d-flex align-items-center">';
            echo '<span class="badge bg-warning me-2">الاستخدامات</span>';
            echo '<span>' . $usage_name . '</span>';
            echo '</div>';
            echo '</div>';
        }
        
        if (!empty($references_text)) {
            echo '<div class="col-md-6 mb-2">';
            echo '<div class="d-flex align-items-center">';
            echo '<span class="badge bg-info me-2">الأجزاء المرتبطة</span>';
            echo '<span>' . $references_text . '</span>';
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
        echo '</div>';

        // عرض الأجزاء الفرعية بشكل متكرر
        if (!empty($section['subsections'])) {
            displaySectionsRecursive($section['subsections'], $article_id);
        }
    }
}

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
                        $entity_id = !empty($article['entity_id']) ? cleanInput($article['entity_id']) : null;
                        $usage_id = !empty($article['usage_id']) ? cleanInput($article['usage_id']) : null;

                        $sql = "INSERT INTO articles (system_id, title, content, entity_id, usage_id) VALUES (?, ?, ?, ?, ?)";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "issii", $system_id, $article_title, $article_content, $entity_id, $usage_id);
                        mysqli_stmt_execute($stmt);

                        $article_id = mysqli_insert_id($conn);

                        // معالجة مراجع المادة
                        if (!empty($article['references']) && is_array($article['references'])) {
                            foreach ($article['references'] as $reference_id) {
                                $sql = "INSERT INTO article_references (article_id, referenced_article_id) VALUES (?, ?)";
                                $stmt = mysqli_prepare($conn, $sql);
                                mysqli_stmt_bind_param($stmt, "ii", $article_id, $reference_id);
                                mysqli_stmt_execute($stmt);
                            }
                        }

                        // معالجة الأجزاء داخل المادة
                        if (isset($article['sections']) && is_array($article['sections'])) {
                            processSections($article['sections'], $article_id, null);
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
        $entity_id = !empty($_POST['entity_id']) ? cleanInput($_POST['entity_id']) : null;
        $usage_id = !empty($_POST['usage_id']) ? cleanInput($_POST['usage_id']) : null;

        $sql = "UPDATE articles SET title = ?, content = ?, entity_id = ?, usage_id = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssiii", $title, $content, $entity_id, $usage_id, $article_id);

        if (mysqli_stmt_execute($stmt)) {
            // حذف المراجع القديمة
            $sql = "DELETE FROM article_references WHERE article_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $article_id);
            mysqli_stmt_execute($stmt);

            // إضافة المراجع الجديدة
            if (!empty($_POST['references']) && is_array($_POST['references'])) {
                foreach ($_POST['references'] as $reference_id) {
                    $sql = "INSERT INTO article_references (article_id, referenced_article_id) VALUES (?, ?)";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "ii", $article_id, $reference_id);
                    mysqli_stmt_execute($stmt);
                }
            }

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
        $entity_id = !empty($_POST['entity_id']) ? cleanInput($_POST['entity_id']) : null;
        $usage_id = !empty($_POST['usage_id']) ? cleanInput($_POST['usage_id']) : null;

        $sql = "UPDATE sections SET title = ?, content = ?, entity_id = ?, usage_id = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssiii", $title, $content, $entity_id, $usage_id, $section_id);

        if (mysqli_stmt_execute($stmt)) {
            // حذف المراجع القديمة
            $sql = "DELETE FROM section_references WHERE section_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $section_id);
            mysqli_stmt_execute($stmt);

            // إضافة المراجع الجديدة
            if (!empty($_POST['references']) && is_array($_POST['references'])) {
                foreach ($_POST['references'] as $reference_id) {
                    $sql = "INSERT INTO section_references (section_id, referenced_section_id) VALUES (?, ?)";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "ii", $section_id, $reference_id);
                    mysqli_stmt_execute($stmt);
                }
            }

            $_SESSION['message'] = "تم تعديل الجزء بنجاح!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "خطأ في تعديل الجزء: " . mysqli_error($conn);
            $_SESSION['message_type'] = "danger";
        }
    }

    // إضافة مادة قانونية مستقلة لنظام معين
    if (isset($_POST['add_article'])) {
        $system_id = cleanInput($_POST['system_id']);
        $title = cleanInput($_POST['article_title']);
        $content = cleanInput($_POST['article_content']);
        $entity_id = !empty($_POST['entity_id']) ? cleanInput($_POST['entity_id']) : null;

        $sql = "INSERT INTO articles (system_id, title, content, entity_id) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "issi", $system_id, $title, $content, $entity_id);

        if (mysqli_stmt_execute($stmt)) {
            $article_id = mysqli_insert_id($conn);

            // إضافة مراجع المادة
            if (!empty($_POST['references']) && is_array($_POST['references'])) {
                foreach ($_POST['references'] as $reference_id) {
                    $sql = "INSERT INTO article_references (article_id, referenced_article_id) VALUES (?, ?)";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "ii", $article_id, $reference_id);
                    mysqli_stmt_execute($stmt);
                }
            }

            // معالجة الأجزاء بشكل متكرر
            if (isset($_POST['sections']) && is_array($_POST['sections'])) {
                processSections($_POST['sections'], $article_id, null);
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
            transition: all 0.3s;
        }

        .btn-group-sm > .btn, .btn-sm {
            padding: .25rem .5rem;
            font-size: .875rem;
        }
        
        /* تصميم مخصص لقوائم الاختيار المتعدد */
        .select2-container--default .select2-selection--multiple {
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            min-height: calc(1.5em + 0.75rem + 2px);
            padding: 0.375rem 0.75rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: #86b7fe;
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #0d6efd;
            color: white;
            border: none;
            border-radius: 0.25rem;
            padding: 0.25rem 0.5rem;
            margin: 0 0.25rem 0.25rem 0;
            display: inline-flex;
            align-items: center;
            font-size: 0.875rem;
        }
        
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            color: white;
            margin-right: 0.5rem;
            font-weight: bold;
            opacity: 0.8;
            transition: opacity 0.15s ease-in-out;
        }
        
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            opacity: 1;
        }
        
        .select2-container--default .select2-search--inline .select2-search__field {
            min-height: auto;
            padding: 0;
            margin: 0;
        }
        
        .select2-container--default .select2-selection--multiple .select2-selection__rendered {
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem;
            padding: 0;
            margin: 0;
            list-style: none;
        }
        
        .select2-container--default .select2-dropdown {
            border-radius: 0.375rem;
            border: 1px solid #ced4da;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
            background-color: #0d6efd;
            color: white;
        }
        
        .select2-container--default .select2-results__option--selected {
            background-color: #e9ecef;
            color: #212529;
            font-weight: 500;
        }
        
        .select2-container--default .select2-search--dropdown .select2-search__field {
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
        }

        .add-article-btn, .add-section-btn, .add-subsection-btn {
            margin-top: 10px;
            margin-bottom: 10px;
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

        .nested-sections {
            margin-right: 20px;
            border-right: 1px dashed #ddd;
            padding-right: 15px;
        }

        .section-level-1 {
            border-right-color: var(--info-color);
        }

        .section-level-2 {
            border-right-color: var(--success-color);
        }

        .section-level-3 {
            border-right-color: var(--warning-color);
        }

        .section-level-4 {
            border-right-color: var(--danger-color);
        }

        .section-item {
            position: relative;
            margin-bottom: 15px;
        }

        .section-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .section-item-title {
            font-weight: 600;
            color: var(--dark-color);
        }

        .section-item-actions {
            display: flex;
            gap: 5px;
        }

        .subsection-container {
            margin-right: 20px;
            margin-top: 10px;
            padding-right: 15px;
            border-right: 1px dashed #ddd;
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
                    <a class="nav-link" href="entities.php">
                      <i class="fas fa-building"></i> الجهات المعنية
                    </a>
                    <a class="nav-link" href="usages.php"><i class="fas fa-cogs"></i> الاستخدامات</a>
                    <?php endif; ?>
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
                                <div class="system-header" data-bs-toggle="collapse" data-bs-target="#systemBody<?php echo $system['id']; ?>" style="cursor: pointer;">
                                    <h4 class="mb-0"><?php echo $system['title']; ?> <i class="fas fa-chevron-down ms-2"></i></h4>
                                    <div>
                                        <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#editSystemModal<?php echo $system['id']; ?>" onclick="event.stopPropagation();">
                                            <i class="fas fa-edit"></i> تعديل
                                        </button>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="system_id" value="<?php echo $system['id']; ?>">
                                            <button type="submit" name="delete_system" class="btn btn-danger btn-sm" onclick="return confirm('هل أنت متأكد من حذف هذا النظام؟'); event.stopPropagation();">
                                                <i class="fas fa-trash"></i> حذف
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div class="system-body collapse show" id="systemBody<?php echo $system['id']; ?>">
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
                                            <div class="article-card mb-4">
                                                <div class="card">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-start mb-2" data-bs-toggle="collapse" data-bs-target="#articleBody<?php echo $article['id']; ?>" style="cursor: pointer;">
                                                            <h5 class="card-title"><?php echo $article['title']; ?> <i class="fas fa-chevron-down ms-2"></i></h5>
                                                            <div>
                                                                <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#editArticleModal<?php echo $article['id']; ?>" onclick="event.stopPropagation();">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <form method="post" style="display: inline;">
                                                                    <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                                                                    <button type="submit" name="delete_article" class="btn btn-danger btn-sm" onclick="return confirm('هل أنت متأكد من حذف هذه المادة؟'); event.stopPropagation();">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                        <div class="collapse show" id="articleBody<?php echo $article['id']; ?>">
                                                        
                                                        <p class="card-text"><?php echo nl2br(substr($article['content'], 0, 200)) . (strlen($article['content']) > 200 ? '...' : ''); ?></p>
                                                        
                                                        <?php
                                                        // الحصول على الجهة المعنية
                                                        $entity_name = '';
                                                        if (!empty($article['entity_id'])) {
                                                            $entity = getEntityById($article['entity_id']);
                                                            if ($entity) {
                                                                $entity_name = $entity['title'];
                                                            }
                                                        }
                                                        
                                                        // الحصول على الاستخدام
                                                        $usage_name = '';
                                                        if (!empty($article['usage_id'])) {
                                                            $usage = getUsageById($article['usage_id']);
                                                            if ($usage) {
                                                                $usage_name = $usage['title'];
                                                            }
                                                        }
                                                        
                                                        // الحصول على الأجزاء المرتبطة
                                                        $references = getArticleReferences($article['id']);
                                                        $references_text = '';
                                                        if (!empty($references)) {
                                                            $references_titles = [];
                                                            foreach ($references as $ref) {
                                                                $ref_article = getArticleById($ref['referenced_article_id']);
                                                                if ($ref_article) {
                                                                    $references_titles[] = $ref_article['title'];
                                                                }
                                                            }
                                                            if (!empty($references_titles)) {
                                                                $references_text = implode(', ', array_slice($references_titles, 0, 3));
                                                                if (count($references_titles) > 3) {
                                                                    $references_text .= ' و ' . (count($references_titles) - 3) . ' أخرى';
                                                                }
                                                            }
                                                        }
                                                        ?>
                                                        
                                                        <!-- عرض الجهة المعنية والاستخدام والأجزاء المرتبطة -->
                                                        <div class="row mt-3">
                                                            
                                                            <?php if (!empty($entity_name)): ?>
                                                            <div class="col-md-4 mb-2">
                                                                <div class="d-flex align-items-center">
                                                                    <span class="badge bg-primary me-2">الجهة المعنية</span>
                                                                    <span><?php echo $entity_name; ?></span>
                                                                </div>
                                                            </div>
                                                            <?php endif; ?>

                                                            <?php if (!empty($usage_name)): ?>
                                                            <div class="col-md-4 mb-2">
                                                                <div class="d-flex align-items-center">
                                                                    <span class="badge bg-warning me-2">الاستخدامات</span>
                                                                    <span><?php echo $usage_name; ?></span>
                                                                </div>
                                                            </div>
                                                            <?php endif; ?>
                                                            
                                                            <?php if (!empty($references_text)): ?>
                                                            <div class="col-md-4 mb-2">
                                                                <div class="d-flex align-items-center">
                                                                    <span class="badge bg-info me-2">المواد المرتبطة</span>
                                                                    <span><?php echo $references_text; ?></span>
                                                                </div>
                                                            </div>
                                                            <?php endif; ?>
                                                            
                                                        </div>

                                                        
                                                        <!-- Sections -->
                                                        <?php
                                                        $sections = getSectionsRecursive($article['id']);

                                                        if (!empty($sections)):
                                                        ?>
                                                            <div class="mt-4">
                                                                <h6 class="text-muted mb-3">أجزاء المادة:</h6>
                                                                <div class="sections-container">
                                                                    <?php displaySectionsRecursive($sections, $article['id']); ?>
                                                                </div>
                                                            </div>
                                                        <?php else: ?>
                                                            <p class="text-muted mt-3">لا توجد أجزاء لهذه المادة.</p>
                                                        <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
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
                            <div class="modal fade" id="addArticleModal<?php echo $system['id']; ?>" data-system-id="<?php echo $system['id']; ?>" tabindex="-1" aria-hidden="true">
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
                                                    <label for="article_entity<?php echo $system['id']; ?>" class="form-label">الجهة المعنية</label>
                                                    <select class="form-select" id="article_entity<?php echo $system['id']; ?>" name="entity_id">
                                                        <option value="">-- اختر جهة معنية --</option>
                                                        <?php
                                                        $entities = getEntities();
                                                        foreach ($entities as $entity) {
                                                            echo "<option value='" . $entity['id'] . "'>" . $entity['title'] . "</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="article_usage<?php echo $system['id']; ?>" class="form-label">الاستخدام</label>
                                                    <select class="form-select" id="article_usage<?php echo $system['id']; ?>" name="usage_id">
                                                        <option value="">-- اختر استخدام --</option>
                                                        <?php
                                                        $usages = getUsages();
                                                        foreach ($usages as $usage) {
                                                            echo "<option value='" . $usage['id'] . "'>" . $usage['title'] . "</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="article_references<?php echo $system['id']; ?>" class="form-label">المواد المرتبطة</label>
                                                    <select class="form-select" id="article_references<?php echo $system['id']; ?>" name="references[]" multiple>
                                                        <?php
                                                        $articles = getArticles();
                                                        foreach ($articles as $article) {
                                                            echo "<option value='" . $article['id'] . "'>" . $article['system_title'] . " - " . $article['title'] . "</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                    <div class="form-text">اختر المواد المرتبطة (يمكنك الاختيار المتعدد بالضغط على Ctrl)</div>
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
        <div class="modal fade" id="editArticleModal<?php echo $article['id']; ?>" data-article-id="<?php echo $article['id']; ?>" tabindex="-1" aria-hidden="true">
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
                            <div class="mb-3">
                                <label for="article_entity<?php echo $article['id']; ?>" class="form-label">الجهة المعنية</label>
                                <select class="form-select" id="article_entity<?php echo $article['id']; ?>" name="entity_id">
                                    <option value="">-- اختر جهة معنية --</option>
                                    <?php
                                    $entities = getEntities();
                                    $current_entity = getArticleEntity($article['id']);
                                    foreach ($entities as $entity) {
                                        $selected = ($current_entity && $current_entity['id'] == $entity['id']) ? 'selected' : '';
                                        echo "<option value='" . $entity['id'] . "' $selected>" . $entity['title'] . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="article_usage<?php echo $article['id']; ?>" class="form-label">الاستخدام</label>
                                <select class="form-select" id="article_usage<?php echo $article['id']; ?>" name="usage_id">
                                    <option value="">-- اختر استخدام --</option>
                                    <?php
                                    $usages = getUsages();
                                    $current_usage = getArticleUsage($article['id']);
                                    foreach ($usages as $usage) {
                                        $selected = ($current_usage && $current_usage['id'] == $usage['id']) ? 'selected' : '';
                                        echo "<option value='" . $usage['id'] . "' $selected>" . $usage['title'] . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="article_references<?php echo $article['id']; ?>" class="form-label">المواد المرتبطة</label>
                                <select class="form-select" id="article_references<?php echo $article['id']; ?>" name="references[]" multiple>
                                    <?php
                                    $articles = getArticles($article['id']);
                                    $references = getArticleReferences($article['id']);
                                    $reference_ids = [];
                                    foreach ($references as $ref) {
                                        $reference_ids[] = $ref['referenced_article_id'];
                                    }

                                    foreach ($articles as $article_option) {
                                        $selected = in_array($article_option['id'], $reference_ids) ? 'selected' : '';
                                        echo "<option value='" . $article_option['id'] . "' $selected>" . $article_option['system_title'] . " - " . $article_option['title'] . "</option>";
                                    }
                                    ?>
                                </select>
                                <div class="form-text">اختر المواد المرتبطة (يمكنك الاختيار المتعدد بالضغط على Ctrl)</div>
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
        <div class="modal fade" id="editSectionModal<?php echo $section['id']; ?>" data-section-id="<?php echo $section['id']; ?>" tabindex="-1" aria-hidden="true">
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
                            <div class="mb-3">
                                <label for="section_entity<?php echo $section['id']; ?>" class="form-label">الجهة المعنية</label>
                                <select class="form-select" id="section_entity<?php echo $section['id']; ?>" name="entity_id">
                                    <option value="">-- اختر جهة معنية --</option>
                                    <?php
                                    $entities = getEntities();
                                    $current_entity = getSectionEntity($section['id']);
                                    foreach ($entities as $entity) {
                                        $selected = ($current_entity && $current_entity['id'] == $entity['id']) ? 'selected' : '';
                                        echo "<option value='" . $entity['id'] . "' $selected>" . $entity['title'] . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="section_usage<?php echo $section['id']; ?>" class="form-label">الاستخدام</label>
                                <select class="form-select" id="section_usage<?php echo $section['id']; ?>" name="usage_id">
                                    <option value="">-- اختر استخدام --</option>
                                    <?php
                                    $usages = getUsages();
                                    $current_usage = getSectionUsage($section['id']);
                                    foreach ($usages as $usage) {
                                        $selected = ($current_usage && $current_usage['id'] == $usage['id']) ? 'selected' : '';
                                        echo "<option value='" . $usage['id'] . "' $selected>" . $usage['title'] . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="section_references<?php echo $section['id']; ?>" class="form-label">الأجزاء المرتبطة</label>
                                <select class="form-select" id="section_references<?php echo $section['id']; ?>" name="references[]" multiple>
                                    <?php
                                    $sections = getSections($section['id']);
                                    $references = getSectionReferences($section['id']);
                                    $reference_ids = [];
                                    foreach ($references as $ref) {
                                        $reference_ids[] = $ref['referenced_section_id'];
                                    }

                                    foreach ($sections as $section_option) {
                                        $selected = in_array($section_option['id'], $reference_ids) ? 'selected' : '';
                                        echo "<option value='" . $section_option['id'] . "' $selected>" . $section_option['system_title'] . " - " . $section_option['article_title'] . " - " . $section_option['title'] . "</option>";
                                    }
                                    ?>
                                </select>
                                <div class="form-text">اختر الأجزاء المرتبطة (يمكنك الاختيار المتعدد بالضغط على Ctrl)</div>
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

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // عند فتح أي modal
        $('.modal.fade').on('shown.bs.modal', function () {
            let modal = $(this);

            // اذا modal اضافة مادة
            if(modal.data('system-id')) {
                let id = modal.data('system-id');
                $('#article_entity' + id).select2({ dropdownParent: modal.find('.modal-content'), width: '100%' });
                $('#article_usage' + id).select2({ dropdownParent: modal.find('.modal-content'), width: '100%' });
                $('#article_references' + id).select2({ dropdownParent: modal.find('.modal-content'), width: '100%' });
            }

            // اذا modal تعديل مادة
            if(modal.data('article-id')) {
                let id = modal.data('article-id');
                $('#article_entity' + id).select2({ dropdownParent: modal.find('.modal-content'), width: '100%' });
                $('#article_usage' + id).select2({ dropdownParent: modal.find('.modal-content'), width: '100%' });
                $('#article_references' + id).select2({ dropdownParent: modal.find('.modal-content'), width: '100%' });
            }

            // اذا modal تعديل جزء
            if(modal.data('section-id')) {
                let id = modal.data('section-id');
                $('#section_entity' + id).select2({ dropdownParent: modal.find('.modal-content'), width: '100%' });
                $('#section_usage' + id).select2({ dropdownParent: modal.find('.modal-content'), width: '100%' });
                $('#section_references' + id).select2({ dropdownParent: modal.find('.modal-content'), width: '100%' });
            }

            // اذا modal اضافة نظام (select المواد والأجزاء لو موجودة)
            if(modal.attr('id') === 'addSystemModal') {
                modal.find('select').each(function(){
                    $(this).select2({ dropdownParent: modal.find('.modal-content'), width: '100%' });
                });
            }

            // اذا modal اضافة مادة (Add Article inside system)
            if(modal.attr('id').startsWith('addArticleModal')) {
                modal.find('select').each(function(){
                    $(this).select2({ dropdownParent: modal.find('.modal-content'), width: '100%' });
                });
            }
        });

    </script>
    <script>
        $(document).ready(function() {
            // تفعيل Select2 على جميع قوائم الاختيار المتعدد
            $('.select2-multiple').select2({
                placeholder: "اختر العناصر المرتبطة",
                allowClear: true,
                dir: "rtl",
                width: "100%",
                closeOnSelect: false,
                language: {
                    noResults: function() {
                        return "لا توجد نتائج";
                    },
                    searching: function() {
                        return "جاري البحث...";
                    },
                    inputTooShort: function() {
                        return "يرجى إدخال حرف واحد على الأقل";
                    },
                    removeAllItems: function() {
                        return "إزالة الكل";
                    }
                }
            });
            
            // تحسين شكل العناصر المختارة
            $(document).on('select2:open', function() {
                document.querySelector('.select2-search__field').focus();
            });
            
            // إضافة تصميم مخصص للعناصر المختارة
            $('.select2-selection__rendered').addClass('d-flex flex-wrap gap-1');
            
            // تخزين قوائم الاختيار في متغيرات JavaScript
            let entitiesOptions = `
                <option value="">-- اختر جهة معنية --</option>
                <?php
                $entities = getEntities();
                foreach ($entities as $entity) {
                    echo "<option value='" . $entity['id'] . "'>" . $entity['title'] . "</option>";
                }
                ?>
            `;

            let usagesOptions = `
                <option value="">-- اختر استخدام --</option>
                <?php
                $usages = getUsages();
                foreach ($usages as $usage) {
                    echo "<option value='" . $usage['id'] . "'>" . $usage['title'] . "</option>";
                }
                ?>
            `;
            
            let sectionsOptions = `
                <?php
                $sections = getSections();
                foreach ($sections as $section_option) {
                    echo "<option value='" . $section_option['id'] . "'>" . $section_option['system_title'] . " - " . $section_option['article_title'] . " - " . $section_option['title'] . "</option>";
                }
                ?>
            `;
            
            let articleCount = 0;
            let sectionCount = {};
            let subsectionCount = {};


            // عند فتح أي modal
            $('.modal.fade').on('shown.bs.modal', function () {
                let modal = $(this);

                // تهيئة كل select داخل المودال عند فتحه
                modal.find('select').each(function(){
                    if (!$(this).hasClass('select2-hidden-accessible')) { // نتأكد أنه لم يتم تهيئته مسبقًا
                        $(this).select2({ 
                            dropdownParent: modal.find('.modal-content'), 
                            width: '100%'
                        });
                    }
                });
            });

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
                            <label class="form-label">الجهة المعنية</label>
                            <select class="form-select" name="articles[${articleCount}][entity_id]">
                                <option value="">-- اختر جهة معنية --</option>
                                <?php
                                $entities = getEntities();
                                foreach ($entities as $entity) {
                                    echo "<option value='" . $entity['id'] . "'>" . $entity['title'] . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الاستخدام</label>
                            <select class="form-select" name="articles[${articleCount}][usage_id]">
                                <option value="">-- اختر استخدام --</option>
                                <?php
                                $usages = getUsages();
                                foreach ($usages as $usage) {
                                    echo "<option value='" . $usage['id'] . "'>" . $usage['title'] . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">المواد المرتبطة</label>
                            <select class="form-select" name="articles[${articleCount}][references][]" multiple>
                                <?php
                                $articles = getArticles();
                                foreach ($articles as $article_option) {
                                    echo "<option value='" . $article_option['id'] . "'>" . $article_option['system_title'] . " - " . $article_option['title'] . "</option>";
                                }
                                ?>
                            </select>
                            <div class="form-text">اختر المواد المرتبطة (يمكنك الاختيار المتعدد بالضغط على Ctrl)</div>
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
                 let modal = $('#addSystemModal');
                    modal.find(`#article-${articleCount} select`).each(function(){
                        $(this).select2({
                            dropdownParent: modal.find('.modal-content'),
                            width: '100%'
                        });
                    });

                sectionCount[articleCount] = 0;
                subsectionCount[articleCount] = {};
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
                sectionCount[articleId]++;

                let sectionHtml = `
                    <div class="section-item" id="section-${articleId}-${sectionCount[articleId]}">
                        <div class="section-item-header">
                            <h6>جزء ${sectionCount[articleId]}</h6>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-section" data-article="${articleId}" data-section="${sectionCount[articleId]}">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">عنوان الجزء</label>
                            <input type="text" class="form-control" name="articles[${articleId}][sections][${sectionCount[articleId]}][title]">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">محتوى الجزء</label>
                            <textarea class="form-control" name="articles[${articleId}][sections][${sectionCount[articleId]}][content]" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الجهة المعنية</label>
                            <select class="form-select" name="articles[${articleId}][sections][${sectionCount[articleId]}][entity_id]">
                                <option value="">-- اختر جهة معنية --</option>
                                <?php
                                $entities = getEntities();
                                foreach ($entities as $entity) {
                                    echo "<option value='" . $entity['id'] . "'>" . $entity['title'] . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الاستخدام</label>
                            <select class="form-select" name="articles[${articleId}][sections][${sectionCount[articleId]}][usage_id]">
                                <option value="">-- اختر استخدام --</option>
                                <?php
                                $usages = getUsages();
                                foreach ($usages as $usage) {
                                    echo "<option value='" . $usage['id'] . "'>" . $usage['title'] . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الأجزاء المرتبطة</label>
                            <select class="form-select" name="articles[${articleId}][sections][${sectionCount[articleId]}][references][]" multiple>
                                <?php
                                $sections = getSections();
                                foreach ($sections as $section_option) {
                                    echo "<option value='" . $section_option['id'] . "'>" . $section_option['system_title'] . " - " . $section_option['article_title'] . " - " . $section_option['title'] . "</option>";
                                }
                                ?>
                            </select>
                            <div class="form-text">اختر الأجزاء المرتبطة</div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">الأجزاء الفرعية</label>
                                <button type="button" class="btn btn-sm btn-outline-info add-subsection-btn" data-article="${articleId}" data-section="${sectionCount[articleId]}">
                                    <i class="fas fa-plus"></i> إضافة جزء فرعي
                                </button>
                            </div>
                            <div id="subsections-container-${articleId}-${sectionCount[articleId]}">
                                <!-- Subsections will be added here dynamically -->
                            </div>
                        </div>
                    </div>
                `;

                $(`#sections-container-${articleId}`).append(sectionHtml);
                
                // تفعيل Select2 على العناصر الجديدة
                 let modal = $(this).closest('.modal');
                    modal.find(`#section-${articleId}-${sectionCount[articleId]} select`).each(function(){
                        $(this).select2({
                            dropdownParent: modal.find('.modal-content'),
                            width: '100%'
                        });
                    });

                // Initialize subsection count for this section
                subsectionCount[articleId][sectionCount[articleId]] = 0;
            });

            // Remove Section Button Click
            $(document).on('click', '.remove-section', function() {
                let articleId = $(this).data('article');
                let sectionId = $(this).data('section');
                $(`#section-${articleId}-${sectionId}`).remove();
            });

            // Add Subsection Button Click
            $(document).on('click', '.add-subsection-btn', function() {
                let articleId = $(this).data('article');
                let sectionId = $(this).data('section');

                if (!subsectionCount[articleId][sectionId]) {
                    subsectionCount[articleId][sectionId] = 0;
                }
                subsectionCount[articleId][sectionId]++;

                let subsectionHtml = `
                    <div class="subsection-container" id="subsection-${articleId}-${sectionId}-${subsectionCount[articleId][sectionId]}">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6>جزء فرعي ${subsectionCount[articleId][sectionId]}</h6>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-subsection" data-article="${articleId}" data-section="${sectionId}" data-subsection="${subsectionCount[articleId][sectionId]}">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">عنوان الجزء الفرعي</label>
                            <input type="text" class="form-control" name="articles[${articleId}][sections][${sectionId}][subsections][${subsectionCount[articleId][sectionId]}][title]">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">محتوى الجزء الفرعي</label>
                            <textarea class="form-control" name="articles[${articleId}][sections][${sectionId}][subsections][${subsectionCount[articleId][sectionId]}][content]" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الجهة المعنية</label>
                            <select class="form-select" name="articles[${articleId}][sections][${sectionId}][subsections][${subsectionCount[articleId][sectionId]}][entity_id]">
                                <option value="">-- اختر جهة معنية --</option>
                                <?php
                                $entities = getEntities();
                                foreach ($entities as $entity) {
                                    echo "<option value='" . $entity['id'] . "'>" . $entity['title'] . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الاستخدام</label>
                            <select class="form-select" name="articles[${articleId}][sections][${sectionId}][subsections][${subsectionCount[articleId][sectionId]}][usage_id]">
                                <option value="">-- اختر استخدام --</option>
                                <?php
                                $usages = getUsages();
                                foreach ($usages as $usage) {
                                    echo "<option value='" . $usage['id'] . "'>" . $usage['title'] . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الأجزاء المرتبطة</label>
                            <select class="form-select" name="articles[${articleId}][sections][${sectionId}][subsections][${subsectionCount[articleId][sectionId]}][references][]" multiple>
                                <?php
                                $sections = getSections();
                                foreach ($sections as $section_option) {
                                    echo "<option value='" . $section_option['id'] . "'>" . $section_option['system_title'] . " - " . $section_option['article_title'] . " - " . $section_option['title'] . "</option>";
                                }
                                ?>
                            </select>
                            <div class="form-text">اختر الأجزاء المرتبطة</div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">أجزاء فرعية إضافية</label>
                                <button type="button" class="btn btn-sm btn-outline-info add-subsection-btn" data-article="${articleId}" data-section="${sectionId}" data-parent="${subsectionCount[articleId][sectionId]}">
                                    <i class="fas fa-plus"></i> إضافة جزء فرعي
                                </button>
                            </div>
                            <div id="subsubsections-container-${articleId}-${sectionId}-${subsectionCount[articleId][sectionId]}">
                                <!-- Sub-subsections will be added here dynamically -->
                            </div>
                        </div>
                    </div>
                `;

                if ($(this).data('parent') !== undefined) {
                    // This is a nested subsection
                    let parentId = $(this).data('parent');
                    $(`#subsubsections-container-${articleId}-${sectionId}-${parentId}`).append(subsectionHtml);
                } else {
                    // This is a direct subsection of a section
                    $(`#subsections-container-${articleId}-${sectionId}`).append(subsectionHtml);
                }
                
                // تفعيل Select2 على العناصر الجديدة
                let modal = $(this).closest('.modal');
                    modal.find(`#subsection-${articleId}-${sectionId}-${subsectionCount[articleId][sectionId]} select`).each(function(){
                        $(this).select2({
                            dropdownParent: modal.find('.modal-content'),
                            width: '100%'
                        });
                    });

                // Initialize sub-subsection count for this subsection
                if (!subsectionCount[articleId][sectionId + '_' + subsectionCount[articleId][sectionId]]) {
                    subsectionCount[articleId][sectionId + '_' + subsectionCount[articleId][sectionId]] = 0;
                }
            });

            // Remove Subsection Button Click
            $(document).on('click', '.remove-subsection', function() {
                let articleId = $(this).data('article');
                let sectionId = $(this).data('section');
                let subsectionId = $(this).data('subsection');
                $(`#subsection-${articleId}-${sectionId}-${subsectionId}`).remove();
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
                    div.className = 'section-item mb-3';
                    div.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6>جزء ${index}</h6>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-section">إزالة</button>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">عنوان الجزء</label>
                            <input type="text" class="form-control" name="sections[${index}][title]" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">محتوى الجزء</label>
                            <textarea class="form-control" name="sections[${index}][content]" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الجهة المعنية</label>
                            <select class="form-select" name="sections[${index}][entity_id]">
                                <option value="">-- اختر جهة معنية --</option>
                                <?php
                                $entities = getEntities();
                                foreach ($entities as $entity) {
                                    echo "<option value='" . $entity['id'] . "'>" . $entity['title'] . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الاستخدام</label>
                            <select class="form-select" name="sections[${index}][usage_id]">
                                <option value="">-- اختر استخدام --</option>
                                <?php
                                $usages = getUsages();
                                foreach ($usages as $usage) {
                                    echo "<option value='" . $usage['id'] . "'>" . $usage['title'] . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الأجزاء المرتبطة</label>
                            <select class="form-select" name="sections[${index}][references][]" multiple>
                                <?php
                                $sections = getSections();
                                foreach ($sections as $section_option) {
                                    echo "<option value='" . $section_option['id'] . "'>" . $section_option['system_title'] . " - " . $section_option['article_title'] . " - " . $section_option['title'] . "</option>";
                                }
                                ?>
                            </select>
                            <div class="form-text">اختر الأجزاء المرتبطة (يمكنك الاختيار المتعدد بالضغط على Ctrl)</div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">الأجزاء الفرعية</label>
                                <button type="button" class="btn btn-sm btn-outline-info add-subsection-btn" data-section="${index}">
                                    <i class="fas fa-plus"></i> إضافة جزء فرعي
                                </button>
                            </div>
                            <div id="subsections-container-${index}">
                                <!-- Subsections will be added here dynamically -->
                            </div>
                        </div>
                    `;

                    div.querySelector('.remove-section').addEventListener('click', () => div.remove());
                    container.appendChild(div);

                    // بعد إنشاء أي div جديد (section / subsection / subsubsection)
                    const modal = btn.closest('.modal'); // الحصول على المودال الحالي
                    div.querySelectorAll('select').forEach(sel => {
                        $(sel).select2({
                            dropdownParent: $(modal).find('.modal-content'),
                            width: '100%',
                            dir: 'rtl',
                            placeholder: "اختر من العناصر المتاحة",
                            allowClear: true
                        });
                    });


                    // Add subsection functionality
                    const addSubsectionBtn = div.querySelector('.add-subsection-btn');
                    addSubsectionBtn.addEventListener('click', () => {
                        const sectionIndex = addSubsectionBtn.dataset.section;
                        const subsectionContainer = document.getElementById(`subsections-container-${sectionIndex}`);
                        const subsectionIndex = subsectionContainer.querySelectorAll('.subsection-container').length + 1;

                        const subsectionDiv = document.createElement('div');
                        subsectionDiv.className = 'subsection-container mb-3';
                        subsectionDiv.innerHTML = `
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6>جزء فرعي ${subsectionIndex}</h6>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-subsection">إزالة</button>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">عنوان الجزء الفرعي</label>
                                <input type="text" class="form-control" name="sections[${sectionIndex}][subsections][${subsectionIndex}][title]" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">محتوى الجزء الفرعي</label>
                                <textarea class="form-control" name="sections[${sectionIndex}][subsections][${subsectionIndex}][content]" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">الجهة المعنية</label>
                                <select class="form-select" name="sections[${sectionIndex}][subsections][${subsectionIndex}][entity_id]">
                                    <option value="">-- اختر جهة معنية --</option>
                                    <?php
                                    $entities = getEntities();
                                    foreach ($entities as $entity) {
                                        echo "<option value='" . $entity['id'] . "'>" . $entity['title'] . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                            <label class="form-label">الاستخدام</label>
                            <select class="form-select" name="sections[${sectionIndex}][subsections][${subsectionIndex}][usage_id]">
                                <option value="">-- اختر استخدام --</option>
                                <?php
                                $usages = getUsages();
                                foreach ($usages as $usage) {
                                    echo "<option value='" . $usage['id'] . "'>" . $usage['title'] . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                            <div class="mb-3">
                                <label class="form-label">الأجزاء المرتبطة</label>
                                <select class="form-select" name="sections[${sectionIndex}][subsections][${subsectionIndex}][references][]" multiple>
                                    <?php
                                    $sections = getSections();
                                    foreach ($sections as $section_option) {
                                        echo "<option value='" . $section_option['id'] . "'>" . $section_option['system_title'] . " - " . $section_option['article_title'] . " - " . $section_option['title'] . "</option>";
                                    }
                                    ?>
                                </select>
                                <div class="form-text">اختر الأجزاء المرتبطة (يمكنك الاختيار المتعدد بالضغط على Ctrl)</div>
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0">أجزاء فرعية إضافية</label>
                                    <button type="button" class="btn btn-sm btn-outline-info add-subsubsection-btn" data-section="${sectionIndex}" data-subsection="${subsectionIndex}">
                                        <i class="fas fa-plus"></i> إضافة جزء فرعي
                                    </button>
                                </div>
                                <div id="subsubsections-container-${sectionIndex}-${subsectionIndex}">
                                    <!-- Sub-subsections will be added here dynamically -->
                                </div>
                            </div>
                        `;

                        subsectionDiv.querySelector('.remove-subsection').addEventListener('click', () => subsectionDiv.remove());
                        subsectionContainer.appendChild(subsectionDiv);

                        // بعد إنشاء أي div جديد (section / subsection / subsubsection)
                        const modal = btn.closest('.modal'); // الحصول على المودال الحالي
                        div.querySelectorAll('select').forEach(sel => {
                            $(sel).select2({
                                dropdownParent: $(modal).find('.modal-content'),
                                width: '100%',
                                dir: 'rtl',
                                placeholder: "اختر من العناصر المتاحة",
                                allowClear: true
                            });
                        });


                        // Add sub-subsection functionality
                        const addSubsubsectionBtn = subsectionDiv.querySelector('.add-subsubsection-btn');
                        addSubsubsectionBtn.addEventListener('click', () => {
                            const subSectionIndex = addSubsubsectionBtn.dataset.section;
                            const subsubsectionIndex = addSubsubsectionBtn.dataset.subsection;
                            const subsubsectionContainer = document.getElementById(`subsubsections-container-${subSectionIndex}-${subsubsectionIndex}`);
                            const subsubsectionIdx = subsubsectionContainer.querySelectorAll('.subsubsection-container').length + 1;

                            const subsubsectionDiv = document.createElement('div');
                            subsubsectionDiv.className = 'subsubsection-container mb-3';
                            subsubsectionDiv.innerHTML = `
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6>جزء فرعي ${subsubsectionIdx}</h6>
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-subsubsection">إزالة</button>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">عنوان الجزء الفرعي</label>
                                    <input type="text" class="form-control" name="sections[${subSectionIndex}][subsections][${subsubsectionIndex}][subsubsections][${subsubsectionIdx}][title]" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">محتوى الجزء الفرعي</label>
                                    <textarea class="form-control" name="sections[${subSectionIndex}][subsections][${subsubsectionIndex}][subsubsections][${subsubsectionIdx}][content]" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                <label class="form-label">الجهة المعنية</label>
                                <select class="form-select" name="sections[${sectionIndex}][subsections][${subsectionIndex}][entity_id]">
                                    <option value="">-- اختر جهة معنية --</option>
                                    <?php
                                    $entities = getEntities();
                                    foreach ($entities as $entity) {
                                        echo "<option value='" . $entity['id'] . "'>" . $entity['title'] . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                            <label class="form-label">الاستخدام</label>
                            <select class="form-select" name="sections[${sectionIndex}][subsections][${subsectionIndex}][usage_id]">
                                <option value="">-- اختر استخدام --</option>
                                <?php
                                $usages = getUsages();
                                foreach ($usages as $usage) {
                                    echo "<option value='" . $usage['id'] . "'>" . $usage['title'] . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                            <div class="mb-3">
                                <label class="form-label">الأجزاء المرتبطة</label>
                                <select class="form-select" name="sections[${sectionIndex}][subsections][${subsectionIndex}][references][]" multiple>
                                    <?php
                                    $sections = getSections();
                                    foreach ($sections as $section_option) {
                                        echo "<option value='" . $section_option['id'] . "'>" . $section_option['system_title'] . " - " . $section_option['article_title'] . " - " . $section_option['title'] . "</option>";
                                    }
                                    ?>
                                </select>
                                <div class="form-text">اختر الأجزاء المرتبطة (يمكنك الاختيار المتعدد بالضغط على Ctrl)</div>
                            </div>
                            `;

                            subsubsectionDiv.querySelector('.remove-subsubsection').addEventListener('click', () => subsubsectionDiv.remove());
                            subsubsectionContainer.appendChild(subsubsectionDiv);
                            // بعد إنشاء أي div جديد (section / subsection / subsubsection)
                            const modal = btn.closest('.modal'); // الحصول على المودال الحالي
                            div.querySelectorAll('select').forEach(sel => {
                                $(sel).select2({
                                    dropdownParent: $(modal).find('.modal-content'),
                                    width: '100%',
                                    dir: 'rtl',
                                    placeholder: "اختر من العناصر المتاحة",
                                    allowClear: true
                                });
                            });

                        });
                    });
                });
            });
        });
    </script>

</body>
</html>