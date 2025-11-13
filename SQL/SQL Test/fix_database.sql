-- إصلاح شامل لقاعدة البيانات
-- Comprehensive Database Fix

-- إنشاء قاعدة البيانات إذا لم تكن موجودة
CREATE DATABASE IF NOT EXISTS medical_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE medical_booking;

-- حذف الجداول الموجودة إذا كانت موجودة (لإعادة إنشائها)
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS doctor_schedules;
DROP TABLE IF EXISTS doctors;
DROP TABLE IF EXISTS clinics;
DROP TABLE IF EXISTS hospitals;
DROP TABLE IF EXISTS specialties;
DROP TABLE IF EXISTS users;

-- جدول المستخدمين (المرضى)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    date_of_birth DATE,
    gender ENUM('male', 'female') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- جدول التخصصات الطبية
CREATE TABLE specialties (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(100)
);

-- جدول المستشفيات (مع الأعمدة الجديدة)
CREATE TABLE hospitals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    address TEXT NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    website VARCHAR(200),
    description TEXT,
    image VARCHAR(200),
    type ENUM('حكومي', 'خاص') DEFAULT 'حكومي',
    rating DECIMAL(3,2) DEFAULT 0.00,
    is_24h BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- جدول العيادات (مع الأعمدة الجديدة)
CREATE TABLE clinics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hospital_id INT,
    name VARCHAR(200) NOT NULL,
    specialty_id INT,
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(100),
    description TEXT,
    image VARCHAR(200),
    consultation_fee DECIMAL(10,2) DEFAULT 0.00,
    rating DECIMAL(3,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id) ON DELETE CASCADE,
    FOREIGN KEY (specialty_id) REFERENCES specialties(id) ON DELETE SET NULL
);

-- جدول الأطباء
CREATE TABLE doctors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    specialty_id INT,
    clinic_id INT,
    phone VARCHAR(20),
    email VARCHAR(100),
    experience_years INT,
    education TEXT,
    image VARCHAR(200),
    rating DECIMAL(3,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (specialty_id) REFERENCES specialties(id) ON DELETE SET NULL,
    FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE
);

-- جدول المواعيد
CREATE TABLE appointments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    doctor_id INT NOT NULL,
    clinic_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE
);

-- جدول أوقات عمل الأطباء
CREATE TABLE doctor_schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    doctor_id INT NOT NULL,
    day_of_week ENUM('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

-- جدول تقييمات المواعيد
CREATE TABLE appointment_reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    appointment_id INT NOT NULL,
    user_id INT NOT NULL,
    doctor_id INT NOT NULL,
    rating DECIMAL(2,1) NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    UNIQUE KEY unique_review (appointment_id, user_id)
);

-- جدول الإشعارات
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('appointment_confirmed', 'appointment_cancelled', 'appointment_reminder', 'system_message') NOT NULL,
    message TEXT NOT NULL,
    related_id INT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- جدول الملفات الطبية للمرضى
CREATE TABLE patient_medical_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_id INT,
    diagnosis TEXT,
    prescription TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
);

-- جدول إعدادات النظام
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- إدراج التخصصات الطبية
INSERT INTO specialties (name, description) VALUES
('طب القلب والأوعية الدموية', 'تخصص في علاج أمراض القلب والشرايين'),
('طب العيون', 'تخصص في علاج أمراض العيون والرؤية'),
('طب الأسنان', 'تخصص في علاج أمراض الأسنان واللثة'),
('طب الأطفال', 'تخصص في علاج أمراض الأطفال'),
('طب النساء والولادة', 'تخصص في أمراض النساء والولادة'),
('طب الجراحة العامة', 'تخصص في العمليات الجراحية العامة'),
('طب الأعصاب', 'تخصص في أمراض الجهاز العصبي'),
('طب الجلدية', 'تخصص في أمراض الجلد'),
('طب العظام', 'تخصص في أمراض العظام والمفاصل'),
('طب الأنف والأذن والحنجرة', 'تخصص في أمراض الأنف والأذن والحنجرة'),
('طب الباطنة', 'تخصص في الأمراض الباطنية'),
('طب النفسية', 'تخصص في الأمراض النفسية'),
('طب الأورام', 'تخصص في علاج الأورام والسرطان'),
('طب الطوارئ', 'تخصص في حالات الطوارئ'),
('طب التخدير', 'تخصص في التخدير والإنعاش'),
('طب الأشعة', 'تخصص في التصوير الطبي'),
('طب المختبرات', 'تخصص في التحاليل الطبية'),
('طب التغذية', 'تخصص في التغذية العلاجية'),
('طب التأهيل', 'تخصص في التأهيل الطبي'),
('طب المسنين', 'تخصص في طب المسنين');

-- إدراج المستشفيات
INSERT INTO hospitals (name, address, phone, email, description, type, rating, is_24h) VALUES
('مستشفى القاهرة العام', 'شارع القصر العيني، القاهرة', '02-23678901', 'info@cairo-hospital.com', 'مستشفى حكومي شامل يقدم خدمات طبية متكاملة', 'حكومي', 4.2, 1),
('مستشفى دار الشفاء التخصصي', 'شارع النيل، الجيزة', '02-34567890', 'info@daralshafa.com', 'مستشفى خاص متخصص في الجراحات المتقدمة', 'خاص', 4.8, 1),
('مستشفى السلام الدولي', 'شارع المعادي، القاهرة', '02-45678901', 'info@alsalam-hospital.com', 'مستشفى دولي يقدم خدمات طبية عالمية المستوى', 'خاص', 4.9, 1),
('مستشفى الأزهر الجامعي', 'شارع الأزهر، القاهرة', '02-56789012', 'info@azhar-hospital.com', 'مستشفى تعليمي تابع لجامعة الأزهر', 'حكومي', 4.1, 0),
('مستشفى النيل التخصصي', 'شارع كورنيش النيل، القاهرة', '02-67890123', 'info@nile-hospital.com', 'مستشفى متخصص في أمراض القلب والأوعية الدموية', 'خاص', 4.7, 1),
('مستشفى مصر للعيون', 'شارع التحرير، القاهرة', '02-78901234', 'info@egypt-eyes.com', 'مستشفى متخصص في طب العيون والجراحات المتقدمة', 'خاص', 4.6, 0),
('مستشفى الأطفال التخصصي', 'شارع عباس العقاد، القاهرة', '02-89012345', 'info@children-hospital.com', 'مستشفى متخصص في طب الأطفال وحديثي الولادة', 'حكومي', 4.4, 1),
('مستشفى النساء والولادة', 'شارع رمسيس، القاهرة', '02-90123456', 'info@women-hospital.com', 'مستشفى متخصص في طب النساء والولادة', 'حكومي', 4.3, 1),
('مستشفى الجراحة المتقدمة', 'شارع مصر الجديدة، القاهرة', '02-01234567', 'info@surgery-hospital.com', 'مستشفى متخصص في الجراحات المتقدمة والمناظير', 'خاص', 4.8, 1),
('مستشفى التأمين الصحي', 'شارع الهرم، الجيزة', '02-12345678', 'info@insurance-hospital.com', 'مستشفى تابع للتأمين الصحي', 'حكومي', 4.0, 0),
('مستشفى الأسنان التخصصي', 'شارع جامعة القاهرة، الجيزة', '02-23456789', 'info@dental-hospital.com', 'مستشفى متخصص في طب الأسنان والجراحات', 'خاص', 4.5, 0),
('مستشفى الطب النفسي', 'شارع الهرم، الجيزة', '02-34567890', 'info@psychiatry-hospital.com', 'مستشفى متخصص في الطب النفسي والعلاج السلوكي', 'حكومي', 4.2, 1),
('مستشفى الأورام المتخصص', 'شارع المعادي، القاهرة', '02-45678901', 'info@oncology-hospital.com', 'مستشفى متخصص في علاج الأورام والسرطان', 'حكومي', 4.6, 1),
('مستشفى التأهيل الطبي', 'شارع النزهة، القاهرة', '02-56789012', 'info@rehab-hospital.com', 'مستشفى متخصص في التأهيل الطبي والعلاج الطبيعي', 'خاص', 4.4, 0),
('مستشفى الطوارئ العام', 'شارع رمسيس، القاهرة', '02-67890123', 'info@emergency-hospital.com', 'مستشفى متخصص في حالات الطوارئ والحوادث', 'حكومي', 4.3, 1);

-- إدراج العيادات
INSERT INTO clinics (name, hospital_id, specialty_id, description, phone, email, consultation_fee) VALUES
-- عيادات مستشفى القاهرة العام
('عيادة القلب', 1, 1, 'عيادة متخصصة في أمراض القلب والأوعية الدموية', '02-23678902', 'cardio@cairo-hospital.com', 150),
('عيادة العيون', 1, 2, 'عيادة متخصصة في أمراض العيون والرؤية', '02-23678903', 'eye@cairo-hospital.com', 120),
('عيادة الأطفال', 1, 4, 'عيادة متخصصة في طب الأطفال', '02-23678904', 'pediatrics@cairo-hospital.com', 100),

-- عيادات مستشفى دار الشفاء التخصصي
('عيادة الجراحة العامة', 2, 6, 'عيادة متخصصة في الجراحات العامة', '02-34567891', 'surgery@daralshafa.com', 200),
('عيادة الأعصاب', 2, 7, 'عيادة متخصصة في أمراض الجهاز العصبي', '02-34567892', 'neurology@daralshafa.com', 180),
('عيادة الجلدية', 2, 8, 'عيادة متخصصة في أمراض الجلد', '02-34567893', 'dermatology@daralshafa.com', 150),

-- عيادات مستشفى السلام الدولي
('عيادة القلب المتقدمة', 3, 1, 'عيادة متخصصة في جراحات القلب المتقدمة', '02-45678902', 'cardio@alsalam-hospital.com', 300),
('عيادة العظام', 3, 9, 'عيادة متخصصة في أمراض العظام والمفاصل', '02-45678903', 'ortho@alsalam-hospital.com', 250),
('عيادة الأنف والأذن', 3, 10, 'عيادة متخصصة في أمراض الأنف والأذن والحنجرة', '02-45678904', 'ent@alsalam-hospital.com', 200),

-- عيادات مستشفى الأزهر الجامعي
('عيادة الباطنة', 4, 11, 'عيادة متخصصة في الأمراض الباطنية', '02-56789013', 'internal@azhar-hospital.com', 120),
('عيادة النفسية', 4, 12, 'عيادة متخصصة في الأمراض النفسية', '02-56789014', 'psychiatry@azhar-hospital.com', 150),
('عيادة التغذية', 4, 18, 'عيادة متخصصة في التغذية العلاجية', '02-56789015', 'nutrition@azhar-hospital.com', 100),

-- عيادات مستشفى النيل التخصصي
('عيادة القلب التخصصية', 5, 1, 'عيادة متخصصة في أمراض القلب والأوعية الدموية', '02-67890124', 'cardio@nile-hospital.com', 280),
('عيادة الأوعية الدموية', 5, 1, 'عيادة متخصصة في أمراض الأوعية الدموية', '02-67890125', 'vascular@nile-hospital.com', 250),

-- عيادات مستشفى مصر للعيون
('عيادة العيون العامة', 6, 2, 'عيادة متخصصة في أمراض العيون', '02-78901235', 'eye@egypt-eyes.com', 200),
('عيادة الشبكية', 6, 2, 'عيادة متخصصة في أمراض الشبكية', '02-78901236', 'retina@egypt-eyes.com', 300),
('عيادة الجلوكوما', 6, 2, 'عيادة متخصصة في علاج الجلوكوما', '02-78901237', 'glaucoma@egypt-eyes.com', 250),

-- عيادات مستشفى الأطفال التخصصي
('عيادة حديثي الولادة', 7, 4, 'عيادة متخصصة في رعاية حديثي الولادة', '02-89012346', 'neonatal@children-hospital.com', 150),
('عيادة الأطفال العامة', 7, 4, 'عيادة متخصصة في طب الأطفال العام', '02-89012347', 'pediatrics@children-hospital.com', 120),
('عيادة أمراض الأطفال', 7, 4, 'عيادة متخصصة في أمراض الأطفال', '02-89012348', 'diseases@children-hospital.com', 130),

-- عيادات مستشفى النساء والولادة
('عيادة النساء العامة', 8, 5, 'عيادة متخصصة في أمراض النساء', '02-90123457', 'gynecology@women-hospital.com', 150),
('عيادة الولادة', 8, 5, 'عيادة متخصصة في الولادة ورعاية الحوامل', '02-90123458', 'obstetrics@women-hospital.com', 180),
('عيادة العقم', 8, 5, 'عيادة متخصصة في علاج العقم', '02-90123459', 'fertility@women-hospital.com', 250),

-- عيادات مستشفى الجراحة المتقدمة
('عيادة الجراحة العامة', 9, 6, 'عيادة متخصصة في الجراحات العامة', '02-01234568', 'surgery@surgery-hospital.com', 200),
('عيادة الجراحة بالمنظار', 9, 6, 'عيادة متخصصة في الجراحات بالمنظار', '02-01234569', 'laparoscopic@surgery-hospital.com', 300),
('عيادة جراحة الأورام', 9, 13, 'عيادة متخصصة في جراحة الأورام', '02-01234570', 'oncology@surgery-hospital.com', 350),

-- عيادات مستشفى التأمين الصحي
('عيادة الباطنة العامة', 10, 11, 'عيادة متخصصة في الأمراض الباطنية', '02-12345679', 'internal@insurance-hospital.com', 80),
('عيادة العظام', 10, 9, 'عيادة متخصصة في أمراض العظام', '02-12345680', 'ortho@insurance-hospital.com', 100),

-- عيادات مستشفى الأسنان التخصصي
('عيادة الأسنان العامة', 11, 3, 'عيادة متخصصة في طب الأسنان العام', '02-23456790', 'dental@dental-hospital.com', 150),
('عيادة تقويم الأسنان', 11, 3, 'عيادة متخصصة في تقويم الأسنان', '02-23456791', 'orthodontics@dental-hospital.com', 200),
('عيادة جراحة الفم', 11, 3, 'عيادة متخصصة في جراحة الفم والأسنان', '02-23456792', 'oral@dental-hospital.com', 250),

-- عيادات مستشفى الطب النفسي
('عيادة الطب النفسي العام', 12, 12, 'عيادة متخصصة في الطب النفسي العام', '02-34567891', 'psychiatry@psychiatry-hospital.com', 200),
('عيادة العلاج السلوكي', 12, 12, 'عيادة متخصصة في العلاج السلوكي', '02-34567892', 'behavioral@psychiatry-hospital.com', 250),

-- عيادات مستشفى الأورام المتخصص
('عيادة الأورام العامة', 13, 13, 'عيادة متخصصة في علاج الأورام', '02-45678902', 'oncology@oncology-hospital.com', 300),
('عيادة العلاج الكيميائي', 13, 13, 'عيادة متخصصة في العلاج الكيميائي', '02-45678903', 'chemotherapy@oncology-hospital.com', 400),

-- عيادات مستشفى التأهيل الطبي
('عيادة التأهيل العام', 14, 19, 'عيادة متخصصة في التأهيل الطبي', '02-56789013', 'rehab@rehab-hospital.com', 180),
('عيادة العلاج الطبيعي', 14, 19, 'عيادة متخصصة في العلاج الطبيعي', '02-56789014', 'physio@rehab-hospital.com', 150),

-- عيادات مستشفى الطوارئ العام
('عيادة الطوارئ العامة', 15, 14, 'عيادة متخصصة في حالات الطوارئ', '02-67890124', 'emergency@emergency-hospital.com', 100),
('عيادة الحوادث', 15, 14, 'عيادة متخصصة في علاج الحوادث', '02-67890125', 'accidents@emergency-hospital.com', 120);

-- إدراج الأطباء
INSERT INTO doctors (full_name, specialty_id, clinic_id, phone, email, experience_years, education, rating) VALUES
-- أطباء عيادة القلب - مستشفى القاهرة العام
('د. أحمد محمد علي', 1, 1, '010-12345678', 'ahmed.ali@cairo-hospital.com', 15, 'دكتوراه في أمراض القلب - جامعة القاهرة', 4.8),
('د. فاطمة أحمد حسن', 1, 1, '010-12345679', 'fatima.hassan@cairo-hospital.com', 12, 'ماجستير في أمراض القلب - جامعة عين شمس', 4.6),

-- أطباء عيادة العيون - مستشفى القاهرة العام
('د. محمد سعيد عبدالله', 2, 2, '010-12345680', 'mohamed.abdullah@cairo-hospital.com', 18, 'دكتوراه في طب العيون - جامعة الأزهر', 4.7),
('د. سارة محمود أحمد', 2, 2, '010-12345681', 'sara.ahmed@cairo-hospital.com', 10, 'ماجستير في طب العيون - جامعة القاهرة', 4.5),

-- أطباء عيادة الأطفال - مستشفى القاهرة العام
('د. علي حسن محمد', 4, 3, '010-12345682', 'ali.mohamed@cairo-hospital.com', 20, 'دكتوراه في طب الأطفال - جامعة القاهرة', 4.9),
('د. نورا أحمد علي', 4, 3, '010-12345683', 'nora.ali@cairo-hospital.com', 8, 'ماجستير في طب الأطفال - جامعة عين شمس', 4.4),

-- أطباء عيادة الجراحة العامة - مستشفى دار الشفاء
('د. خالد محمد أحمد', 6, 4, '010-12345684', 'khaled.ahmed@daralshafa.com', 22, 'دكتوراه في الجراحة العامة - جامعة القاهرة', 4.8),
('د. ليلى سعيد محمد', 6, 4, '010-12345685', 'laila.mohamed@daralshafa.com', 14, 'ماجستير في الجراحة العامة - جامعة الأزهر', 4.6),

-- أطباء عيادة الأعصاب - مستشفى دار الشفاء
('د. عمر أحمد حسن', 7, 5, '010-12345686', 'omar.hassan@daralshafa.com', 16, 'دكتوراه في طب الأعصاب - جامعة عين شمس', 4.7),
('د. رنا محمد علي', 7, 5, '010-12345687', 'rana.ali@daralshafa.com', 11, 'ماجستير في طب الأعصاب - جامعة القاهرة', 4.5),

-- أطباء عيادة الجلدية - مستشفى دار الشفاء
('د. يوسف أحمد محمد', 8, 6, '010-12345688', 'youssef.mohamed@daralshafa.com', 13, 'دكتوراه في طب الجلدية - جامعة الأزهر', 4.6),
('د. مريم سعيد أحمد', 8, 6, '010-12345689', 'maryam.ahmed@daralshafa.com', 9, 'ماجستير في طب الجلدية - جامعة عين شمس', 4.4),

-- أطباء عيادة القلب المتقدمة - مستشفى السلام الدولي
('د. أحمد حسن علي', 1, 7, '010-12345690', 'ahmed.hassan@alsalam-hospital.com', 25, 'دكتوراه في جراحات القلب - جامعة القاهرة', 4.9),
('د. سلمى محمد أحمد', 1, 7, '010-12345691', 'salma.ahmed@alsalam-hospital.com', 17, 'ماجستير في جراحات القلب - جامعة عين شمس', 4.7),

-- أطباء عيادة العظام - مستشفى السلام الدولي
('د. محمد علي حسن', 9, 8, '010-12345692', 'mohamed.ali@alsalam-hospital.com', 19, 'دكتوراه في طب العظام - جامعة الأزهر', 4.8),
('د. عائشة أحمد محمد', 9, 8, '010-12345693', 'aisha.mohamed@alsalam-hospital.com', 12, 'ماجستير في طب العظام - جامعة القاهرة', 4.6),

-- أطباء عيادة الأنف والأذن - مستشفى السلام الدولي
('د. حسن محمد علي', 10, 9, '010-12345694', 'hassan.mohamed@alsalam-hospital.com', 15, 'دكتوراه في طب الأنف والأذن - جامعة عين شمس', 4.7),
('د. زينب أحمد حسن', 10, 9, '010-12345695', 'zeinab.hassan@alsalam-hospital.com', 10, 'ماجستير في طب الأنف والأذن - جامعة الأزهر', 4.5),

-- أطباء عيادة الباطنة - مستشفى الأزهر الجامعي
('د. علي أحمد محمد', 11, 10, '010-12345696', 'ali.ahmed@azhar-hospital.com', 21, 'دكتوراه في الأمراض الباطنية - جامعة الأزهر', 4.8),
('د. فاطمة محمد علي', 11, 10, '010-12345697', 'fatima.mohamed@azhar-hospital.com', 13, 'ماجستير في الأمراض الباطنية - جامعة القاهرة', 4.6),

-- أطباء عيادة النفسية - مستشفى الأزهر الجامعي
('د. أحمد محمد حسن', 12, 11, '010-12345698', 'ahmed.mohamed@azhar-hospital.com', 18, 'دكتوراه في الطب النفسي - جامعة عين شمس', 4.7),
('د. نورا علي أحمد', 12, 11, '010-12345699', 'nora.ali@azhar-hospital.com', 11, 'ماجستير في الطب النفسي - جامعة الأزهر', 4.5),

-- أطباء عيادة التغذية - مستشفى الأزهر الجامعي
('د. محمد حسن علي', 18, 12, '010-12345700', 'mohamed.hassan@azhar-hospital.com', 14, 'دكتوراه في التغذية العلاجية - جامعة القاهرة', 4.6),
('د. سارة أحمد محمد', 18, 12, '010-12345701', 'sara.ahmed@azhar-hospital.com', 8, 'ماجستير في التغذية العلاجية - جامعة عين شمس', 4.4);

-- إدراج أوقات عمل الأطباء
INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time) VALUES
-- د. أحمد محمد علي - عيادة القلب
(1, 'sunday', '09:00:00', '17:00:00'),
(1, 'monday', '09:00:00', '17:00:00'),
(1, 'tuesday', '09:00:00', '17:00:00'),
(1, 'wednesday', '09:00:00', '17:00:00'),
(1, 'thursday', '09:00:00', '17:00:00'),

-- د. فاطمة أحمد حسن - عيادة القلب
(2, 'sunday', '10:00:00', '18:00:00'),
(2, 'monday', '10:00:00', '18:00:00'),
(2, 'tuesday', '10:00:00', '18:00:00'),
(2, 'wednesday', '10:00:00', '18:00:00'),
(2, 'thursday', '10:00:00', '18:00:00'),

-- د. محمد سعيد عبدالله - عيادة العيون
(3, 'sunday', '08:00:00', '16:00:00'),
(3, 'monday', '08:00:00', '16:00:00'),
(3, 'tuesday', '08:00:00', '16:00:00'),
(3, 'wednesday', '08:00:00', '16:00:00'),
(3, 'thursday', '08:00:00', '16:00:00'),

-- د. سارة محمود أحمد - عيادة العيون
(4, 'sunday', '11:00:00', '19:00:00'),
(4, 'monday', '11:00:00', '19:00:00'),
(4, 'tuesday', '11:00:00', '19:00:00'),
(4, 'wednesday', '11:00:00', '19:00:00'),
(4, 'thursday', '11:00:00', '19:00:00'),

-- د. علي حسن محمد - عيادة الأطفال
(5, 'sunday', '09:00:00', '17:00:00'),
(5, 'monday', '09:00:00', '17:00:00'),
(5, 'tuesday', '09:00:00', '17:00:00'),
(5, 'wednesday', '09:00:00', '17:00:00'),
(5, 'thursday', '09:00:00', '17:00:00'),

-- د. نورا أحمد علي - عيادة الأطفال
(6, 'sunday', '10:00:00', '18:00:00'),
(6, 'monday', '10:00:00', '18:00:00'),
(6, 'tuesday', '10:00:00', '18:00:00'),
(6, 'wednesday', '10:00:00', '18:00:00'),
(6, 'thursday', '10:00:00', '18:00:00');

-- إدراج تقييمات المواعيد
INSERT INTO appointment_reviews (appointment_id, user_id, doctor_id, rating, comment) VALUES
(1, 1, 1, 5, 'ممتاز، خدمة مميزة'),
(2, 2, 1, 4, 'جيد، لكن يمكن تحسين التوقيت'),
(3, 3, 2, 5, 'ممتاز، الطبيب مهندس علمي'),
(4, 4, 2, 3, 'جيد، لكن كان هناك تأخير في الوصول'),
(5, 5, 3, 5, 'ممتاز، الطبيب متميز'),
(6, 6, 3, 4, 'جيد، لكن كان هناك تأخير في التوقيت'),
(7, 7, 4, 5, 'ممتاز، الطبيب متميز ومهندس علمي'),
(8, 8, 4, 3, 'جيد، لكن كان هناك تأخير في التوقيت'),
(9, 9, 5, 5, 'ممتاز، الطبيب متميز ومهندس علمي'),
(10, 10, 5, 4, 'جيد، لكن كان هناك تأخير في التوقيت'),
(11, 11, 6, 5, 'ممتاز، الطبيب متميز ومهندس علمي'),
(12, 12, 6, 3, 'جيد، لكن كان هناك تأخير في التوقيت'),
(13, 13, 7, 5, 'ممتاز، الطبيب متميز ومهندس علمي'),
(14, 14, 7, 3, 'جيد، لكن كان هناك تأخير في التوقيت'),
(15, 15, 8, 5, 'ممتاز، الطبيب متميز ومهندس علمي'),
(16, 16, 8, 3, 'جيد، لكن كان هناك تأخير في التوقيت'),
(17, 17, 9, 5, 'ممتاز، الطبيب متميز ومهندس علمي'),
(18, 18, 9, 3, 'جيد، لكن كان هناك تأخير في التوقيت'),
(19, 19, 10, 5, 'ممتاز، الطبيب متميز ومهندس علمي'),
(20, 20, 10, 3, 'جيد، لكن كان هناك تأخير في التوقيت'),
(21, 21, 11, 5, 'ممتاز، الطبيب متميز ومهندس علمي'),
(22, 22, 11, 3, 'جيد، لكن كان هناك تأخير في التوقيت'),
(23, 23, 12, 5, 'ممتاز، الطبيب متميز ومهندس علمي'),
(24, 24, 12, 3, 'جيد، لكن كان هناك تأخير في التوقيت'),
(25, 25, 13, 5, 'ممتاز، الطبيب متميز ومهندس علمي'),
(26, 26, 13, 3, 'جيد، لكن كان هناك تأخير في التوقيت'),
(27, 27, 14, 5, 'ممتاز، الطبيب متميز ومهندس علمي'),
(28, 28, 14, 3, 'جيد، لكن كان هناك تأخير في التوقيت'),
(29, 29, 15, 5, 'ممتاز، الطبيب متميز ومهندس علمي'),
(30, 30, 15, 3, 'جيد، لكن كان هناك تأخير في التوقيت');

-- إدراج الإشعارات
INSERT INTO notifications (user_id, type, message) VALUES
(1, 'appointment_confirmed', 'تم تأكيد موعدك الأحد 10:00 مساءً مع الطبيب'),
(2, 'appointment_cancelled', 'تم إلغاء موعدك الأحد 10:00 مساءً مع الطبيب'),
(3, 'appointment_reminder', 'تذكير: لديك موعد الأحد 10:00 مساءً مع الطبيب'),
(4, 'system_message', 'نظام التطبيق في حالة صيانة حالياً'),
(5, 'appointment_confirmed', 'تم تأكيد موعدك الأحد 09:00 صباحاً مع الطبيب'),
(6, 'appointment_cancelled', 'تم إلغاء موعدك الأحد 09:00 صباحاً مع الطبيب'),
(7, 'appointment_reminder', 'تذكير: لديك موعد الأحد 09:00 صباحاً مع الطبيب'),
(8, 'system_message', 'تم تفعيل نظام الإشعارات'),
(9, 'appointment_confirmed', 'تم تأكيد موعدك الأحد 11:00 صباحاً مع الطبيب'),
(10, 'appointment_cancelled', 'تم إلغاء موعدك الأحد 11:00 صباحاً مع الطبيب'),
(11, 'appointment_reminder', 'تذكير: لديك موعد الأحد 11:00 صباحاً مع الطبيب'),
(12, 'system_message', 'تم تعطيل نظام الإشعارات'),
(13, 'appointment_confirmed', 'تم تأكيد موعدك الأحد 10:00 مساءً مع الطبيب'),
(14, 'appointment_cancelled', 'تم إلغاء موعدك الأحد 10:00 مساءً مع الطبيب'),
(15, 'appointment_reminder', 'تذكير: لديك موعد الأحد 10:00 مساءً مع الطبيب'),
(16, 'system_message', 'تم تفعيل نظام الإشعارات'),
(17, 'appointment_confirmed', 'تم تأكيد موعدك الأحد 09:00 صباحاً مع الطبيب'),
(18, 'appointment_cancelled', 'تم إلغاء موعدك الأحد 09:00 صباحاً مع الطبيب'),
(19, 'appointment_reminder', 'تذكير: لديك موعد الأحد 09:00 صباحاً مع الطبيب'),
(20, 'system_message', 'تم تعطيل نظام الإشعارات'),
(21, 'appointment_confirmed', 'تم تأكيد موعدك الأحد 11:00 صباحاً مع الطبيب'),
(22, 'appointment_cancelled', 'تم إلغاء موعدك الأحد 11:00 صباحاً مع الطبيب'),
(23, 'appointment_reminder', 'تذكير: لديك موعد الأحد 11:00 صباحاً مع الطبيب'),
(24, 'system_message', 'تم تفعيل نظام الإشعارات'),
(25, 'appointment_confirmed', 'تم تأكيد موعدك الأحد 10:00 مساءً مع الطبيب'),
(26, 'appointment_cancelled', 'تم إلغاء موعدك الأحد 10:00 مساءً مع الطبيب'),
(27, 'appointment_reminder', 'تذكير: لديك موعد الأحد 10:00 مساءً مع الطبيب'),
(28, 'system_message', 'تم تعطيل نظام الإشعارات'),
(29, 'appointment_confirmed', 'تم تأكيد موعدك الأحد 11:00 صباحاً مع الطبيب'),
(30, 'appointment_cancelled', 'تم إلغاء موعدك الأحد 11:00 صباحاً مع الطبيب');

-- إدراج الملفات الطبية للمرضى
INSERT INTO patient_medical_records (user_id, doctor_id, appointment_id, diagnosis, prescription, notes) VALUES
(1, 1, 1, 'ألم في الصدر', 'أدوية للتخفيف', 'تم إرسال أوامر الأدوية'),
(2, 2, 2, 'ألم في الركبة', 'أدوية للتخفيف والعلاج الطبيعي', 'تم إرسال أوامر الأدوية'),
(3, 3, 3, 'ألم في الظهر', 'أدوية للتخفيف والعلاج الطبيعي', 'تم إرسال أوامر الأدوية'),
(4, 4, 4, 'ألم في الصدر', 'أدوية للتخفيف والعلاج الطبيعي', 'تم إرسال أوامر الأدوية'),
(5, 5, 5, 'ألم في الركبة', 'أدوية للتخفيف والعلاج الطبيعي', 'تم إرسال أوامر الأدوية'),
(6, 6, 6, 'ألم في الظهر', 'أدوية للتخفيف والعلاج الطبيعي', 'تم إرسال أوامر الأدوية'),
(7, 7, 7, 'ألم في الصدر', 'أدوية للتخفيف والعلاج الطبيعي', 'تم إرسال أوامر الأدوية'),
(8, 8, 8, 'ألم في الركبة', 'أدوية للتخفيف والعلاج الطبيعي', 'تم إرسال أوامر الأدوية'),
(9, 9, 9, 'ألم في الظهر', 'أدوية للتخفيف والعلاج الطبيعي', 'تم إرسال أوامر الأدوية'),
(10, 10, 10, 'ألم في الصدر', 'أدوية للتخفيف والعلاج الطبيعي', 'تم إرسال أوامر الأدوية'),
(11, 11, 11, 'ألم في الركبة', 'أدوية للتخفيف والعلاج الطبيعي', 'تم إرسال أوامر الأدوية'),
(12, 12, 12, 'ألم في الظهر', 'أدوية للتخفيف والعلاج الطبيعي', 'تم إرسال أوامر الأدوية'),
(13, 13, 13, 'ألم في الصدر', 'أدوية للتخفيف والعلاج الطبيعي', 'تم إرسال أوامر الأدوية'),
(14, 14, 14, 'ألم في الركبة', 'أدوية للتخفيف والعلاج الطبيعي', 'تم إرسال أوامر الأدوية'),
(15, 15, 15, 'ألم في الظهر', 'أدوية للتخفيف والعلاج الطبيعي', 'تم إرسال أوامر الأدوية'),
(16, 16, 16, 'ألم في الصدر', 'أدوية للتخفيف والعلاج الطبيعي', 'تم إرسال أوامر الأدوية'),
(17, 17, 17, 'ألم في الركبة', 'أدوية للتخفيف والعلاج الطبيعي', 'تم إرسال أوامر الأدوية'),
(18, 18, 18, 'ألم في الظهر', 'أدوية للتخفيف والعلاج الطبيعي', 'تم إرسال أوامر الأدوية'),
(19, 19, 19, 'ألم في الصدر', 'أدوية للتخفيف والعلاج الطبيعي', 'تم إرسال أوامر الأدوية'),
(20, 20, 20, 'ألم في الركبة', 'أدوية للتخفيف والعلاج الطبيعي', 'تم إرسال أوامر الأدوية'),
(21, 21, 21, 'ألم في الظهر', 'أدوية للتخفيف والعلاج الطبيعي', 'تم إرسال أوامر الأدوية'),
(22, 22, 22, 'ألم في الصدر', 'أدوية للتخفيف والعلاج الطبيعي', 'تم إرسال أوامر الأدوية'),
(23, 23, 23, 'ألم في الركبة', 'أدوية للتخفيف والعلاج الطبيعي', 'تم إرسال أوامر الأدوية'),
(24, 24, 24, 'ألم في الظهر', 'أدوية للتخفيف والعلاج الطبيعي', 'تم إرسال أوامر الأدوية'),
(25, 25, 25, 'ألم في الصدر', 'أدوية للتخفيف والعلاج الطبيعي', 'تم إرسال أوامر الأدوية'),
(26, 26, 26, 'ألم في الركبة', 'أدوية للتخفيف والعلاج الطبيعي', 'تم إرسال أوامر الأدوية'),
(27, 27, 27, 'ألم في الظهر', 'أدوية للتخفيف والعلاج الطبيعي', 'تم إرسال أوامر الأدوية'),
(28, 28, 28, 'ألم في الصدر', 'أدوية للتخفيف والعلاج الطبيعي', 'تم إرسال أوامر الأدوية'),
(29, 29, 29, 'ألم في الركبة', 'أدوية للتخفيف والعلاج الطبيعي', 'تم إرسال أوامر الأدوية'),
(30, 30, 30, 'ألم في الظهر', 'أدوية للتخفيف والعلاج الطبيعي', 'تم إرسال أوامر الأدوية');

-- إدراج إعدادات النظام الافتراضية
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('appointment_reminder_hours', '24', 'عدد الساعات قبل الموعد لإرسال تذكير'),
('max_appointments_per_day', '50', 'الحد الأقصى للمواعيد اليومية لكل طبيب'),
('appointment_duration_minutes', '30', 'مدة الموعد بالدقائق'),
('auto_confirm_appointments', 'true', 'تأكيد المواعيد تلقائياً'),
('enable_notifications', 'true', 'تفعيل نظام الإشعارات'),
('system_maintenance_mode', 'false', 'وضع الصيانة للنظام');

-- عرض إحصائيات قاعدة البيانات
SELECT 'إحصائيات قاعدة البيانات' as 'التقرير';
SELECT COUNT(*) as 'عدد التخصصات' FROM specialties;
SELECT COUNT(*) as 'عدد المستشفيات' FROM hospitals;
SELECT COUNT(*) as 'عدد العيادات' FROM clinics;
SELECT COUNT(*) as 'عدد الأطباء' FROM doctors;
SELECT COUNT(*) as 'عدد أوقات العمل' FROM doctor_schedules;
