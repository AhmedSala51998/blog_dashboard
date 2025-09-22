
<?php
require_once 'config.php';

// التحقق من تسجيل دخول المستخدم
requireLogin();

// معالجة طلبات تسجيل الخروج
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - المدونات</title>
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

        .stat-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card .icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .stat-card h3 {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }

        .stat-card p {
            color: var(--secondary-color);
            margin-bottom: 0;
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

        .recent-activity {
            max-height: 300px;
            overflow-y: auto;
        }

        .activity-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-item .time {
            font-size: 0.8rem;
            color: var(--secondary-color);
        }

        .footer {
            background-color: var(--dark-color);
            color: white;
            padding: 20px 0;
            margin-top: 30px;
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
                    <a class="nav-link active" href="index.php">
                        <i class="fas fa-home"></i> الرئيسية
                    </a>
                    <a class="nav-link" href="systems.php">
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
                    <a class="nav-link" href="usages.php"><i class="fas fa-cogs"></i> الاستخدامات</a>
                    <a class="nav-link" href="?logout=true">
                        <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                <!-- Top Navbar -->
                <div class="top-navbar">
                    <div class="d-flex justify-content-between align-items-center px-4">
                        <h2>لوحة تحكم المدونات</h2>
                        <div class="user-info">
                            <span>مرحباً، <?php echo $_SESSION['username']; ?></span>
                            <img src="https://picsum.photos/seed/user<?php echo $_SESSION['user_id']; ?>/40/40.jpg" alt="User Avatar">
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="content">
                    <?php showMessage(); ?>

                    <!-- Stats Cards -->
                    <div class="row">
                        <div class="col-md-3">
                            <div class="stat-card bg-primary text-white">
                                <div class="icon"><i class="fas fa-gavel"></i></div>
                                <h3>
                                    <?php
                                    $sql = "SELECT COUNT(*) as total FROM systems";
                                    $result = mysqli_query($conn, $sql);
                                    $data = mysqli_fetch_assoc($result);
                                    echo $data['total'];
                                    ?>
                                </h3>
                                <p style="color:#FFF">الأنظمة والقوانين</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card bg-success text-white">
                                <div class="icon"><i class="fas fa-newspaper"></i></div>
                                <h3>
                                    <?php
                                    $sql = "SELECT COUNT(*) as total FROM blogs";
                                    $result = mysqli_query($conn, $sql);
                                    $data = mysqli_fetch_assoc($result);
                                    echo $data['total'];
                                    ?>
                                </h3>
                                <p style="color:#FFF">المدونات</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card bg-info text-white">
                                <div class="icon"><i class="fas fa-users"></i></div>
                                <h3>
                                    <?php
                                    $sql = "SELECT COUNT(*) as total FROM users";
                                    $result = mysqli_query($conn, $sql);
                                    $data = mysqli_fetch_assoc($result);
                                    echo $data['total'];
                                    ?>
                                </h3>
                                <p style="color:#FFF">المستخدمين</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card bg-warning text-white">
                                <div class="icon"><i class="fas fa-file-alt"></i></div>
                                <h3>
                                    <?php
                                    $sql = "SELECT COUNT(*) as total FROM articles";
                                    $result = mysqli_query($conn, $sql);
                                    $data = mysqli_fetch_assoc($result);
                                    echo $data['total'];
                                    ?>
                                </h3>
                                <p style="color:#FFF">المواد القانونية</p>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">آخر النشاطات</h5>
                                </div>
                                <div class="card-body recent-activity">
                                    <?php
                                    // استعلام لجلب آخر النشاطات
                                    $sql = "SELECT 'system' as type, title, created_at FROM systems 
                                            UNION ALL
                                            SELECT 'blog' as type, title, created_at FROM blogs
                                            UNION ALL
                                            SELECT 'article' as type, title, created_at FROM articles
                                            ORDER BY created_at DESC LIMIT 10";
                                    $result = mysqli_query($conn, $sql);

                                    if (mysqli_num_rows($result) > 0) {
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            $icon = '';
                                            $typeText = '';

                                            switch ($row['type']) {
                                                case 'system':
                                                    $icon = 'fas fa-gavel';
                                                    $typeText = 'نظام/قانون';
                                                    break;
                                                case 'blog':
                                                    $icon = 'fas fa-newspaper';
                                                    $typeText = 'مدونة';
                                                    break;
                                                case 'article':
                                                    $icon = 'fas fa-file-alt';
                                                    $typeText = 'مادة قانونية';
                                                    break;
                                            }

                                            echo '<div class="activity-item">';
                                            echo '<div class="d-flex justify-content-between">';
                                            echo '<div><i class="' . $icon . '"></i> ' . $row['title'] . ' <span class="badge bg-secondary">' . $typeText . '</span></div>';
                                            echo '<div class="time">' . date('Y/m/d H:i', strtotime($row['created_at'])) . '</div>';
                                            echo '</div>';
                                            echo '</div>';
                                        }
                                    } else {
                                        echo '<p class="text-center text-muted">لا توجد نشاطات حديثة</p>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">معلومات سريعة</h5>
                                </div>
                                <div class="card-body">
                                    <p><i class="fas fa-info-circle text-primary"></i> مرحباً بك في لوحة تحكم المدونات</p>
                                    <p><i class="fas fa-user text-success"></i> أنت مسجل الدخول كـ: <strong><?php echo $_SESSION['username']; ?></strong></p>
                                    <p><i class="fas fa-shield-alt text-warning"></i> صلاحياتك: <strong>
                                    <?php 
                                    switch ($_SESSION['role']) {
                                        case 'admin':
                                            echo 'مسؤول';
                                            break;
                                        case 'editor':
                                            echo 'محرر';
                                            break;
                                        default:
                                            echo 'مستخدم';
                                            break;
                                    }
                                    ?>
                                    </strong></p>
                                    <p><i class="fas fa-clock text-info"></i> آخر تسجيل دخول: <strong><?php echo date('Y/m/d H:i'); ?></strong></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12 text-center">
                    <p>&copy; <?php echo date('Y'); ?> لوحة تحكم المدونات. جميع الحقوق محفوظة.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>
