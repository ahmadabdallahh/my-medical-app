-- Ratings and Reviews Tables for Doctors

-- Doctor Ratings Table
CREATE TABLE IF NOT EXISTS doctor_ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    user_id INT NOT NULL,
    rating TINYINT(1) NOT NULL COMMENT '1-5 stars',
    review TEXT NULL COMMENT 'Optional review text',
    is_anonymous TINYINT(1) DEFAULT 0 COMMENT 'Whether the review is anonymous',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_doctor_user_rating (doctor_id, user_id),
    
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_user_id (user_id),
    INDEX idx_rating (rating),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Update doctors table to include calculated rating
ALTER TABLE doctors ADD COLUMN IF NOT EXISTS calculated_rating DECIMAL(3,2) DEFAULT 0.00 COMMENT 'Calculated average rating';
ALTER TABLE doctors ADD COLUMN IF NOT EXISTS total_ratings INT DEFAULT 0 COMMENT 'Total number of ratings';

-- Create trigger to update doctor's calculated rating when ratings change
DELIMITER //
CREATE TRIGGER IF NOT EXISTS update_doctor_rating_after_insert
AFTER INSERT ON doctor_ratings
FOR EACH ROW
BEGIN
    UPDATE doctors d SET 
        d.calculated_rating = (
            SELECT COALESCE(AVG(rating), 0) 
            FROM doctor_ratings 
            WHERE doctor_id = NEW.doctor_id
        ),
        d.total_ratings = (
            SELECT COUNT(*) 
            FROM doctor_ratings 
            WHERE doctor_id = NEW.doctor_id
        )
    WHERE d.user_id = NEW.doctor_id;
END//
DELIMITER ;

DELIMITER //
CREATE TRIGGER IF NOT EXISTS update_doctor_rating_after_update
AFTER UPDATE ON doctor_ratings
FOR EACH ROW
BEGIN
    UPDATE doctors d SET 
        d.calculated_rating = (
            SELECT COALESCE(AVG(rating), 0) 
            FROM doctor_ratings 
            WHERE doctor_id = NEW.doctor_id
        ),
        d.total_ratings = (
            SELECT COUNT(*) 
            FROM doctor_ratings 
            WHERE doctor_id = NEW.doctor_id
        )
    WHERE d.user_id = NEW.doctor_id;
END//
DELIMITER ;

DELIMITER //
CREATE TRIGGER IF NOT EXISTS update_doctor_rating_after_delete
AFTER DELETE ON doctor_ratings
FOR EACH ROW
BEGIN
    UPDATE doctors d SET 
        d.calculated_rating = (
            SELECT COALESCE(AVG(rating), 0) 
            FROM doctor_ratings 
            WHERE doctor_id = OLD.doctor_id
        ),
        d.total_ratings = (
            SELECT COUNT(*) 
            FROM doctor_ratings 
            WHERE doctor_id = OLD.doctor_id
        )
    WHERE d.user_id = OLD.doctor_id;
END//
DELIMITER ;

-- Insert sample ratings for testing
INSERT IGNORE INTO doctor_ratings (doctor_id, user_id, rating, review) VALUES
-- Doctor ID 220 ratings
(220, 1, 5, 'دكتور ممتاز جداً، متعاون ويفصل بوضوح'),
(220, 2, 4, 'جيد جداً، استشارته كانت مفيدة'),
(220, 3, 5, 'أفضل دكتور تعاملت معه'),
-- Doctor ID 221 ratings
(221, 1, 4, 'دكتور جيد ومتعاون'),
(221, 4, 3, 'مقبول، لكن يمكن تحسين خدمة الانتظار'),
-- Doctor ID 222 ratings
(222, 2, 5, 'محترف جداً ومتخصص في مجاله'),
(222, 5, 4, 'استشارته كانت ممتازة');

-- Update existing doctors' calculated ratings
UPDATE doctors d SET 
    d.calculated_rating = (
        SELECT COALESCE(AVG(rating), 0) 
        FROM doctor_ratings 
        WHERE doctor_id = d.user_id
    ),
    d.total_ratings = (
        SELECT COUNT(*) 
        FROM doctor_ratings 
        WHERE doctor_id = d.user_id
    );
