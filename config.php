
<?php
// إعدادات الاتصال بقاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_USER', 'u552468652_blog_system');
define('DB_PASS', 'Blog12345@#');
define('DB_NAME', 'u552468652_blog_system');

// إنشاء الاتصال
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// التحقق من الاتصال
if (!$conn) {
    die("فشل الاتصال بقاعدة البيانات: " . mysqli_connect_error());
}

// تعيين ترميز الاتصال
mysqli_set_charset($conn, "utf8mb4");

// بدء الجلسة
session_start();

// التحقق من تسجيل دخول المستخدم
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// التحقق من صلاحيات المسؤول
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// التحقق من صلاحيات المحرر
function isEditor() {
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'editor');
}

// دالة لحماية الصفحات
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// دالة لحماية صفحات المسؤولين
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: index.php");
        exit();
    }
}

// دالة لحماية صفحات المحررين
function requireEditor() {
    requireLogin();
    if (!isEditor()) {
        header("Location: index.php");
        exit();
    }
}

// دالة لتنظيف المدخلات
function cleanInput($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}

// دالة لعرض رسائل النجاح والخطأ
function showMessage() {
    if (isset($_SESSION['message'])) {
        $type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'info';
        echo "<div class='alert alert-{$type}'>{$_SESSION['message']}</div>";
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }
}

// دالة للحصول على قائمة الجهات المعنية
function getEntities() {
    global $conn;
    $entities = [];
    $sql = "SELECT * FROM entities ORDER BY name ASC";
    $result = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $entities[] = $row;
    }
    return $entities;
}

// دالة للحصول على قائمة المواد
function getArticles($exclude_id = null) {
    global $conn;
    $articles = [];
    $sql = "SELECT a.*, s.title as system_title FROM articles a 
            JOIN systems s ON a.system_id = s.id";
    if ($exclude_id) {
        $sql .= " WHERE a.id != " . intval($exclude_id);
    }
    $sql .= " ORDER BY s.title, a.title";
    $result = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $articles[] = $row;
    }
    return $articles;
}

// دالة للحصول على قائمة الأجزاء
function getSections($exclude_id = null) {
    global $conn;
    $sections = [];
    $sql = "SELECT s.*, a.title as article_title, sys.title as system_title FROM sections s 
            JOIN articles a ON s.article_id = a.id 
            JOIN systems sys ON a.system_id = sys.id";
    if ($exclude_id) {
        $sql .= " WHERE s.id != " . intval($exclude_id);
    }
    $sql .= " ORDER BY sys.title, a.title, s.title";
    $result = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $sections[] = $row;
    }
    return $sections;
}

// دالة للحصول على مراجع المادة
function getArticleReferences($article_id) {
    global $conn;
    $references = [];
    $sql = "SELECT ar.*, a.title as referenced_article_title, sys.title as system_title 
            FROM article_references ar 
            JOIN articles a ON ar.referenced_article_id = a.id 
            JOIN systems sys ON a.system_id = sys.id 
            WHERE ar.article_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $article_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $references[] = $row;
    }
    return $references;
}

// دالة للحصول على مراجع الجزء
function getSectionReferences($section_id) {
    global $conn;
    $references = [];
    $sql = "SELECT sr.*, s.title as referenced_section_title, a.title as article_title, sys.title as system_title 
            FROM section_references sr 
            JOIN sections s ON sr.referenced_section_id = s.id 
            JOIN articles a ON s.article_id = a.id 
            JOIN systems sys ON a.system_id = sys.id 
            WHERE sr.section_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $section_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $references[] = $row;
    }
    return $references;
}

// دالة للحصول على الجهة المعنية للمادة
function getArticleEntity($article_id) {
    global $conn;
    $sql = "SELECT e.* FROM entities e 
            JOIN articles a ON e.id = a.entity_id 
            WHERE a.id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $article_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

// دالة للحصول على الجهة المعنية للجزء
function getSectionEntity($section_id) {
    global $conn;
    $sql = "SELECT e.* FROM entities e 
            JOIN sections s ON e.id = s.entity_id 
            WHERE s.id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $section_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}
?>
