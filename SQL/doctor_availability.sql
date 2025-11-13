-- Doctor Availability Table
-- This table stores the working schedule for each doctor

CREATE TABLE IF NOT EXISTS doctor_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    day_of_week VARCHAR(20) NOT NULL COMMENT 'saturday, sunday, monday, tuesday, wednesday, thursday, friday',
    start_time TIME NOT NULL COMMENT 'Start of working hours',
    end_time TIME NOT NULL COMMENT 'End of working hours',
    slot_duration INT NOT NULL DEFAULT 30 COMMENT 'Duration of each appointment slot in minutes',
    is_active TINYINT(1) DEFAULT 1 COMMENT 'Whether this schedule is active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_doctor_day (doctor_id, day_of_week),
    
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_day_of_week (day_of_week),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample availability for existing doctors
-- This will only work if doctors exist in the users table

INSERT IGNORE INTO doctor_availability (doctor_id, day_of_week, start_time, end_time, slot_duration) 
SELECT u.id, 'saturday', '09:00:00', '17:00:00', 30 
FROM users u WHERE u.role = 'doctor' LIMIT 5;

INSERT IGNORE INTO doctor_availability (doctor_id, day_of_week, start_time, end_time, slot_duration) 
SELECT u.id, 'sunday', '09:00:00', '17:00:00', 30 
FROM users u WHERE u.role = 'doctor' LIMIT 5;

INSERT IGNORE INTO doctor_availability (doctor_id, day_of_week, start_time, end_time, slot_duration) 
SELECT u.id, 'monday', '09:00:00', '17:00:00', 30 
FROM users u WHERE u.role = 'doctor' LIMIT 5;

INSERT IGNORE INTO doctor_availability (doctor_id, day_of_week, start_time, end_time, slot_duration) 
SELECT u.id, 'tuesday', '09:00:00', '17:00:00', 30 
FROM users u WHERE u.role = 'doctor' LIMIT 5;

INSERT IGNORE INTO doctor_availability (doctor_id, day_of_week, start_time, end_time, slot_duration) 
SELECT u.id, 'wednesday', '09:00:00', '17:00:00', 30 
FROM users u WHERE u.role = 'doctor' LIMIT 5;
