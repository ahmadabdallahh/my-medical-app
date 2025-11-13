<?php
require_once 'config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

echo "<h2>Creating Fake Doctors Data</h2>";

// Sample doctors data with different ratings
$fake_doctors = [
    [
        'full_name' => 'د. أحمد محمد السيد',
        'specialty_id' => 1,
        'clinic_id' => 1,
        'hospital_id' => 1,
        'rating' => 4.9,
        'consultation_fee' => 300,
        'experience_years' => 15,
        'education' => 'بكالوريوس الطب والجراحة - جامعة القاهرة',
        'image' => 'assets/images/doctor-1.jpg'
    ],
    [
        'full_name' => 'د. منى عبدالله إبراهيم',
        'specialty_id' => 2,
        'clinic_id' => 2,
        'hospital_id' => 2,
        'rating' => 4.8,
        'consultation_fee' => 250,
        'experience_years' => 12,
        'education' => 'ماجستير أمراض النساء والتوليد - جامعة عين شمس',
        'image' => 'assets/images/doctor-2.jpg'
    ],
    [
        'full_name' => 'د. خالد محمود حسن',
        'specialty_id' => 3,
        'clinic_id' => 3,
        'hospital_id' => 1,
        'rating' => 4.7,
        'consultation_fee' => 400,
        'experience_years' => 20,
        'education' => 'دكتوراه في جراحة القلب - جامعة الأزهر',
        'image' => 'assets/images/doctor-3.jpg'
    ],
    [
        'full_name' => 'د. فاطمة علي أحمد',
        'specialty_id' => 4,
        'clinic_id' => 4,
        'hospital_id' => 3,
        'rating' => 4.6,
        'consultation_fee' => 200,
        'experience_years' => 8,
        'education' => 'بكالوريوس طب الأطفال - جامعة المنصورة',
        'image' => 'assets/images/doctor-4.jpg'
    ],
    [
        'full_name' => 'د. محمد عبدالرحيم خالد',
        'specialty_id' => 5,
        'clinic_id' => 5,
        'hospital_id' => 2,
        'rating' => 4.5,
        'consultation_fee' => 350,
        'experience_years' => 18,
        'education' => 'ماجستير جراحة العظام - جامعة الإسكندرية',
        'image' => 'assets/images/doctor-5.jpg'
    ],
    [
        'full_name' => 'د. نادية سالم محمد',
        'specialty_id' => 6,
        'clinic_id' => 6,
        'hospital_id' => 1,
        'rating' => 4.4,
        'consultation_fee' => 180,
        'experience_years' => 10,
        'education' => 'دكتوراه في الأمراض الجلدية - جامعة القاهرة',
        'image' => 'assets/images/doctor-6.jpg'
    ],
    [
        'full_name' => 'د. عمر حسن علي',
        'specialty_id' => 7,
        'clinic_id' => 7,
        'hospital_id' => 4,
        'rating' => 4.3,
        'consultation_fee' => 280,
        'experience_years' => 14,
        'education' => 'بكالوريوس طب الأسنان - جامعة طنطا',
        'image' => 'assets/images/doctor-7.jpg'
    ],
    [
        'full_name' => 'د. سارة محمود عبدالله',
        'specialty_id' => 8,
        'clinic_id' => 8,
        'hospital_id' => 3,
        'rating' => 4.2,
        'consultation_fee' => 220,
        'experience_years' => 6,
        'education' => 'ماجستير الأمراض العصبية - جامعة المنوفية',
        'image' => 'assets/images/doctor-8.jpg'
    ],
    [
        'full_name' => 'د. مصطفي كامل أحمد',
        'specialty_id' => 1,
        'clinic_id' => 9,
        'hospital_id' => 5,
        'rating' => 4.1,
        'consultation_fee' => 320,
        'experience_years' => 11,
        'education' => 'دكتوراه في الأمراض الباطنية - جامعة سوهاج',
        'image' => 'assets/images/doctor-9.jpg'
    ],
    [
        'full_name' => 'د. هناء يوسف محمد',
        'specialty_id' => 2,
        'clinic_id' => 10,
        'hospital_id' => 4,
        'rating' => 4.0,
        'consultation_fee' => 260,
        'experience_years' => 9,
        'education' => 'بكالوريوس الطب والجراحة - جامعة أسيوط',
        'image' => 'assets/images/doctor-10.jpg'
    ],
    [
        'full_name' => 'د. حسين عبدالله محمود',
        'specialty_id' => 3,
        'clinic_id' => 1,
        'hospital_id' => 2,
        'rating' => 3.9,
        'consultation_fee' => 380,
        'experience_years' => 16,
        'education' => 'ماجستير أمراض القلب - جامعة بنها',
        'image' => 'assets/images/doctor-11.jpg'
    ],
    [
        'full_name' => 'د. ليلى أحمد سعيد',
        'specialty_id' => 4,
        'clinic_id' => 2,
        'hospital_id' => 5,
        'rating' => 3.8,
        'consultation_fee' => 190,
        'experience_years' => 7,
        'education' => 'دكتوراه في طب الأطفال - جامعة الفيوم',
        'image' => 'assets/images/doctor-12.jpg'
    ]
];

try {
    // Create users for doctors first
    $conn->exec("DELETE FROM users WHERE role = 'doctor'");
    $users_sql = "INSERT INTO users (username, email, password, role, full_name, gender) VALUES (?, ?, ?, ?, ?, ?)";
    $users_stmt = $conn->prepare($users_sql);
    
    $user_ids = [];
    foreach ($fake_doctors as $index => $doctor) {
        $username = 'dr_' . strtolower(str_replace(' ', '_', $doctor['full_name']));
        $email = 'dr' . ($index + 1) . '@medical.com';
        $password = password_hash('doctor123', PASSWORD_DEFAULT);
        
        $users_stmt->execute([
            $username,
            $email,
            $password,
            'doctor',
            $doctor['full_name'],
            'male'
        ]);
        
        $user_ids[] = $conn->lastInsertId();
    }
    echo "<p style='color: green;'>✅ Created " . count($user_ids) . " doctor users</p>";
    
    // Create specialties if not exists
    $conn->exec("DELETE FROM specialties");
    $specialties_sql = "INSERT INTO specialties (id, name) VALUES (?, ?)";
    $specialties_stmt = $conn->prepare($specialties_sql);
    
    $specialties = [
        [1, 'طب عام'],
        [2, 'نساء وتوليد'],
        [3, 'قلب وأوعية دموية'],
        [4, 'أطفال'],
        [5, 'عظام'],
        [6, 'جلدية'],
        [7, 'أسنان'],
        [8, 'أمراض عصبية']
    ];
    
    foreach ($specialties as $spec) {
        $specialties_stmt->execute($spec);
    }
    echo "<p style='color: green;'>✅ Created specialties</p>";
    
    // Create clinics if not exists
    $conn->exec("DELETE FROM clinics");
    $clinics_sql = "INSERT INTO clinics (id, name, specialty_id, hospital_id, consultation_fee, description) VALUES (?, ?, ?, ?, ?, ?)";
    $clinics_stmt = $conn->prepare($clinics_sql);
    
    $clinics = [
        [1, 'عيادة الطب العام', 1, 1, 300, 'عيادة متخصصة في الطب العام'],
        [2, 'عيادة النساء والتوليد', 2, 2, 250, 'عيادة نساء وتوليد متكاملة'],
        [3, 'عيادة القلب', 3, 1, 400, 'عيادة متخصصة في أمراض القلب'],
        [4, 'عيادة الأطفال', 4, 3, 200, 'عيادة أطفال حديثة'],
        [5, 'عيادة العظام', 5, 2, 350, 'عيادة عظام وجراحة'],
        [6, 'عيادة الجلدية', 6, 1, 180, 'عيادة الأمراض الجلدية'],
        [7, 'عيادة الأسنان', 7, 4, 280, 'عيادة أسنان متكاملة'],
        [8, 'عيادة الأمراض العصبية', 8, 3, 220, 'عيادة متخصصة في الأمراض العصبية'],
        [9, 'عيادة الباطنية', 1, 5, 320, 'عيادة الأمراض الباطنية'],
        [10, 'عيادة النساء والتوليد الثانية', 2, 4, 260, 'عيادة نساء وتوليد حديثة']
    ];
    
    foreach ($clinics as $clinic) {
        $clinics_stmt->execute($clinic);
    }
    echo "<p style='color: green;'>✅ Created clinics</p>";
    
    // Check if doctors table needs user_id
    $stmt = $conn->prepare("DESCRIBE doctors");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $has_user_id = in_array('user_id', $columns);
    
    // Clear existing doctors
    $conn->exec("DELETE FROM doctors");
    echo "<p style='color: orange;'>⚠️ Cleared existing doctors data</p>";
    
    // Insert fake doctors
    if ($has_user_id) {
        $insert_sql = "INSERT INTO doctors (full_name, specialty_id, clinic_id, hospital_id, rating, consultation_fee, experience_years, education, image, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        echo "<p>📝 Using table with user_id column</p>";
    } else {
        $insert_sql = "INSERT INTO doctors (full_name, specialty_id, clinic_id, hospital_id, rating, consultation_fee, experience_years, education, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        echo "<p>📝 Using table without user_id column</p>";
    }
    
    $insert_stmt = $conn->prepare($insert_sql);
    
    $inserted_count = 0;
    foreach ($fake_doctors as $index => $doctor) {
        $params = [
            $doctor['full_name'],
            $doctor['specialty_id'],
            $doctor['clinic_id'],
            $doctor['hospital_id'],
            $doctor['rating'],
            $doctor['consultation_fee'],
            $doctor['experience_years'],
            $doctor['education'],
            $doctor['image']
        ];
        
        if ($has_user_id) {
            $params[] = $user_ids[$index]; // Use actual user_id
        }
        
        $insert_stmt->execute($params);
        $inserted_count++;
    }
    
    echo "<p style='color: green;'>✅ Successfully inserted $inserted_count fake doctors</p>";
    
    // Verify the data
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM doctors");
    $stmt->execute();
    $count = $stmt->fetch()['count'];
    echo "<p>📊 Total doctors in database: $count</p>";
    
    // Show top 5 doctors by rating
    $stmt = $conn->prepare("SELECT full_name, rating, specialty_id FROM doctors ORDER BY rating DESC LIMIT 5");
    $stmt->execute();
    $top_doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Top 5 Doctors by Rating:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Name</th><th>Rating</th><th>Specialty ID</th></tr>";
    
    foreach ($top_doctors as $doctor) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($doctor['full_name']) . "</td>";
        echo "<td>" . $doctor['rating'] . " ⭐</td>";
        echo "<td>" . $doctor['specialty_id'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<br><br>
<a href="search.php">Go to Search Page</a> | 
<a href="index.php">Go to Home</a>
