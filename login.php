
<?php
require_once 'config.php';

// إذا كان المستخدم مسجل دخوله بالفعل، قم بتحويله للصفحة الرئيسية
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

// معالجة نموذج تسجيل الدخول
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = cleanInput($_POST['username']);
    $password = $_POST['password'];

    // استعلام للتحقق من بيانات المستخدم
    $sql = "SELECT id, username, password, role FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);

        // التحقق من كلمة المرور
        if (password_verify($password, $user['password'])) {
            // حفظ بيانات المستخدم في الجلسة
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // توجيه المستخدم للصفحة الرئيسية
            header("Location: index.php");
            exit();
        } else {
            $_SESSION['message'] = "كلمة المرور غير صحيحة!";
            $_SESSION['message_type'] = "danger";
        }
    } else {
        $_SESSION['message'] = "اسم المستخدم غير موجود!";
        $_SESSION['message_type'] = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - لوحة تحكم المدونات</title>
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h2 {
            color: #343a40;
            font-weight: 700;
        }
        .login-header i {
            font-size: 48px;
            color: #0d6efd;
            margin-bottom: 15px;
        }
        .form-control {
            border-radius: 5px;
            padding: 12px;
        }
        .btn-primary {
            background-color: #0d6efd;
            border: none;
            padding: 12px;
            font-weight: 600;
            border-radius: 5px;
            width: 100%;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
        }
        .alert {
            margin-bottom: 20px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <i class="fas fa-blog"></i>
                <h2>لوحة تحكم المدونات</h2>
                <p class="text-muted">تسجيل الدخول إلى حسابك</p>
            </div>

            <?php showMessage(); ?>

            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="mb-3">
                    <label for="username" class="form-label">اسم المستخدم</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">كلمة المرور</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">تسجيل الدخول</button>
                </div>
            </form>

            <div class="text-center mt-3">
                <p class="text-muted">اسم المستخدم: admin | كلمة المرور: admin123</p>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
