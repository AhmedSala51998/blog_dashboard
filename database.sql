
-- قاعدة بيانات لوحة تحكم المدونات

-- جدول المستخدمين
CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('admin', 'editor', 'user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول الأنظمة والقوانين
CREATE TABLE IF NOT EXISTS systems (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- جدول المواد القانونية
CREATE TABLE IF NOT EXISTS articles (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    system_id INT(11) NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (system_id) REFERENCES systems(id) ON DELETE CASCADE
);

-- جدول الأجزاء داخل المواد
CREATE TABLE IF NOT EXISTS sections (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    article_id INT(11) NOT NULL,
    parent_id INT(11) NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES sections(id) ON DELETE CASCADE
);

-- جدول المدونات
CREATE TABLE IF NOT EXISTS blogs (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    pdf_path VARCHAR(255),
    video_url VARCHAR(255),
    image_url VARCHAR(255),
    external_link VARCHAR(255),
    reference_system_id TEXT,
    reference_article_id TEXT,
    reference_section_id TEXT,
    reference_subsection_id TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- جدول مراجع المواد
CREATE TABLE IF NOT EXISTS article_references (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    article_id INT(11) NOT NULL,
    referenced_article_id INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (referenced_article_id) REFERENCES articles(id) ON DELETE CASCADE,
    UNIQUE KEY (article_id, referenced_article_id)
);

-- جدول مراجع الأجزاء
CREATE TABLE IF NOT EXISTS section_references (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    section_id INT(11) NOT NULL,
    referenced_section_id INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    FOREIGN KEY (referenced_section_id) REFERENCES sections(id) ON DELETE CASCADE,
    UNIQUE KEY (section_id, referenced_section_id)
);

-- إضافة مستخدم افتراضي (كلمة المرور: admin123)
INSERT INTO users (username, password, email, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'admin');
