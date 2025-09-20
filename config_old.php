
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
?>