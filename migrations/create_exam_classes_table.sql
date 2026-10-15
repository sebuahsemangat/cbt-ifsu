-- Migrasi: Tabel relasi kelas yang diizinkan mengakses ujian (exam_classes)
CREATE TABLE IF NOT EXISTS exam_classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    class_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_exam_id (exam_id),
    INDEX idx_class_name (class_name),
    UNIQUE KEY uq_exam_class (exam_id, class_name),
    CONSTRAINT fk_exam_classes_exam FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
