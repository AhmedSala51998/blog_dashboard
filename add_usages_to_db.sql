-- إضافة حقل usage_id لجدول articles
ALTER TABLE articles ADD COLUMN usage_id INT NULL;

-- إضافة حقل usage_id لجدول sections
ALTER TABLE sections ADD COLUMN usage_id INT NULL;

-- إضافة حقل usage_id لجدول subsections
ALTER TABLE subsections ADD COLUMN usage_id INT NULL;

-- إضافة مفتاح خارجي لجدول articles
ALTER TABLE articles ADD FOREIGN KEY (usage_id) REFERENCES usages(id) ON DELETE SET NULL;

-- إضافة مفتاح خارجي لجدول sections
ALTER TABLE sections ADD FOREIGN KEY (usage_id) REFERENCES usages(id) ON DELETE SET NULL;

-- إضافة مفتاح خارجي لجدول subsections
ALTER TABLE subsections ADD FOREIGN KEY (usage_id) REFERENCES usages(id) ON DELETE SET NULL;
