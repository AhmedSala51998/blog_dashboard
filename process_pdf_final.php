<?php
// دالة لمعالجة ملف PDF واستخراج البيانات
function processPDFFile($file_path, $system_id) {
    global $conn;

    // استخراج النص من ملف PDF
    require_once 'vendor/autoload.php';

    try {
        // استخدام مكتبة PDFParser لاستخراج النص من ملف PDF
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($file_path);
        $text = $pdf->getText();

        // معالجة النص المستخرج لضمان التنسيق الصحيح
        // إضافة فواصل واضحة بين المواد والأجزاء والأجزاء الفرعية
        $text = preg_replace('/(مادة\s+\d+)/i', "===ARTICLE===\n$1", $text);
        $text = preg_replace('/(الجزء\s+\d+)/i', "===SECTION===\n$1", $text);
        $text = preg_replace('/(الجزء\s+الفرعي\s+\d+)/i', "===SUBSECTION===\n$1", $text);

        // تقسيم النص إلى أسطر
        $lines = explode("\n", $text);

        $articles_count = 0;
        $sections_count = 0;
        $subsections_count = 0;

        // متغيرات لتتبع العناصر الحالية
        $current_article_id = null;
        $current_section_id = null;
        $current_article_content = "";
        $current_section_content = "";
        $current_subsection_content = "";

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // التحقق إذا كان السطر يبدأ بكلمة "مادة" متبوعة برقم
            if (preg_match('/^مادة\s+(\d+)/i', $line, $matches)) {
                // إذا كان هناك مادة سابقة، قم بإغلاقها
                if ($current_article_id !== null) {
                    // تحديث محتوى المادة الحالية
                    $sql = "UPDATE articles SET content = ? WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "si", $current_article_content, $current_article_id);
                    mysqli_stmt_execute($stmt);
                }

                // إضافة مادة جديدة
                $article_title = cleanInput($line);
                $article_content = "";
                $sql = "INSERT INTO articles (system_id, title, content) VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "iss", $system_id, $article_title, $article_content);
                mysqli_stmt_execute($stmt);

                $current_article_id = mysqli_insert_id($conn);
                $current_section_id = null;
                $current_article_content = "";
                $current_section_content = "";
                $current_subsection_content = "";
                $articles_count++;
            }
            // التحقق إذا كان السطر يبدأ بكلمة "الجزء" متبوعة برقم
            else if (preg_match('/^الجزء\s+(\d+)/i', $line, $matches) && $current_article_id !== null) {
                // إذا كان هناك جزء سابق، قم بإغلاقه
                if ($current_section_id !== null) {
                    // تحديث محتوى الجزء الحالي
                    $sql = "UPDATE sections SET content = ? WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "si", $current_section_content, $current_section_id);
                    mysqli_stmt_execute($stmt);
                }

                // إضافة جزء جديد
                $section_title = cleanInput($line);
                $section_content = "";
                $sql = "INSERT INTO sections (article_id, title, content) VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "iss", $current_article_id, $section_title, $section_content);
                mysqli_stmt_execute($stmt);

                $current_section_id = mysqli_insert_id($conn);
                $current_section_content = "";
                $current_subsection_content = "";
                $sections_count++;
            }
            // التحقق إذا كان السطر يبدأ بكلمة "الجزء الفرعي" متبوعة برقم
            else if (preg_match('/^الجزء\s+الفرعي\s+(\d+)/i', $line, $matches) && $current_section_id !== null) {
                // إذا كان هناك جزء فرعي سابق، قم بإغلاقه
                if (!empty($current_subsection_content)) {
                    // إضافة جزء فرعي جديد
                    $subsection_title = cleanInput($line);
                    $subsection_content = "";
                    $sql = "INSERT INTO sections (article_id, title, content, parent_id) VALUES (?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "issi", $current_article_id, $subsection_title, $subsection_content, $current_section_id);
                    mysqli_stmt_execute($stmt);
                    $subsections_count++;
                }

                // بدء جزء فرعي جديد
                $current_subsection_content = "";
            }
            // إضافة المحتوى للعنصر الحالي
            else {
                if (!empty($current_subsection_content)) {
                    $current_subsection_content .= (empty($current_subsection_content) ? '' : "\n") . $line;
                } else if ($current_section_id !== null) {
                    $current_section_content .= (empty($current_section_content) ? '' : "\n") . $line;
                } else if ($current_article_id !== null) {
                    $current_article_content .= (empty($current_article_content) ? '' : "\n") . $line;
                }
            }
        }

        // إغلاق العناصر المتبقية
        if ($current_article_id !== null) {
            // تحديث محتوى المادة الحالية
            $sql = "UPDATE articles SET content = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $current_article_content, $current_article_id);
            mysqli_stmt_execute($stmt);
        }

        if ($current_section_id !== null) {
            // تحديث محتوى الجزء الحالي
            $sql = "UPDATE sections SET content = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $current_section_content, $current_section_id);
            mysqli_stmt_execute($stmt);
        }

        if (!empty($current_subsection_content) && $current_section_id !== null) {
            // إضافة الجزء الفرعي الأخير
            $subsection_title = cleanInput("الجزء الفرعي");
            $subsection_content = $current_subsection_content;
            $sql = "INSERT INTO sections (article_id, title, content, parent_id) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "issi", $current_article_id, $subsection_title, $subsection_content, $current_section_id);
            mysqli_stmt_execute($stmt);
            $subsections_count++;
        }

        return [
            'success' => true,
            'articles_count' => $articles_count,
            'sections_count' => $sections_count,
            'subsections_count' => $subsections_count
        ];

    } catch (Exception $e) {
        // في حالة وجود خطأ في معالجة ملف PDF
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}
?>