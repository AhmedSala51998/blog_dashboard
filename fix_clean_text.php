<?php
// ملف لإصلاح دالة cleanExtractedText في ملف systems.php

// قراءة محتوى الملف الأصلي
$systems_file = __DIR__ . '/systems.php';
$content = file_get_contents($systems_file);

// استبدال دالة cleanExtractedText بالكامل
$old_function = 'function cleanExtractedText($text) {
    // تسجيل النص الأصلي قبل التنظيف
    logDebug("النص الأصلي قبل التنظيف", substr($text, 0, 500));

    // إزالة الأسطر الفارغة المتكررة
    $text = preg_replace("/
\s*
\s*
/", "
", $text);

    // إزالة المسافات الزائدة مع الحفاظ على فواصل الأسطر
    $lines = explode("
", $text);
    foreach ($lines as &$line) {
        $line = trim($line);
    }
    $text = implode("
", $lines);

    // إصلاح فواصل الأسطر
    $text = str_replace("-
", "", $text);

    // إزالة الأحرف الغريبة
    $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);

    // تسجيل النص بعد التنظيف
    logDebug("النص بعد التنظيف", substr($text, 0, 500));

    return $text;
}';

$new_function = 'function cleanExtractedText($text) {
    // تسجيل النص الأصلي قبل التنظيف
    logDebug("النص الأصلي قبل التنظيف", substr($text, 0, 500));

    // إزالة الأسطر الفارغة المتكررة مع الحفاظ على فواصل الأسطر
    $text = preg_replace("/
\s*
\s*
/", "

", $text);

    // إزالة المسافات الزائدة في بداية ونهاية كل سطر
    $lines = explode("
", $text);
    foreach ($lines as &$line) {
        $line = trim($line);
    }
    $text = implode("
", $lines);

    // إصلاح فواصل الأسطر - إزالة الأسطر التي تحتوي فقط على واصلة
    $text = preg_replace("/^\s*-\s*$/m", "", $text);

    // إزالة الأحرف الغريبة مع الحفاظ على الأسطر
    $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);

    // تسجيل النص بعد التنظيف
    logDebug("النص بعد التنظيف", substr($text, 0, 500));

    return $text;
}';

// استبدال الدالة
$content = str_replace($old_function, $new_function, $content);

// كتابة المحتوى المعدل إلى الملف
file_put_contents($systems_file, $content);

echo "تم تعديل دالة cleanExtractedText بنجاح";
?>
