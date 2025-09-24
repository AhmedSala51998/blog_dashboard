-- إضافة حقل الاستخدامات لجدول المواد
ALTER TABLE Articles ADD COLUMN usage_id INT NULL;

-- إضافة حقل الاستخدامات لجدول الأجزاء
ALTER TABLE Sections ADD COLUMN usage_id INT NULL;

-- إضافة علاقة بين جدول الاستخدامات وجدول المواد
ALTER TABLE Articles ADD CONSTRAINT fk_articles_usage FOREIGN KEY (usage_id) REFERENCES Usages(id) ON DELETE SET NULL;

-- إضافة علاقة بين جدول الاستخدامات وجدول الأجزاء
ALTER TABLE Sections ADD CONSTRAINT fk_sections_usage FOREIGN KEY (usage_id) REFERENCES Usages(id) ON DELETE SET NULL;
