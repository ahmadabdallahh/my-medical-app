-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 22, 2025 at 07:53 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `medical_booking_test`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `AddDoctorReview` (IN `p_doctor_info_id` INT, IN `p_patient_id` INT, IN `p_rating` DECIMAL(3,2), IN `p_review_text` TEXT, IN `p_treatment_date` DATE)   BEGIN
    INSERT INTO doctor_reviews (doctor_info_id, patient_id, rating, review_text, treatment_date, created_at)
    VALUES (p_doctor_info_id, p_patient_id, p_rating, p_review_text, p_treatment_date, NOW());

    -- Update average rating in doctor_info
    UPDATE doctor_info
    SET patient_satisfaction_rate = (
        SELECT AVG(rating)
        FROM doctor_reviews
        WHERE doctor_info_id = p_doctor_info_id
    )
    WHERE id = p_doctor_info_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `BookAppointment` (IN `p_user_id` INT, IN `p_doctor_id` INT, IN `p_clinic_id` INT, IN `p_appointment_date` DATE, IN `p_appointment_time` TIME, IN `p_notes` TEXT, OUT `p_appointment_id` INT)   BEGIN
    DECLARE appointment_datetime DATETIME;

    SET appointment_datetime = CONCAT(p_appointment_date, ' ', p_appointment_time);

    INSERT INTO appointments (user_id, doctor_id, clinic_id, appointment_date, appointment_time, appointment_datetime, notes)
    VALUES (p_user_id, p_doctor_id, p_clinic_id, p_appointment_date, p_appointment_time, appointment_datetime, p_notes);

    SET p_appointment_id = LAST_INSERT_ID();

    -- إنشاء تذكيرات تلقائية
    INSERT INTO reminder_logs (user_id, appointment_id, reminder_type, status)
    VALUES (p_user_id, p_appointment_id, 'email', 'sent');

    INSERT INTO push_notifications (user_id, title, message, type)
    VALUES (p_user_id, 'موعد جديد', CONCAT('تم حجز موعد جديد في ', p_appointment_date), 'appointment');
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `CreateDoctorInfo` (IN `p_doctor_id` INT, IN `p_license_number` VARCHAR(50), IN `p_national_id` VARCHAR(20), IN `p_date_of_birth` DATE, IN `p_gender` ENUM('male','female'), IN `p_nationality` VARCHAR(50), IN `p_address` TEXT, IN `p_emergency_contact` VARCHAR(100), IN `p_emergency_phone` VARCHAR(20), IN `p_languages_spoken` TEXT, IN `p_consultation_fee` DECIMAL(10,2), IN `p_years_of_experience` INT, IN `p_special_interests` TEXT)   BEGIN
    INSERT INTO doctor_info (
        doctor_id, license_number, national_id, date_of_birth, gender,
        nationality, address, emergency_contact, emergency_phone,
        languages_spoken, consultation_fee, years_of_experience,
        special_interests, created_at
    ) VALUES (
        p_doctor_id, p_license_number, p_national_id, p_date_of_birth, p_gender,
        p_nationality, p_address, p_emergency_contact, p_emergency_phone,
        p_languages_spoken, p_consultation_fee, p_years_of_experience,
        p_special_interests, NOW()
    );

    SELECT LAST_INSERT_ID() as doctor_info_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetAvailableTimes` (IN `p_doctor_id` INT, IN `p_date` DATE)   BEGIN
    DECLARE day_name VARCHAR(20);
    SET day_name = LOWER(DAYNAME(p_date));

    SELECT
        TIME_FORMAT(wh.start_time, '%H:%i') as available_time
    FROM working_hours wh
    WHERE wh.doctor_id = p_doctor_id
    AND wh.day_of_week = day_name
    AND wh.is_available = 1
    AND TIME_FORMAT(wh.start_time, '%H:%i') NOT IN (
        SELECT TIME_FORMAT(a.appointment_time, '%H:%i')
        FROM appointments a
        WHERE a.doctor_id = p_doctor_id
        AND a.appointment_date = p_date
        AND a.status IN ('pending', 'confirmed')
    )
    ORDER BY wh.start_time;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateDoctorSchedule` (IN `p_doctor_info_id` INT, IN `p_day_of_week` VARCHAR(10), IN `p_start_time` TIME, IN `p_end_time` TIME, IN `p_max_appointments` INT)   BEGIN
    INSERT INTO doctor_schedule (doctor_info_id, day_of_week, start_time, end_time, max_appointments)
    VALUES (p_doctor_info_id, p_day_of_week, p_start_time, p_end_time, p_max_appointments)
    ON DUPLICATE KEY UPDATE
    start_time = p_start_time,
    end_time = p_end_time,
    max_appointments = p_max_appointments;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `doctor_id` int(11) NOT NULL,
  `clinic_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `appointment_type` varchar(50) DEFAULT 'استشارة عامة',
  `appointment_datetime` datetime DEFAULT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `user_id`, `patient_id`, `doctor_id`, `clinic_id`, `appointment_date`, `appointment_time`, `appointment_type`, `appointment_datetime`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(70, 2, NULL, 36, 1, '2025-11-02', '12:45:00', 'استشارة عامة', NULL, 'completed', 'test_appointment_2_36', '2025-11-08 13:05:45', '2025-11-08 13:05:45'),
(71, 3, NULL, 36, 2, '2025-10-16', '13:30:00', 'استشارة عامة', NULL, 'completed', 'test_appointment_3_36', '2025-11-08 13:05:45', '2025-11-08 13:05:45'),
(72, 4, NULL, 36, 1, '2025-10-30', '14:37:00', 'استشارة عامة', NULL, 'completed', 'test_appointment_4_36', '2025-11-08 13:05:45', '2025-11-08 13:05:45'),
(73, 2, NULL, 37, 3, '2025-10-17', '14:51:00', 'استشارة عامة', NULL, 'completed', 'test_appointment_2_37', '2025-11-08 13:05:45', '2025-11-08 13:05:45'),
(74, 3, NULL, 37, 3, '2025-10-15', '15:31:00', 'استشارة عامة', NULL, 'completed', 'test_appointment_3_37', '2025-11-08 13:05:45', '2025-11-08 13:05:45'),
(75, 4, NULL, 37, 1, '2025-10-21', '11:25:00', 'استشارة عامة', NULL, 'completed', 'test_appointment_4_37', '2025-11-08 13:05:45', '2025-11-08 13:05:45'),
(76, 206, NULL, 47, 1, '2025-11-08', '13:30:00', 'استشارة عامة', NULL, 'cancelled', NULL, '2025-11-08 13:07:28', '2025-11-08 14:23:05'),
(77, 206, NULL, 47, 1, '2025-11-08', '16:00:00', 'استشارة عامة', NULL, 'confirmed', NULL, '2025-11-08 13:08:08', '2025-11-08 14:23:32');

--
-- Triggers `appointments`
--
DELIMITER $$
CREATE TRIGGER `send_automatic_reminders` AFTER INSERT ON `appointments` FOR EACH ROW BEGIN
    -- إرسال تذكير فوري
    INSERT INTO push_notifications (user_id, title, message, type)
    VALUES (NEW.user_id, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment');
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_doctor_rating` AFTER INSERT ON `appointments` FOR EACH ROW BEGIN
        -- Simple trigger that doesn't try to update non-existent columns
        -- Can be extended later to handle actual rating updates
        -- For now, this is just a placeholder
    END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `appointment_info_view`
-- (See below for the actual view)
--
CREATE TABLE `appointment_info_view` (
`id` int(11)
,`appointment_date` date
,`appointment_time` time
,`appointment_datetime` datetime
,`status` enum('pending','confirmed','completed','cancelled')
,`notes` text
,`created_at` timestamp
,`patient_name` varchar(100)
,`patient_phone` varchar(20)
,`patient_email` varchar(100)
,`doctor_name` varchar(100)
,`doctor_phone` varchar(20)
,`clinic_name` varchar(200)
,`hospital_name` varchar(200)
,`specialty_name` varchar(100)
);

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

CREATE TABLE `cities` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `governorate` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cities`
--

INSERT INTO `cities` (`id`, `name`, `governorate`, `created_at`) VALUES
(1, 'القاهرة', 'القاهرة', '2025-08-10 18:49:54'),
(2, 'الإسكندرية', 'الإسكندرية', '2025-08-10 18:49:54'),
(3, 'الجيزة', 'الجيزة', '2025-08-10 18:49:54'),
(4, 'المنوفية', 'المنوفية', '2025-08-10 18:49:54'),
(5, 'الشرقية', 'الشرقية', '2025-08-10 18:49:54'),
(6, 'الغربية', 'الغربية', '2025-08-10 18:49:54'),
(7, 'أسيوط', 'أسيوط', '2025-08-10 18:49:54'),
(8, 'سوهاج', 'سوهاج', '2025-08-10 18:49:54'),
(9, 'قنا', 'قنا', '2025-08-10 18:49:54'),
(10, 'الأقصر', 'الأقصر', '2025-08-10 18:49:54'),
(11, 'أسوان', 'أسوان', '2025-08-10 18:49:54'),
(12, 'بني سويف', 'بني سويف', '2025-08-10 18:49:54'),
(13, 'الفيوم', 'الفيوم', '2025-08-10 18:49:54'),
(14, 'المنيا', 'المنيا', '2025-08-10 18:49:54'),
(15, 'دمياط', 'دمياط', '2025-08-10 18:49:54'),
(16, 'بورسعيد', 'بورسعيد', '2025-08-10 18:49:54'),
(17, 'الإسماعيلية', 'الإسماعيلية', '2025-08-10 18:49:54'),
(18, 'السويس', 'السويس', '2025-08-10 18:49:54'),
(19, 'شرم الشيخ', 'جنوب سيناء', '2025-08-10 18:49:54'),
(20, 'دهب', 'جنوب سيناء', '2025-08-10 18:49:54'),
(61, 'الرياض', 'الرياض', '2025-08-16 07:29:09'),
(62, 'جدة', 'مكة المكرمة', '2025-08-16 07:29:09'),
(63, 'الدمام', 'الشرقية', '2025-08-16 07:29:09'),
(64, 'مكة المكرمة', 'مكة المكرمة', '2025-08-16 07:29:09'),
(65, 'المدينة المنورة', 'المدينة المنورة', '2025-08-16 07:29:09'),
(66, 'تبوك', 'تبوك', '2025-08-16 07:29:09'),
(67, 'بريدة', 'القصيم', '2025-08-16 07:29:09'),
(68, 'خميس مشيط', 'عسير', '2025-08-16 07:29:09'),
(69, 'حائل', 'حائل', '2025-08-16 07:29:09'),
(70, 'أبها', 'عسير', '2025-08-16 07:29:09');

-- --------------------------------------------------------

--
-- Table structure for table `cities_temp`
--

CREATE TABLE `cities_temp` (
  `id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `governorate` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cities_temp`
--

INSERT INTO `cities_temp` (`id`, `name`, `governorate`) VALUES
(70, 'أبها', 'عسير'),
(11, 'أسوان', 'أسوان'),
(7, 'أسيوط', 'أسيوط'),
(10, 'الأقصر', 'الأقصر'),
(2, 'الإسكندرية', 'الإسكندرية'),
(17, 'الإسماعيلية', 'الإسماعيلية'),
(3, 'الجيزة', 'الجيزة'),
(63, 'الدمام', 'الشرقية'),
(61, 'الرياض', 'الرياض'),
(18, 'السويس', 'السويس'),
(5, 'الشرقية', 'الشرقية'),
(6, 'الغربية', 'الغربية'),
(13, 'الفيوم', 'الفيوم'),
(1, 'القاهرة', 'القاهرة'),
(65, 'المدينة المنورة', 'المدينة المنورة'),
(4, 'المنوفية', 'المنوفية'),
(14, 'المنيا', 'المنيا'),
(67, 'بريدة', 'القصيم'),
(12, 'بني سويف', 'بني سويف'),
(16, 'بورسعيد', 'بورسعيد'),
(66, 'تبوك', 'تبوك'),
(62, 'جدة', 'مكة المكرمة'),
(69, 'حائل', 'حائل'),
(68, 'خميس مشيط', 'عسير'),
(15, 'دمياط', 'دمياط'),
(20, 'دهب', 'جنوب سيناء'),
(8, 'سوهاج', 'سوهاج'),
(19, 'شرم الشيخ', 'جنوب سيناء'),
(9, 'قنا', 'قنا'),
(64, 'مكة المكرمة', 'مكة المكرمة');

-- --------------------------------------------------------

--
-- Table structure for table `clinics`
--

CREATE TABLE `clinics` (
  `id` int(11) NOT NULL,
  `hospital_id` int(11) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `specialty_id` int(11) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(200) DEFAULT NULL,
  `consultation_fee` decimal(10,2) DEFAULT 0.00,
  `rating` decimal(3,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clinics`
--

INSERT INTO `clinics` (`id`, `hospital_id`, `name`, `specialty_id`, `address`, `phone`, `email`, `description`, `image`, `consultation_fee`, `rating`, `created_at`) VALUES
(1, 1, 'عيادة الطب العام', 1, NULL, NULL, NULL, 'عيادة متخصصة في الطب العام', NULL, 300.00, 0.00, '2025-11-08 12:16:28'),
(2, 2, 'عيادة النساء والتوليد', 2, NULL, NULL, NULL, 'عيادة نساء وتوليد متكاملة', NULL, 250.00, 0.00, '2025-11-08 12:16:28'),
(3, 1, 'عيادة القلب', 3, NULL, NULL, NULL, 'عيادة متخصصة في أمراض القلب', NULL, 400.00, 0.00, '2025-11-08 12:16:28'),
(4, 3, 'عيادة الأطفال', 4, NULL, NULL, NULL, 'عيادة أطفال حديثة', NULL, 200.00, 0.00, '2025-11-08 12:16:28'),
(5, 2, 'عيادة العظام', 5, NULL, NULL, NULL, 'عيادة عظام وجراحة', NULL, 350.00, 0.00, '2025-11-08 12:16:28'),
(6, 1, 'عيادة الجلدية', 6, NULL, NULL, NULL, 'عيادة الأمراض الجلدية', NULL, 180.00, 0.00, '2025-11-08 12:16:28'),
(7, 4, 'عيادة الأسنان', 7, NULL, NULL, NULL, 'عيادة أسنان متكاملة', NULL, 280.00, 0.00, '2025-11-08 12:16:28'),
(8, 3, 'عيادة الأمراض العصبية', 8, NULL, NULL, NULL, 'عيادة متخصصة في الأمراض العصبية', NULL, 220.00, 0.00, '2025-11-08 12:16:28'),
(9, 5, 'عيادة الباطنية', 1, NULL, NULL, NULL, 'عيادة الأمراض الباطنية', NULL, 320.00, 0.00, '2025-11-08 12:16:28'),
(10, 4, 'عيادة النساء والتوليد الثانية', 2, NULL, NULL, NULL, 'عيادة نساء وتوليد حديثة', NULL, 260.00, 0.00, '2025-11-08 12:16:28');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `hospital_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `description`, `hospital_id`, `created_at`) VALUES
(1, 'قسم القلب', 'قسم متخصص في أمراض القلب والأوعية الدموية', 1, '2025-08-10 18:49:54'),
(2, 'قسم العيون', 'قسم متخصص في أمراض العيون والرؤية', 1, '2025-08-10 18:49:54'),
(3, 'قسم الأسنان', 'قسم متخصص في طب الأسنان', 2, '2025-08-10 18:49:54'),
(4, 'قسم الأطفال', 'قسم متخصص في رعاية الأطفال', 2, '2025-08-10 18:49:54'),
(5, 'قسم النساء والولادة', 'قسم متخصص في صحة المرأة والولادة', 3, '2025-08-10 18:49:54'),
(6, 'قسم الجلدية', 'قسم متخصص في أمراض الجلد', 3, '2025-08-10 18:49:54'),
(7, 'قسم العظام', 'قسم متخصص في أمراض العظام والمفاصل', 4, '2025-08-10 18:49:54'),
(8, 'قسم الأعصاب', 'قسم متخصص في أمراض الجهاز العصبي', 4, '2025-08-10 18:49:54'),
(9, 'قسم الباطنة', 'قسم متخصص في الأمراض الباطنية', 5, '2025-08-10 18:49:54'),
(10, 'قسم الأنف والأذن والحنجرة', 'قسم متخصص في أمراض الأنف والأذن والحنجرة', 5, '2025-08-10 18:49:54');

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `specialty_id` int(11) DEFAULT NULL,
  `clinic_id` int(11) DEFAULT NULL,
  `hospital_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `experience_years` int(11) DEFAULT NULL,
  `education` text DEFAULT NULL,
  `image` varchar(200) DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `consultation_fee` decimal(10,2) DEFAULT 0.00,
  `bio` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) NOT NULL,
  `calculated_rating` decimal(3,2) DEFAULT 0.00 COMMENT 'Calculated average rating',
  `total_ratings` int(11) DEFAULT 0 COMMENT 'Total number of ratings'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`id`, `full_name`, `specialty_id`, `clinic_id`, `hospital_id`, `department_id`, `phone`, `email`, `experience_years`, `education`, `image`, `rating`, `is_active`, `consultation_fee`, `bio`, `created_at`, `user_id`, `calculated_rating`, `total_ratings`) VALUES
(36, 'د. منى عبدالله إبراهيم', 2, 2, 2, NULL, NULL, NULL, 12, 'ماجستير أمراض النساء والتوليد - جامعة عين شمس', 'assets/images/doctor-2.jpg', 4.80, 1, 250.00, NULL, '2025-11-08 12:16:28', 221, 3.00, 1),
(37, 'د. خالد محمود حسن', 3, 3, 1, NULL, NULL, NULL, 20, 'دكتوراه في جراحة القلب - جامعة الأزهر', 'assets/images/doctor-3.jpg', 4.70, 1, 400.00, NULL, '2025-11-08 12:16:28', 222, 4.50, 2),
(38, 'د. فاطمة علي أحمد', 4, 4, 3, NULL, NULL, NULL, 8, 'بكالوريوس طب الأطفال - جامعة المنصورة', 'assets/images/doctor-4.jpg', 4.60, 1, 200.00, NULL, '2025-11-08 12:16:28', 223, 0.00, 0),
(39, 'د. محمد عبدالرحيم خالد', 5, 5, 2, NULL, NULL, NULL, 18, 'ماجستير جراحة العظام - جامعة الإسكندرية', 'assets/images/doctor-5.jpg', 4.50, 1, 350.00, NULL, '2025-11-08 12:16:28', 224, 0.00, 0),
(40, 'د. نادية سالم محمد', 6, 6, 1, NULL, NULL, NULL, 10, 'دكتوراه في الأمراض الجلدية - جامعة القاهرة', 'assets/images/doctor-6.jpg', 4.40, 1, 180.00, NULL, '2025-11-08 12:16:28', 225, 0.00, 0),
(41, 'د. عمر حسن علي', 7, 7, 4, NULL, NULL, NULL, 14, 'بكالوريوس طب الأسنان - جامعة طنطا', 'assets/images/doctor-7.jpg', 4.30, 1, 280.00, NULL, '2025-11-08 12:16:28', 226, 0.00, 0),
(42, 'د. سارة محمود عبدالله', 8, 8, 3, NULL, NULL, NULL, 6, 'ماجستير الأمراض العصبية - جامعة المنوفية', 'assets/images/doctor-8.jpg', 4.20, 1, 220.00, NULL, '2025-11-08 12:16:28', 227, 0.00, 0),
(43, 'د. مصطفي كامل أحمد', 1, 9, 5, NULL, NULL, NULL, 11, 'دكتوراه في الأمراض الباطنية - جامعة سوهاج', 'assets/images/doctor-9.jpg', 4.10, 1, 320.00, NULL, '2025-11-08 12:16:28', 228, 0.00, 0),
(44, 'د. هناء يوسف محمد', 2, 10, 4, NULL, NULL, NULL, 9, 'بكالوريوس الطب والجراحة - جامعة أسيوط', 'assets/images/doctor-10.jpg', 4.00, 1, 260.00, NULL, '2025-11-08 12:16:28', 229, 0.00, 0),
(45, 'د. حسين عبدالله محمود', 3, 1, 2, NULL, NULL, NULL, 16, 'ماجستير أمراض القلب - جامعة بنها', 'assets/images/doctor-11.jpg', 3.90, 1, 380.00, NULL, '2025-11-08 12:16:28', 230, 0.00, 0),
(46, 'د. ليلى أحمد سعيد', 4, 2, 5, NULL, NULL, NULL, 7, 'دكتوراه في طب الأطفال - جامعة الفيوم', 'assets/images/doctor-12.jpg', 3.80, 1, 190.00, NULL, '2025-11-08 12:16:28', 231, 0.00, 0),
(47, 'د. أحمد محمد السيد', 1, 1, 1, NULL, '01234567890', 'dr.ahmed@medical.com', 15, 'بكالوريوس الطب والجراحة - جامعة القاهرة', NULL, 4.80, 1, 250.00, NULL, '2025-11-08 12:35:56', 232, 0.00, 0);

-- --------------------------------------------------------

--
-- Table structure for table `doctor_availability`
--

CREATE TABLE `doctor_availability` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `day_of_week` varchar(20) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `slot_duration` int(11) NOT NULL DEFAULT 30,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `doctor_availability`
--

INSERT INTO `doctor_availability` (`id`, `doctor_id`, `day_of_week`, `start_time`, `end_time`, `slot_duration`, `is_active`, `created_at`, `updated_at`) VALUES
(6, 221, 'saturday', '09:00:00', '17:00:00', 30, 1, '2025-11-08 12:42:58', '2025-11-08 12:42:58'),
(7, 221, 'sunday', '09:00:00', '17:00:00', 30, 1, '2025-11-08 12:42:58', '2025-11-08 12:42:58'),
(8, 221, 'monday', '09:00:00', '17:00:00', 30, 1, '2025-11-08 12:42:58', '2025-11-08 12:42:58'),
(9, 221, 'tuesday', '09:00:00', '17:00:00', 30, 1, '2025-11-08 12:42:58', '2025-11-08 12:42:58'),
(10, 221, 'wednesday', '09:00:00', '17:00:00', 30, 1, '2025-11-08 12:42:58', '2025-11-08 12:42:58'),
(11, 222, 'saturday', '09:00:00', '17:00:00', 30, 1, '2025-11-08 12:42:58', '2025-11-08 12:42:58'),
(12, 222, 'sunday', '09:00:00', '17:00:00', 30, 1, '2025-11-08 12:42:58', '2025-11-08 12:42:58'),
(13, 222, 'monday', '09:00:00', '17:00:00', 30, 1, '2025-11-08 12:42:58', '2025-11-08 12:42:58'),
(14, 222, 'tuesday', '09:00:00', '17:00:00', 30, 1, '2025-11-08 12:42:58', '2025-11-08 12:42:58'),
(15, 222, 'wednesday', '09:00:00', '17:00:00', 30, 1, '2025-11-08 12:42:58', '2025-11-08 12:42:58'),
(16, 223, 'saturday', '09:00:00', '17:00:00', 30, 1, '2025-11-08 12:42:58', '2025-11-08 12:42:58'),
(17, 223, 'sunday', '09:00:00', '17:00:00', 30, 1, '2025-11-08 12:42:58', '2025-11-08 12:42:58'),
(18, 223, 'monday', '09:00:00', '17:00:00', 30, 1, '2025-11-08 12:42:58', '2025-11-08 12:42:58'),
(19, 223, 'tuesday', '09:00:00', '17:00:00', 30, 1, '2025-11-08 12:42:58', '2025-11-08 12:42:58'),
(20, 223, 'wednesday', '09:00:00', '17:00:00', 30, 1, '2025-11-08 12:42:58', '2025-11-08 12:42:58'),
(21, 224, 'saturday', '09:00:00', '17:00:00', 30, 1, '2025-11-08 12:42:58', '2025-11-08 12:42:58'),
(22, 224, 'sunday', '09:00:00', '17:00:00', 30, 1, '2025-11-08 12:42:58', '2025-11-08 12:42:58'),
(23, 224, 'monday', '09:00:00', '17:00:00', 30, 1, '2025-11-08 12:42:58', '2025-11-08 12:42:58'),
(24, 224, 'tuesday', '09:00:00', '17:00:00', 30, 1, '2025-11-08 12:42:58', '2025-11-08 12:42:58'),
(25, 224, 'wednesday', '09:00:00', '17:00:00', 30, 1, '2025-11-08 12:42:58', '2025-11-08 12:42:58'),
(26, 232, 'saturday', '09:00:00', '17:00:00', 30, 1, '2025-11-08 12:43:16', '2025-11-08 12:43:16'),
(27, 232, 'sunday', '09:00:00', '17:00:00', 30, 1, '2025-11-08 12:43:16', '2025-11-08 12:43:16'),
(28, 232, 'monday', '09:00:00', '17:00:00', 30, 1, '2025-11-08 12:43:16', '2025-11-08 12:43:16'),
(29, 232, 'tuesday', '09:00:00', '17:00:00', 30, 1, '2025-11-08 12:43:16', '2025-11-08 12:43:16'),
(30, 232, 'wednesday', '09:00:00', '17:00:00', 30, 1, '2025-11-08 12:43:16', '2025-11-08 12:43:16'),
(31, 232, 'thursday', '09:00:00', '17:00:00', 30, 1, '2025-11-08 12:43:16', '2025-11-08 12:43:16');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_availability_exceptions`
--

CREATE TABLE `doctor_availability_exceptions` (
  `id` int(11) NOT NULL,
  `doctor_info_id` int(11) NOT NULL,
  `exception_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `doctor_complete_profile`
-- (See below for the actual view)
--
CREATE TABLE `doctor_complete_profile` (
`doctor_info_id` int(11)
,`full_name` varchar(100)
,`email` varchar(100)
,`phone` varchar(20)
,`license_number` varchar(50)
,`national_id` varchar(20)
,`date_of_birth` date
,`gender` enum('male','female')
,`nationality` varchar(50)
,`address` text
,`emergency_contact` varchar(100)
,`emergency_phone` varchar(20)
,`languages_spoken` text
,`consultation_fee` decimal(10,2)
,`follow_up_fee` decimal(10,2)
,`emergency_fee` decimal(10,2)
,`accepts_insurance` tinyint(1)
,`education_details` longtext
,`certifications` longtext
,`awards` longtext
,`publications` longtext
,`research_interests` text
,`years_of_experience` int(11)
,`special_interests` text
,`treatment_methods` text
,`success_rate` decimal(5,2)
,`patient_satisfaction_rate` decimal(5,2)
,`total_patients_treated` int(11)
,`verified` tinyint(1)
,`verification_date` timestamp
,`created_at` timestamp
,`updated_at` timestamp
,`average_rating` decimal(7,6)
,`total_reviews` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `doctor_info`
--

CREATE TABLE `doctor_info` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `license_number` varchar(50) NOT NULL,
  `national_id` varchar(20) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female') NOT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `marital_status` enum('single','married','divorced','widowed') DEFAULT NULL,
  `address` text DEFAULT NULL,
  `emergency_contact` varchar(100) DEFAULT NULL,
  `emergency_phone` varchar(20) DEFAULT NULL,
  `languages_spoken` text DEFAULT NULL,
  `consultation_fee` decimal(10,2) DEFAULT 0.00,
  `follow_up_fee` decimal(10,2) DEFAULT 0.00,
  `emergency_fee` decimal(10,2) DEFAULT 0.00,
  `accepts_insurance` tinyint(1) DEFAULT 0,
  `insurance_companies` text DEFAULT NULL,
  `education_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`education_details`)),
  `certifications` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`certifications`)),
  `awards` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`awards`)),
  `publications` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`publications`)),
  `research_interests` text DEFAULT NULL,
  `professional_memberships` text DEFAULT NULL,
  `hospital_affiliations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`hospital_affiliations`)),
  `clinic_locations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`clinic_locations`)),
  `working_hours` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`working_hours`)),
  `appointment_duration` int(11) DEFAULT 30,
  `max_daily_appointments` int(11) DEFAULT 20,
  `online_consultation` tinyint(1) DEFAULT 0,
  `telemedicine_available` tinyint(1) DEFAULT 0,
  `home_visits` tinyint(1) DEFAULT 0,
  `special_interests` text DEFAULT NULL,
  `treatment_methods` text DEFAULT NULL,
  `equipment_used` text DEFAULT NULL,
  `success_rate` decimal(5,2) DEFAULT NULL,
  `patient_satisfaction_rate` decimal(5,2) DEFAULT NULL,
  `years_of_experience` int(11) DEFAULT NULL,
  `total_patients_treated` int(11) DEFAULT 0,
  `verified` tinyint(1) DEFAULT 0,
  `verification_date` timestamp NULL DEFAULT NULL,
  `background_check_completed` tinyint(1) DEFAULT 0,
  `malpractice_insurance` tinyint(1) DEFAULT 0,
  `insurance_expiry` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `doctor_info_view`
-- (See below for the actual view)
--
CREATE TABLE `doctor_info_view` (
`id` int(11)
,`full_name` varchar(100)
,`phone` varchar(20)
,`email` varchar(100)
,`experience_years` int(11)
,`rating` decimal(3,2)
,`consultation_fee` decimal(10,2)
,`bio` text
,`is_active` tinyint(1)
,`specialty_name` varchar(100)
,`hospital_name` varchar(200)
,`clinic_name` varchar(200)
,`department_name` varchar(100)
);

-- --------------------------------------------------------

--
-- Table structure for table `doctor_ratings`
--

CREATE TABLE `doctor_ratings` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL COMMENT '1-5 stars',
  `review` text DEFAULT NULL COMMENT 'Optional review text',
  `is_anonymous` tinyint(1) DEFAULT 0 COMMENT 'Whether the review is anonymous',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `doctor_ratings`
--

INSERT INTO `doctor_ratings` (`id`, `doctor_id`, `user_id`, `rating`, `review`, `is_anonymous`, `created_at`, `updated_at`) VALUES
(5, 221, 4, 3, 'مقبول، لكن يمكن تحسين خدمة الانتظار', 0, '2025-11-08 12:48:24', '2025-11-08 12:48:24'),
(6, 222, 2, 5, 'محترف جداً ومتخصص في مجاله', 0, '2025-11-08 12:48:24', '2025-11-08 12:48:24'),
(7, 222, 5, 4, 'استشارته كانت ممتازة', 0, '2025-11-08 12:48:24', '2025-11-08 12:48:24');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_reviews`
--

CREATE TABLE `doctor_reviews` (
  `id` int(11) NOT NULL,
  `doctor_info_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `rating` decimal(3,2) NOT NULL CHECK (`rating` >= 0 and `rating` <= 5),
  `review_text` text DEFAULT NULL,
  `treatment_date` date DEFAULT NULL,
  `wait_time_rating` decimal(3,2) DEFAULT NULL,
  `staff_rating` decimal(3,2) DEFAULT NULL,
  `facility_rating` decimal(3,2) DEFAULT NULL,
  `communication_rating` decimal(3,2) DEFAULT NULL,
  `would_recommend` tinyint(1) DEFAULT 1,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_schedule`
--

CREATE TABLE `doctor_schedule` (
  `id` int(11) NOT NULL,
  `doctor_info_id` int(11) NOT NULL,
  `day_of_week` enum('sunday','monday','tuesday','wednesday','thursday','friday','saturday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `break_start` time DEFAULT NULL,
  `break_end` time DEFAULT NULL,
  `max_appointments` int(11) DEFAULT 10,
  `is_available` tinyint(1) DEFAULT 1,
  `location` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_schedules`
--

CREATE TABLE `doctor_schedules` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `day_of_week` enum('sunday','monday','tuesday','wednesday','thursday','friday','saturday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctor_specialties`
--

CREATE TABLE `doctor_specialties` (
  `id` int(11) NOT NULL,
  `doctor_info_id` int(11) NOT NULL,
  `specialty_id` int(11) NOT NULL,
  `years_experience` int(11) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `doctor_stats_view`
-- (See below for the actual view)
--
CREATE TABLE `doctor_stats_view` (
`id` int(11)
,`full_name` varchar(100)
,`rating` decimal(3,2)
,`consultation_fee` decimal(10,2)
,`total_appointments` bigint(21)
,`completed_appointments` bigint(21)
,`pending_appointments` bigint(21)
,`cancelled_appointments` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `doctor_verification`
--

CREATE TABLE `doctor_verification` (
  `id` int(11) NOT NULL,
  `doctor_info_id` int(11) NOT NULL,
  `verification_type` enum('license','education','certification','background') NOT NULL,
  `document_path` varchar(500) DEFAULT NULL,
  `verification_status` enum('pending','verified','rejected') DEFAULT 'pending',
  `verified_by` varchar(100) DEFAULT NULL,
  `verification_date` timestamp NULL DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hospitals`
--

CREATE TABLE `hospitals` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `address` text NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `website` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(200) DEFAULT NULL,
  `type` enum('حكومي','خاص') DEFAULT 'حكومي',
  `rating` decimal(3,2) DEFAULT 0.00,
  `is_24h` tinyint(1) DEFAULT 0,
  `has_emergency` tinyint(1) DEFAULT 0,
  `has_insurance` tinyint(1) DEFAULT 0,
  `city_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hospitals`
--

INSERT INTO `hospitals` (`id`, `name`, `address`, `phone`, `email`, `website`, `description`, `image`, `type`, `rating`, `is_24h`, `has_emergency`, `has_insurance`, `city_id`, `created_at`) VALUES
(1, 'مستشفى القاهرة العام', 'شارع القصر العيني، القاهرة', '02-23658974', 'info@cairohospital.com', 'www.cairohospital.com', 'مستشفى عام متكامل الخدمات', NULL, 'حكومي', 4.50, 1, 1, 1, 1, '2025-08-10 18:49:54'),
(2, 'مستشفى المعادي', 'شارع النصر، المعادي، القاهرة', '02-25258963', 'info@maadi-hospital.com', 'www.maadi-hospital.com', 'مستشفى خاص بمعايير عالمية', NULL, 'حكومي', 4.80, 0, 1, 1, 1, '2025-08-10 18:49:54'),
(3, 'مستشفى مصر الجديدة', 'شارع الثورة، مصر الجديدة', '02-24158974', 'info@newcairo-hospital.com', 'www.newcairo-hospital.com', 'مستشفى حديث التجهيزات', NULL, 'حكومي', 4.20, 0, 0, 1, 1, '2025-08-10 18:49:54'),
(4, 'مستشفى الإسكندرية العام', 'شارع الإبراهيمية، الإسكندرية', '03-45678912', 'info@alexhospital.com', 'www.alexhospital.com', 'مستشفى عام في الإسكندرية', NULL, 'حكومي', 4.30, 0, 1, 1, 2, '2025-08-10 18:49:54'),
(5, 'مستشفى الجيزة التخصصي', 'شارع الهرم، الجيزة', '02-34567890', 'info@gizahospital.com', 'www.gizahospital.com', 'مستشفى تخصصي في الجيزة', NULL, 'حكومي', 4.60, 0, 1, 0, 3, '2025-08-10 18:49:54'),
(6, 'مستشفى الملك فهد', 'شارع الملك فهد، حي النزهة، الرياض', '+966 11 123 4567', 'info@kfh.com', 'www.kfh.com', 'مستشفى متخصص في علاج أمراض القلب والجراحات المتقدمة', 'hospital-1.jpg', 'حكومي', 4.80, 1, 0, 0, NULL, '2025-08-16 07:29:09'),
(7, 'مركز الأمير سلطان الطبي', 'شارع التحلية، حي الكورنيش، جدة', '+966 12 234 5678', 'info@pstc.com', 'www.pstc.com', 'مركز طبي متقدم في طب العيون وجراحات التجميل', 'hospital-2.jpg', 'خاص', 4.90, 1, 0, 0, NULL, '2025-08-16 07:29:09'),
(8, 'مستشفى الملك خالد', 'شارع الملك خالد، حي الشاطئ، الدمام', '+966 13 345 6789', 'info@kkh.com', 'www.kkh.com', 'مستشفى متخصص في طب الأعصاب وجراحات العظام', 'hospital-3.jpg', 'حكومي', 4.70, 1, 0, 0, NULL, '2025-08-16 07:29:09'),
(9, 'مستشفى الملك عبدالعزيز', 'شارع التحلية، حي الكورنيش، جدة', '+966 12 456 7890', 'info@kauh.com', 'www.kauh.com', 'مستشفى جامعي متقدم في جميع التخصصات', 'hospital-4.jpg', 'حكومي', 4.60, 1, 0, 0, NULL, '2025-08-16 07:29:09'),
(10, 'مركز الملك فهد الطبي', 'شارع الملك فهد، حي النزهة، الرياض', '+966 11 567 8901', 'info@kfmc.com', 'www.kfmc.com', 'مركز طبي متخصص في علاج السرطان', 'hospital-5.jpg', 'حكومي', 4.80, 1, 0, 0, NULL, '2025-08-16 07:29:09'),
(26, 'Test Hospital 9329', '', '0123456789', 'test_hospital_2047@demo.com', NULL, 'تم التسجيل عبر الموقع', NULL, 'خاص', 0.00, 0, 0, 0, NULL, '2025-11-22 18:51:46'),
(27, 'Test Hospital 5037', '', '0123456789', 'test_hospital_2153@demo.com', NULL, 'تم التسجيل عبر الموقع', NULL, 'خاص', 0.00, 0, 0, 0, NULL, '2025-11-22 18:52:18');

-- --------------------------------------------------------

--
-- Table structure for table `push_notifications`
--

CREATE TABLE `push_notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('appointment','reminder','system') DEFAULT 'system',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `push_notifications`
--

INSERT INTO `push_notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(2, 2, 'تذكير بالموعد', 'تذكير: موعدك مع د. فاطمة أحمد حسن غداً في 2:00 مساءً', 'reminder', 0, '2025-08-10 18:49:55'),
(3, 3, 'موعد جديد', 'تم حجز موعد جديد مع د. محمد سعيد أحمد', 'appointment', 0, '2025-08-10 18:49:55'),
(4, 4, 'تحديث الموعد', 'تم تحديث موعدك مع د. سارة محمود علي', 'appointment', 1, '2025-08-10 18:49:55'),
(5, 5, 'تذكير بالموعد', 'تذكير: موعدك مع د. خالد عبد الرحمن بعد 6 ساعات', 'reminder', 0, '2025-08-10 18:49:55'),
(6, 2, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 12:54:26'),
(7, 3, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 12:54:26'),
(8, 4, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 12:54:26'),
(9, 2, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 12:54:26'),
(10, 3, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 12:54:26'),
(11, 4, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 12:54:26'),
(12, 2, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 12:54:26'),
(13, 3, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 12:54:26'),
(14, 4, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 12:54:26'),
(15, 2, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 12:56:06'),
(16, 3, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 12:56:06'),
(17, 4, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 12:56:06'),
(18, 2, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 12:56:06'),
(19, 3, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 12:56:06'),
(20, 4, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 12:56:06'),
(21, 2, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 12:56:06'),
(22, 3, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 12:56:06'),
(23, 4, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 12:56:06'),
(24, 2, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 12:58:25'),
(25, 3, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 12:58:25'),
(26, 4, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 12:58:25'),
(27, 2, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 12:58:25'),
(28, 3, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 12:58:25'),
(29, 4, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 12:58:25'),
(30, 2, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 12:58:25'),
(31, 3, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 12:58:25'),
(32, 4, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 12:58:25'),
(33, 2, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 13:05:45'),
(34, 3, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 13:05:45'),
(35, 4, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 13:05:45'),
(36, 2, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 13:05:45'),
(37, 3, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 13:05:45'),
(38, 4, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 13:05:45'),
(39, 2, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 13:05:45'),
(40, 3, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 13:05:45'),
(41, 4, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 13:05:45'),
(42, 206, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 13:07:28'),
(43, 206, 'تأكيد الحجز', 'تم تأكيد حجز موعدك بنجاح', 'appointment', 0, '2025-11-08 13:08:08');

-- --------------------------------------------------------

--
-- Table structure for table `reminders`
--

CREATE TABLE `reminders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `reminder_type` enum('email','sms','push') NOT NULL,
  `reminder_time` datetime NOT NULL,
  `message` text DEFAULT NULL,
  `is_sent` tinyint(1) DEFAULT 0,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reminder_logs`
--

CREATE TABLE `reminder_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `reminder_type` enum('email','sms','push') NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('sent','failed') DEFAULT 'sent',
  `error_message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reminder_settings`
--

CREATE TABLE `reminder_settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email_enabled` tinyint(1) DEFAULT 1,
  `sms_enabled` tinyint(1) DEFAULT 1,
  `push_enabled` tinyint(1) DEFAULT 1,
  `reminder_hours_before` int(11) DEFAULT 24,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reminder_settings`
--

INSERT INTO `reminder_settings` (`id`, `user_id`, `email_enabled`, `sms_enabled`, `push_enabled`, `reminder_hours_before`, `created_at`, `updated_at`) VALUES
(2, 2, 1, 0, 1, 12, '2025-08-10 18:49:55', '2025-08-10 18:49:55'),
(3, 3, 1, 1, 0, 48, '2025-08-10 18:49:55', '2025-08-10 18:49:55'),
(4, 4, 0, 1, 1, 24, '2025-08-10 18:49:55', '2025-08-10 18:49:55'),
(5, 5, 1, 1, 1, 6, '2025-08-10 18:49:55', '2025-08-10 18:49:55');

-- --------------------------------------------------------

--
-- Table structure for table `specialties`
--

CREATE TABLE `specialties` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `specialties`
--

INSERT INTO `specialties` (`id`, `name`, `description`, `icon`) VALUES
(1, 'طب عام', NULL, NULL),
(2, 'نساء وتوليد', NULL, NULL),
(3, 'قلب وأوعية دموية', NULL, NULL),
(4, 'أطفال', NULL, NULL),
(5, 'عظام', NULL, NULL),
(6, 'جلدية', NULL, NULL),
(7, 'أسنان', NULL, NULL),
(8, 'أمراض عصبية', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `full_name` varchar(100) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `specialty` varchar(100) DEFAULT 'طب عام',
  `oncall_status` enum('available','unavailable') DEFAULT 'available',
  `profile_image` varchar(255) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female') NOT NULL,
  `role` enum('patient','doctor','hospital','admin') DEFAULT 'patient',
  `city_id` int(11) DEFAULT NULL,
  `insurance_provider` varchar(100) DEFAULT NULL,
  `insurance_number` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_type` varchar(50) NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `is_active`, `full_name`, `name`, `phone`, `specialty`, `oncall_status`, `profile_image`, `date_of_birth`, `gender`, `role`, `city_id`, `insurance_provider`, `insurance_number`, `created_at`, `updated_at`, `user_type`) VALUES
(2, 'fatima_patient', 'fatima@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'فاطمة أحمد حسن', NULL, '01012345689', 'طب عام', 'available', NULL, '1985-08-22', 'female', 'patient', 2, 'شركة التأمين المصرية', 'INS001235', '2025-08-10 18:49:54', '2025-08-10 18:49:54', 'user'),
(3, 'mohamed_patient', 'mohamed@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'محمد سعيد أحمد', NULL, '01012345690', 'طب عام', 'available', NULL, '1992-12-10', 'male', 'patient', 3, NULL, NULL, '2025-08-10 18:49:54', '2025-08-10 18:49:54', 'user'),
(4, 'sara_patient', 'sara@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'سارة محمود علي', NULL, '01012345691', 'طب عام', 'available', NULL, '1988-03-25', 'female', 'patient', 1, 'شركة التأمين المصرية', 'INS001236', '2025-08-10 18:49:54', '2025-08-10 18:49:54', 'user'),
(5, 'khaled_patient', 'khaled@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'خالد عبد الرحمن', NULL, '01012345692', 'طب عام', 'available', NULL, '1995-07-18', 'male', 'patient', 2, NULL, NULL, '2025-08-10 18:49:54', '2025-08-10 18:49:54', 'user'),
(11, 'cairo_hospital', 'admin@cairohospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'مستشفى القاهرة العام', NULL, '02-23658974', 'طب عام', 'available', NULL, '1980-01-01', 'male', 'hospital', 1, NULL, NULL, '2025-08-10 18:49:54', '2025-08-10 18:49:54', 'user'),
(12, 'maadi_hospital', 'admin@maadi-hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'مستشفى المعادي', NULL, '02-25258963', 'طب عام', 'available', NULL, '1985-01-01', 'male', 'hospital', 1, NULL, NULL, '2025-08-10 18:49:54', '2025-08-10 18:49:54', 'user'),
(13, 'newcairo_hospital', 'admin@newcairo-hospital.com', '$2y$10$n37o/lQAOqIg.p/jJreEXOP/OMh0I6fZPF7rlabCEBq72G4TxoD6i.$2y$10$W1hsqqUemVTsKlm8uzOATu7WIpgxu6F3bHsc95JWUwdRqOsAWwF3S', 1, 'مستشفى مصر الجديدة', NULL, '02-24158974', 'طب عام', 'available', NULL, '1990-01-01', 'male', 'admin', 1, NULL, NULL, '2025-08-10 18:49:54', '2025-11-11 17:08:21', 'admin'),
(179, 'adgad.test.one', 'sadgasd@test.com', 'agsadg6654656adg###%#@daga5DGDGd', 1, 'agsasgda', NULL, '01006429510', 'طب عام', 'available', NULL, '1990-10-02', 'male', 'patient', NULL, NULL, NULL, '2025-08-16 07:49:37', '2025-08-16 07:49:37', 'patient'),
(180, 'ahmad-test', 'ahmed@example.com', 'adgasdg54DD5&&^^dg', 1, 'Ahmad Ali Mohmoud', NULL, '01006429521', 'طب عام', 'available', NULL, '2000-10-06', 'male', 'patient', NULL, NULL, NULL, '2025-08-17 07:05:27', '2025-08-17 07:05:27', 'patient'),
(181, 'ahmed.ali', 'ahmed.ali@hospital.com', 'default_password_123', 1, 'د. أحمد محمد علي', NULL, '01012345678', 'طب عام', 'available', NULL, NULL, 'male', 'patient', NULL, NULL, NULL, '2025-08-17 07:19:43', '2025-08-17 07:19:43', 'doctor'),
(182, 'fatima.hassan', 'fatima.hassan@hospital.com', 'default_password_123', 1, 'د. فاطمة أحمد حسن', NULL, '01012345679', 'طب عام', 'available', NULL, NULL, 'male', 'patient', NULL, NULL, NULL, '2025-08-17 07:19:43', '2025-08-17 07:19:43', 'doctor'),
(183, 'mohamed.saeed', 'mohamed.saeed@hospital.com', 'default_password_123', 1, 'د. محمد سعيد أحمد', NULL, '01012345680', 'طب عام', 'available', NULL, NULL, 'male', 'patient', NULL, NULL, NULL, '2025-08-17 07:19:43', '2025-08-17 07:19:43', 'doctor'),
(184, 'sara.mahmoud', 'sara.mahmoud@hospital.com', 'default_password_123', 1, 'د. سارة محمود علي', NULL, '01012345681', 'طب عام', 'available', NULL, NULL, 'male', 'patient', NULL, NULL, NULL, '2025-08-17 07:19:43', '2025-08-17 07:19:43', 'doctor'),
(185, 'khaled.abdulrahman', 'khaled.abdulrahman@hospital.com', 'default_password_123', 1, 'د. خالد عبد الرحمن', NULL, '01012345682', 'طب عام', 'available', NULL, NULL, 'male', 'patient', NULL, NULL, NULL, '2025-08-17 07:19:43', '2025-08-17 07:19:43', 'doctor'),
(186, 'nora.ahmed', 'nora.ahmed@hospital.com', 'default_password_123', 1, 'د. نورا أحمد محمد', NULL, '01012345683', 'طب عام', 'available', NULL, NULL, 'male', 'patient', NULL, NULL, NULL, '2025-08-17 07:19:43', '2025-08-17 07:19:43', 'doctor'),
(187, 'omar.mohamed', 'omar.mohamed@hospital.com', 'default_password_123', 1, 'د. عمر محمد حسن', NULL, '01012345684', 'طب عام', 'available', NULL, NULL, 'male', 'patient', NULL, NULL, NULL, '2025-08-17 07:19:43', '2025-08-17 07:19:43', 'doctor'),
(188, 'layla.ahmed', 'layla.ahmed@hospital.com', 'default_password_123', 1, 'د. ليلى أحمد سعيد', NULL, '01012345685', 'طب عام', 'available', NULL, NULL, 'male', 'patient', NULL, NULL, NULL, '2025-08-17 07:19:43', '2025-08-17 07:19:43', 'doctor'),
(189, 'youssef.mohamed', 'youssef.mohamed@hospital.com', 'default_password_123', 1, 'د. يوسف محمد علي', NULL, '01012345686', 'طب عام', 'available', NULL, NULL, 'male', 'patient', NULL, NULL, NULL, '2025-08-17 07:19:43', '2025-08-17 07:19:43', 'doctor'),
(190, 'rana.ahmed', 'rana.ahmed@hospital.com', 'default_password_123', 1, 'د. رنا أحمد حسن', NULL, '01012345687', 'طب عام', 'available', NULL, NULL, 'male', 'patient', NULL, NULL, NULL, '2025-08-17 07:19:43', '2025-08-17 07:19:43', 'doctor'),
(206, 'Ahmad Abdalalh', 'testt@email.com', '$2y$10$A1i.N9xPIi/SjGSfnimWIu20TJeyaiSRYifEH9OUOnHs60/SqZAQ.', 1, 'Ahmad Tech', NULL, '01006429510', 'طب عام', 'available', 'uploads/profile_images/user_206_1762604639.jpg', NULL, 'male', 'patient', NULL, NULL, NULL, '2025-11-08 11:54:35', '2025-11-11 18:08:22', 'user'),
(221, 'dr_د._منى_عبدالله_إبراهيم', 'dr2@medical.com', '$2y$10$AlC/n3tpogwjwKOeAyU.ZuskwoL5O67qvexLMoyYNRJiL8ygYqIxm', 1, 'د. منى عبدالله إبراهيم', NULL, NULL, 'طب عام', 'available', NULL, NULL, 'male', 'doctor', NULL, NULL, NULL, '2025-11-08 12:16:26', '2025-11-08 12:16:26', 'user'),
(222, 'dr_د._خالد_محمود_حسن', 'dr3@medical.com', '$2y$10$1DM.Uu5N4BP8i1L9ZiXqYOY7QKpmmwzMJCS/HaA8x0F.GHQAGYapy', 1, 'د. خالد محمود حسن', NULL, NULL, 'طب عام', 'available', NULL, NULL, 'male', 'doctor', NULL, NULL, NULL, '2025-11-08 12:16:27', '2025-11-08 12:16:27', 'user'),
(223, 'dr_د._فاطمة_علي_أحمد', 'dr4@medical.com', '$2y$10$Web5NAsi7baXpKyOX2yHdeY6hP0Kr1JiTgg17pjLZPJ25ZtqAryhG', 1, 'د. فاطمة علي أحمد', NULL, NULL, 'طب عام', 'available', NULL, NULL, 'male', 'doctor', NULL, NULL, NULL, '2025-11-08 12:16:27', '2025-11-08 12:16:27', 'user'),
(224, 'dr_د._محمد_عبدالرحيم_خالد', 'dr5@medical.com', '$2y$10$LSWCV4JILdK.JragFlH6zuLHXYzbNXE8GKcmmT/XSXt4Va57k3VxW', 1, 'د. محمد عبدالرحيم خالد', NULL, NULL, 'طب عام', 'available', NULL, NULL, 'male', 'doctor', NULL, NULL, NULL, '2025-11-08 12:16:27', '2025-11-08 12:16:27', 'user'),
(225, 'dr_د._نادية_سالم_محمد', 'dr6@medical.com', '$2y$10$ktzroC/cgb3acDnVnsuEceMajb6JTxpW0Dhh1ccwoeOFtM38dF4Qu', 1, 'د. نادية سالم محمد', NULL, NULL, 'طب عام', 'available', NULL, NULL, 'male', 'doctor', NULL, NULL, NULL, '2025-11-08 12:16:27', '2025-11-08 12:16:27', 'user'),
(226, 'dr_د._عمر_حسن_علي', 'dr7@medical.com', '$2y$10$c6EnNk6PNW1TCDJ2BVgLUOhli58MiNhg96vfhuY4HWzPBYtvMtM..', 1, 'د. عمر حسن علي', NULL, NULL, 'طب عام', 'available', NULL, NULL, 'male', 'doctor', NULL, NULL, NULL, '2025-11-08 12:16:27', '2025-11-08 12:16:27', 'user'),
(227, 'dr_د._سارة_محمود_عبدالله', 'dr8@medical.com', '$2y$10$QYmAg4wL2A2sXuW0.znfqOL2RnBAR8iq4zHZcjost00gm9MGxxZHa', 1, 'د. سارة محمود عبدالله', NULL, NULL, 'طب عام', 'available', NULL, NULL, 'male', 'doctor', NULL, NULL, NULL, '2025-11-08 12:16:27', '2025-11-08 12:16:27', 'user'),
(228, 'dr_د._مصطفي_كامل_أحمد', 'dr9@medical.com', '$2y$10$GKoCx4rBnpJB6xRJYVdPKe7WNOwJbd6epBC.ZEfI/jK2dOqfsYQom', 1, 'د. مصطفي كامل أحمد', NULL, NULL, 'طب عام', 'available', NULL, NULL, 'male', 'doctor', NULL, NULL, NULL, '2025-11-08 12:16:27', '2025-11-08 12:16:27', 'user'),
(229, 'dr_د._هناء_يوسف_محمد', 'dr10@medical.com', '$2y$10$w9gpda3CnME1mq8mODFpNeu9yrL2cEGhWg6PUKTJu.m4cdKWGpu.y', 1, 'د. هناء يوسف محمد', NULL, NULL, 'طب عام', 'available', NULL, NULL, 'male', 'doctor', NULL, NULL, NULL, '2025-11-08 12:16:27', '2025-11-08 12:16:27', 'user'),
(230, 'dr_د._حسين_عبدالله_محمود', 'dr11@medical.com', '$2y$10$JCelzZV5Tdl9TrJBYV9FauYLsyOMvfAL5aOnHPcxlfkZL0WxMNOBq', 1, 'د. حسين عبدالله محمود', NULL, NULL, 'طب عام', 'available', NULL, NULL, 'male', 'doctor', NULL, NULL, NULL, '2025-11-08 12:16:27', '2025-11-08 12:16:27', 'user'),
(231, 'dr_د._ليلى_أحمد_سعيد', 'dr12@medical.com', '$2y$10$.2Su2YtvAKFGgbGD9X.kEueDjOx3SaAQhsepAOI2zyDLDrdospMPu', 1, 'د. ليلى أحمد سعيد', NULL, NULL, 'طب عام', 'available', NULL, NULL, 'male', 'doctor', NULL, NULL, NULL, '2025-11-08 12:16:27', '2025-11-08 12:16:27', 'user'),
(232, 'doctor_ahmed', 'dr.ahmed@medical.com', '$2y$10$eiWv24Y4oztsgBm47hmeS.LlxtTBwmyDxTieb9MxMtQSkmpLn5shm', 1, 'د. أحمد محمد السيد', NULL, NULL, 'طب عام', 'available', NULL, NULL, 'male', 'doctor', NULL, NULL, NULL, '2025-11-08 12:35:56', '2025-11-08 12:35:56', 'user'),
(233, 'admin', 'admin@medical.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'Administrator', NULL, '01000000000', 'طب عام', 'available', NULL, NULL, 'male', 'admin', NULL, NULL, NULL, '2025-11-11 17:08:21', '2025-11-11 17:08:21', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `working_hours`
--

CREATE TABLE `working_hours` (
  `id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `day_of_week` enum('sunday','monday','tuesday','wednesday','thursday','friday','saturday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure for view `appointment_info_view`
--
DROP TABLE IF EXISTS `appointment_info_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `appointment_info_view`  AS SELECT `a`.`id` AS `id`, `a`.`appointment_date` AS `appointment_date`, `a`.`appointment_time` AS `appointment_time`, `a`.`appointment_datetime` AS `appointment_datetime`, `a`.`status` AS `status`, `a`.`notes` AS `notes`, `a`.`created_at` AS `created_at`, `u`.`full_name` AS `patient_name`, `u`.`phone` AS `patient_phone`, `u`.`email` AS `patient_email`, `d`.`full_name` AS `doctor_name`, `d`.`phone` AS `doctor_phone`, `c`.`name` AS `clinic_name`, `h`.`name` AS `hospital_name`, `s`.`name` AS `specialty_name` FROM (((((`appointments` `a` join `users` `u` on(`a`.`user_id` = `u`.`id`)) join `doctors` `d` on(`a`.`doctor_id` = `d`.`id`)) join `clinics` `c` on(`a`.`clinic_id` = `c`.`id`)) join `hospitals` `h` on(`c`.`hospital_id` = `h`.`id`)) join `specialties` `s` on(`d`.`specialty_id` = `s`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `doctor_complete_profile`
--
DROP TABLE IF EXISTS `doctor_complete_profile`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `doctor_complete_profile`  AS SELECT `di`.`id` AS `doctor_info_id`, `d`.`full_name` AS `full_name`, `d`.`email` AS `email`, `d`.`phone` AS `phone`, `di`.`license_number` AS `license_number`, `di`.`national_id` AS `national_id`, `di`.`date_of_birth` AS `date_of_birth`, `di`.`gender` AS `gender`, `di`.`nationality` AS `nationality`, `di`.`address` AS `address`, `di`.`emergency_contact` AS `emergency_contact`, `di`.`emergency_phone` AS `emergency_phone`, `di`.`languages_spoken` AS `languages_spoken`, `di`.`consultation_fee` AS `consultation_fee`, `di`.`follow_up_fee` AS `follow_up_fee`, `di`.`emergency_fee` AS `emergency_fee`, `di`.`accepts_insurance` AS `accepts_insurance`, `di`.`education_details` AS `education_details`, `di`.`certifications` AS `certifications`, `di`.`awards` AS `awards`, `di`.`publications` AS `publications`, `di`.`research_interests` AS `research_interests`, `di`.`years_of_experience` AS `years_of_experience`, `di`.`special_interests` AS `special_interests`, `di`.`treatment_methods` AS `treatment_methods`, `di`.`success_rate` AS `success_rate`, `di`.`patient_satisfaction_rate` AS `patient_satisfaction_rate`, `di`.`total_patients_treated` AS `total_patients_treated`, `di`.`verified` AS `verified`, `di`.`verification_date` AS `verification_date`, `di`.`created_at` AS `created_at`, `di`.`updated_at` AS `updated_at`, avg(`dr`.`rating`) AS `average_rating`, count(`dr`.`id`) AS `total_reviews` FROM ((`doctor_info` `di` join `doctors` `d` on(`di`.`doctor_id` = `d`.`id`)) left join `doctor_reviews` `dr` on(`di`.`id` = `dr`.`doctor_info_id`)) GROUP BY `di`.`id` ;

-- --------------------------------------------------------

--
-- Structure for view `doctor_info_view`
--
DROP TABLE IF EXISTS `doctor_info_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `doctor_info_view`  AS SELECT `d`.`id` AS `id`, `d`.`full_name` AS `full_name`, `d`.`phone` AS `phone`, `d`.`email` AS `email`, `d`.`experience_years` AS `experience_years`, `d`.`rating` AS `rating`, `d`.`consultation_fee` AS `consultation_fee`, `d`.`bio` AS `bio`, `d`.`is_active` AS `is_active`, `s`.`name` AS `specialty_name`, `h`.`name` AS `hospital_name`, `c`.`name` AS `clinic_name`, `dep`.`name` AS `department_name` FROM ((((`doctors` `d` left join `specialties` `s` on(`d`.`specialty_id` = `s`.`id`)) left join `hospitals` `h` on(`d`.`hospital_id` = `h`.`id`)) left join `clinics` `c` on(`d`.`clinic_id` = `c`.`id`)) left join `departments` `dep` on(`d`.`department_id` = `dep`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `doctor_stats_view`
--
DROP TABLE IF EXISTS `doctor_stats_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `doctor_stats_view`  AS SELECT `d`.`id` AS `id`, `d`.`full_name` AS `full_name`, `d`.`rating` AS `rating`, `d`.`consultation_fee` AS `consultation_fee`, count(`a`.`id`) AS `total_appointments`, count(case when `a`.`status` = 'completed' then 1 end) AS `completed_appointments`, count(case when `a`.`status` = 'pending' then 1 end) AS `pending_appointments`, count(case when `a`.`status` = 'cancelled' then 1 end) AS `cancelled_appointments` FROM (`doctors` `d` left join `appointments` `a` on(`d`.`id` = `a`.`doctor_id`)) GROUP BY `d`.`id`, `d`.`full_name`, `d`.`rating`, `d`.`consultation_fee` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `clinic_id` (`clinic_id`),
  ADD KEY `idx_appointments_time` (`appointment_time`),
  ADD KEY `idx_appointments_status` (`status`),
  ADD KEY `idx_appointments_user` (`user_id`),
  ADD KEY `idx_appointments_doctor` (`doctor_id`),
  ADD KEY `idx_appointments_date` (`appointment_date`),
  ADD KEY `idx_appointments_patient` (`patient_id`);

--
-- Indexes for table `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_city` (`name`,`governorate`);

--
-- Indexes for table `clinics`
--
ALTER TABLE `clinics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_clinics_hospital` (`hospital_id`),
  ADD KEY `idx_clinics_specialty` (`specialty_id`),
  ADD KEY `idx_clinics_rating` (`rating`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_departments_hospital` (`hospital_id`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `clinic_id` (`clinic_id`),
  ADD KEY `idx_doctors_hospital` (`hospital_id`),
  ADD KEY `idx_doctors_active` (`is_active`),
  ADD KEY `idx_doctors_specialty` (`specialty_id`),
  ADD KEY `idx_doctors_rating` (`rating`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `doctor_availability`
--
ALTER TABLE `doctor_availability`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_doctor_day` (`doctor_id`,`day_of_week`);

--
-- Indexes for table `doctor_availability_exceptions`
--
ALTER TABLE `doctor_availability_exceptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_exceptions_doctor` (`doctor_info_id`),
  ADD KEY `idx_exceptions_date` (`exception_date`);

--
-- Indexes for table `doctor_info`
--
ALTER TABLE `doctor_info`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `license_number` (`license_number`),
  ADD KEY `idx_doctor_info_doctor_id` (`doctor_id`),
  ADD KEY `idx_doctor_info_license` (`license_number`),
  ADD KEY `idx_doctor_info_verified` (`verified`),
  ADD KEY `idx_doctor_info_experience` (`years_of_experience`),
  ADD KEY `idx_doctor_info_rating` (`patient_satisfaction_rate`);

--
-- Indexes for table `doctor_ratings`
--
ALTER TABLE `doctor_ratings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_doctor_user_rating` (`doctor_id`,`user_id`),
  ADD KEY `idx_doctor_id` (`doctor_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `doctor_reviews`
--
ALTER TABLE `doctor_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reviews_doctor` (`doctor_info_id`),
  ADD KEY `idx_reviews_patient` (`patient_id`),
  ADD KEY `idx_reviews_rating` (`rating`),
  ADD KEY `idx_reviews_date` (`created_at`);

--
-- Indexes for table `doctor_schedule`
--
ALTER TABLE `doctor_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_schedule_doctor` (`doctor_info_id`),
  ADD KEY `idx_schedule_day` (`day_of_week`),
  ADD KEY `idx_schedule_available` (`is_available`);

--
-- Indexes for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_doctor_schedules_doctor` (`doctor_id`);

--
-- Indexes for table `doctor_specialties`
--
ALTER TABLE `doctor_specialties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_specialties_doctor` (`doctor_info_id`),
  ADD KEY `idx_specialties_specialty` (`specialty_id`);

--
-- Indexes for table `doctor_verification`
--
ALTER TABLE `doctor_verification`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_verification_doctor` (`doctor_info_id`),
  ADD KEY `idx_verification_type` (`verification_type`),
  ADD KEY `idx_verification_status` (`verification_status`);

--
-- Indexes for table `hospitals`
--
ALTER TABLE `hospitals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hospitals_city` (`city_id`),
  ADD KEY `idx_hospitals_rating` (`rating`),
  ADD KEY `idx_hospitals_emergency` (`has_emergency`),
  ADD KEY `idx_hospitals_insurance` (`has_insurance`);

--
-- Indexes for table `push_notifications`
--
ALTER TABLE `push_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_push_notifications_user` (`user_id`),
  ADD KEY `idx_push_notifications_read` (`is_read`);

--
-- Indexes for table `reminders`
--
ALTER TABLE `reminders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `reminder_logs`
--
ALTER TABLE `reminder_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reminder_logs_user` (`user_id`),
  ADD KEY `idx_reminder_logs_appointment` (`appointment_id`);

--
-- Indexes for table `reminder_settings`
--
ALTER TABLE `reminder_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reminder_settings_user` (`user_id`);

--
-- Indexes for table `specialties`
--
ALTER TABLE `specialties`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `unique_email` (`email`),
  ADD KEY `idx_users_city` (`city_id`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_email` (`email`),
  ADD KEY `idx_users_username` (`username`);

--
-- Indexes for table `working_hours`
--
ALTER TABLE `working_hours`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_working_hours_doctor` (`doctor_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `cities`
--
ALTER TABLE `cities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `clinics`
--
ALTER TABLE `clinics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `doctor_availability`
--
ALTER TABLE `doctor_availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `doctor_availability_exceptions`
--
ALTER TABLE `doctor_availability_exceptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctor_info`
--
ALTER TABLE `doctor_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `doctor_ratings`
--
ALTER TABLE `doctor_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `doctor_reviews`
--
ALTER TABLE `doctor_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctor_schedule`
--
ALTER TABLE `doctor_schedule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `doctor_specialties`
--
ALTER TABLE `doctor_specialties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctor_verification`
--
ALTER TABLE `doctor_verification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hospitals`
--
ALTER TABLE `hospitals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `push_notifications`
--
ALTER TABLE `push_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `reminders`
--
ALTER TABLE `reminders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reminder_logs`
--
ALTER TABLE `reminder_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `reminder_settings`
--
ALTER TABLE `reminder_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `specialties`
--
ALTER TABLE `specialties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=235;

--
-- AUTO_INCREMENT for table `working_hours`
--
ALTER TABLE `working_hours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `clinics`
--
ALTER TABLE `clinics`
  ADD CONSTRAINT `clinics_ibfk_1` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `clinics_ibfk_2` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctors`
--
ALTER TABLE `doctors`
  ADD CONSTRAINT `doctors_ibfk_1` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `doctors_ibfk_2` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `doctors_ibfk_3` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `doctors_ibfk_4` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `doctors_ibfk_5` FOREIGN KEY (`hospital_id`) REFERENCES `hospitals` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `doctors_ibfk_6` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_doctor_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_doctors_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `doctor_availability`
--
ALTER TABLE `doctor_availability`
  ADD CONSTRAINT `doctor_availability_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_availability_exceptions`
--
ALTER TABLE `doctor_availability_exceptions`
  ADD CONSTRAINT `doctor_availability_exceptions_ibfk_1` FOREIGN KEY (`doctor_info_id`) REFERENCES `doctor_info` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_info`
--
ALTER TABLE `doctor_info`
  ADD CONSTRAINT `doctor_info_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_ratings`
--
ALTER TABLE `doctor_ratings`
  ADD CONSTRAINT `doctor_ratings_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `doctor_ratings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_reviews`
--
ALTER TABLE `doctor_reviews`
  ADD CONSTRAINT `doctor_reviews_ibfk_1` FOREIGN KEY (`doctor_info_id`) REFERENCES `doctor_info` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `doctor_reviews_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_schedule`
--
ALTER TABLE `doctor_schedule`
  ADD CONSTRAINT `doctor_schedule_ibfk_1` FOREIGN KEY (`doctor_info_id`) REFERENCES `doctor_info` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_schedules`
--
ALTER TABLE `doctor_schedules`
  ADD CONSTRAINT `doctor_schedules_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_specialties`
--
ALTER TABLE `doctor_specialties`
  ADD CONSTRAINT `doctor_specialties_ibfk_1` FOREIGN KEY (`doctor_info_id`) REFERENCES `doctor_info` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `doctor_specialties_ibfk_2` FOREIGN KEY (`specialty_id`) REFERENCES `specialties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_verification`
--
ALTER TABLE `doctor_verification`
  ADD CONSTRAINT `doctor_verification_ibfk_1` FOREIGN KEY (`doctor_info_id`) REFERENCES `doctor_info` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `hospitals`
--
ALTER TABLE `hospitals`
  ADD CONSTRAINT `hospitals_ibfk_1` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `hospitals_ibfk_2` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `push_notifications`
--
ALTER TABLE `push_notifications`
  ADD CONSTRAINT `push_notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reminders`
--
ALTER TABLE `reminders`
  ADD CONSTRAINT `reminders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reminders_ibfk_2` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reminder_logs`
--
ALTER TABLE `reminder_logs`
  ADD CONSTRAINT `reminder_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reminder_logs_ibfk_2` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reminder_settings`
--
ALTER TABLE `reminder_settings`
  ADD CONSTRAINT `reminder_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `working_hours`
--
ALTER TABLE `working_hours`
  ADD CONSTRAINT `working_hours_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
