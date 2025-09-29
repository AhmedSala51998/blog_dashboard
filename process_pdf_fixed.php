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
        // إضافة فواصل بين المواد والأجزاء والأجزاء الفرعية
        $text = preg_replace('/(مادة\s+\d+)/i', "===ARTICLE===\n$1", $text);
        $text = preg_replace('/(الجزء\s+\d+)/i', "===SECTION===\n$1", $text);
        $text = preg_replace('/(الجزء\s+الفرعي\s+\d+)/i', "===SUBSECTION===\n$1", $text);

        // تقسيم النص إلى أجزاء
        $parts = explode("===ARTICLE===", $text);

        $articles_count = 0;
        $sections_count = 0;
        $subsections_count = 0;

        // معالجة كل مادة على حدة
        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;

            // استخراج عنوان المادة
            $article_lines = explode("\n", $part);
            $article_title = trim($article_lines[0]);

            // التحقق إذا كان العنوان يبدأ بكلمة "مادة" متبوعة برقم
            if (!preg_match('/^مادة\s+\d+/i', $article_title)) continue;

            // إضافة المادة لقاعدة البيانات
            $article_title = cleanInput($article_title);
            $sql = "INSERT INTO articles (system_id, title, content) VALUES (?, ?, '')";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "is", $system_id, $article_title);
            mysqli_stmt_execute($stmt);

            $article_id = mysqli_insert_id($conn);
            $articles_count++;

            // إزالة عنوان المادة من النص
            array_shift($article_lines);
            $article_content = implode("\n", $article_lines);

            // تقسيم محتوى المادة إلى أجزاء
            $sections = explode("===SECTION===", $article_content);

            // معالجة كل جزء على حدة
            foreach ($sections as $section) {
                $section = trim($section);
                if (empty($section)) continue;

                // استخراج عنوان الجزء
                $section_lines = explode("\n", $section);
                $section_title = trim($section_lines[0]);

                // التحقق إذا كان العنوان يبدأ بكلمة "الجزء" متبوعة برقم
                if (!preg_match('/^الجزء\s+\d+/i', $section_title)) {
                    // إذا لم يكن جزءاً، أضفه كمحتوى للمادة
                    $article_content .= "\n" . $section;
                    continue;
                }

                // إضافة الجزء لقاعدة البيانات
                $section_title = cleanInput($section_title);
                $sql = "INSERT INTO sections (article_id, title, content) VALUES (?, ?, '')";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "is", $article_id, $section_title);
                mysqli_stmt_execute($stmt);

                $section_id = mysqli_insert_id($conn);
                $sections_count++;

                // إزالة عنوان الجزء من النص
                array_shift($section_lines);
                $section_content = implode("\n", $section_lines);

                // تقسيم محتوى الجزء إلى أجزاء فرعية
                $subsections = explode("===SUBSECTION===", $section_content);

                // معالجة كل جزء فرعي على حدة
                foreach ($subsections as $subsection) {
                    $subsection = trim($subsection);
                    if (empty($subsection)) continue;

                    // استخراج عنوان الجزء الفرعي
                    $subsection_lines = explode("\n", $subsection);
                    $subsection_title = trim($subsection_lines[0]);

                    // التحقق إذا كان العنوان يبدأ بكلمة "الجزء الفرعي" متبوعة برقم
                    if (!preg_match('/^الجزء\s+الفرعي\s+\d+/i', $subsection_title)) {
                        // إذا لم يكن جزءاً فرعياً، أضفه كمحتوى للجزء
                        $section_content .= "\n" . $subsection;
                        continue;
                    }

                    // إضافة الجزء الفرعي لقاعدة البيانات
                    $subsection_title = cleanInput($subsection_title);
                    $subsection_content = trim(implode("\n", array_slice($subsection_lines, 1)));
                    $sql = "INSERT INTO sections (article_id, title, content, parent_id) VALUES (?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "issi", $article_id, $subsection_title, $subsection_content, $section_id);
                    mysqli_stmt_execute($stmt);

                    $subsections_count++;
                }

                // تحديث محتوى الجزء
                $sql = "UPDATE sections SET content = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "si", $section_content, $section_id);
                mysqli_stmt_execute($stmt);
            }

            // تحديث محتوى المادة
            $sql = "UPDATE articles SET content = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $article_content, $article_id);
            mysqli_stmt_execute($stmt);
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