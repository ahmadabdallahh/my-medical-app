<?php
require_once 'config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

echo "<h2>Creating Doctor User</h2>";

// Doctor user data
$doctor_user = [
    'username' => 'doctor_ahmed',
    'email' => 'dr.ahmed@medical.com',
    'password' => 'doctor123',
    'full_name' => 'د. أحمد محمد السيد',
    'gender' => 'male',
    'role' => 'doctor'
];

try {
    // Check if user already exists
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $check_stmt->execute([$doctor_user['username'], $doctor_user['email']]);
    
    if ($check_stmt->rowCount() > 0) {
        echo "<p style='color: orange;'>⚠️ Doctor user already exists</p>";
    } else {
        // Insert doctor user
        $hashed_password = password_hash($doctor_user['password'], PASSWORD_DEFAULT);
        
        $insert_stmt = $conn->prepare("
            INSERT INTO users (username, email, password, full_name, gender, role) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $insert_stmt->execute([
            $doctor_user['username'],
            $doctor_user['email'],
            $hashed_password,
            $doctor_user['full_name'],
            $doctor_user['gender'],
            $doctor_user['role']
        ]);
        
        $user_id = $conn->lastInsertId();
        
        echo "<p style='color: green;'>✅ Doctor user created successfully</p>";
        echo "<p><strong>User ID:</strong> " . $user_id . "</p>";
        echo "<p><strong>Username:</strong> " . $doctor_user['username'] . "</p>";
        echo "<p><strong>Email:</strong> " . $doctor_user['email'] . "</p>";
        echo "<p><strong>Password:</strong> " . $doctor_user['password'] . "</p>";
        echo "<p><strong>Full Name:</strong> " . $doctor_user['full_name'] . "</p>";
        echo "<p><strong>Role:</strong> " . $doctor_user['role'] . "</p>";
        
        // Create doctor record
        $doctor_data = [
            'user_id' => $user_id,
            'full_name' => $doctor_user['full_name'],
            'specialty_id' => 1, // General Medicine
            'clinic_id' => 1,
            'hospital_id' => 1,
            'rating' => 4.8,
            'consultation_fee' => 300,
            'experience_years' => 15,
            'education' => 'بكالوريوس الطب والجراحة - جامعة القاهرة',
            'phone' => '01234567890',
            'email' => $doctor_user['email']
        ];
        
        $doctor_stmt = $conn->prepare("
            INSERT INTO doctors (user_id, full_name, specialty_id, clinic_id, hospital_id, rating, consultation_fee, experience_years, education, phone, email) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $doctor_stmt->execute([
            $doctor_data['user_id'],
            $doctor_data['full_name'],
            $doctor_data['specialty_id'],
            $doctor_data['clinic_id'],
            $doctor_data['hospital_id'],
            $doctor_data['rating'],
            $doctor_data['consultation_fee'],
            $doctor_data['experience_years'],
            $doctor_data['education'],
            $doctor_data['phone'],
            $doctor_data['email']
        ]);
        
        $doctor_id = $conn->lastInsertId();
        
        echo "<p style='color: green;'>✅ Doctor record created successfully</p>";
        echo "<p><strong>Doctor ID:</strong> " . $doctor_id . "</p>";
        
        // Verify the creation
        $verify_stmt = $conn->prepare("
            SELECT u.*, d.id as doctor_id, d.specialty_id 
            FROM users u 
            LEFT JOIN doctors d ON u.id = d.user_id 
            WHERE u.id = ?
        ");
        $verify_stmt->execute([$user_id]);
        $result = $verify_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo "<h3>✅ Verification Successful:</h3>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            echo "<tr><td>User ID</td><td>" . $result['id'] . "</td></tr>";
            echo "<tr><td>Username</td><td>" . $result['username'] . "</td></tr>";
            echo "<tr><td>Email</td><td>" . $result['email'] . "</td></tr>";
            echo "<tr><td>Full Name</td><td>" . $result['full_name'] . "</td></tr>";
            echo "<tr><td>Role</td><td>" . $result['role'] . "</td></tr>";
            echo "<tr><td>Doctor ID</td><td>" . $result['doctor_id'] . "</td></tr>";
            echo "</table>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<br><br>";
echo "<h3>Next Steps:</h3>";
echo "<p>1. Go to login page: <a href='/App-Demo/login.php'>Login</a></p>";
echo "<p>2. Use these credentials:</p>";
echo "<ul>";
echo "<li><strong>Username:</strong> " . $doctor_user['username'] . "</li>";
echo "<li><strong>Password:</strong> " . $doctor_user['password'] . "</li>";
echo "</ul>";
echo "<p>3. You should be redirected to: <a href='/App-Demo/doctor/index.php'>Doctor Dashboard</a></p>";

echo "<br><br>";
echo "<a href='login.php'>Go to Login</a> | ";
echo "<a href='index.php'>Go to Home</a>";
?>
