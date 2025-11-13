<?php
require_once 'config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

echo "<h2>Creating doctor_availability Table</h2>";

try {
    // Create doctor_availability table
    $sql = "
    CREATE TABLE IF NOT EXISTS doctor_availability (
        id INT AUTO_INCREMENT PRIMARY KEY,
        doctor_id INT NOT NULL,
        day_of_week VARCHAR(20) NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        slot_duration INT NOT NULL DEFAULT 30,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_doctor_day (doctor_id, day_of_week)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $conn->exec($sql);
    echo "<p style='color: green;'>✅ doctor_availability table created successfully</p>";
    
    // Show table structure
    $stmt = $conn->prepare("DESCRIBE doctor_availability");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Table Structure:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . $col['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Insert sample availability for doctors
    $stmt = $conn->prepare("SELECT id FROM users WHERE role = 'doctor' LIMIT 5");
    $stmt->execute();
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($doctors)) {
        echo "<h3>Adding Sample Availability:</h3>";
        
        foreach ($doctors as $doctor) {
            $doctor_id = $doctor['id'];
            
            // Clear existing availability
            $conn->prepare("DELETE FROM doctor_availability WHERE doctor_id = ?")->execute([$doctor_id]);
            
            // Add working days (Saturday to Wednesday)
            $working_days = [
                'saturday' => ['09:00', '17:00'],
                'sunday' => ['09:00', '17:00'],
                'monday' => ['09:00', '17:00'],
                'tuesday' => ['09:00', '17:00'],
                'wednesday' => ['09:00', '17:00']
            ];
            
            foreach ($working_days as $day => $times) {
                $stmt = $conn->prepare("
                    INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time, slot_duration) 
                    VALUES (?, ?, ?, ?, 30)
                ");
                $stmt->execute([$doctor_id, $day, $times[0], $times[1]]);
            }
            
            echo "<p style='color: green;'>✅ Added availability for Doctor ID: " . $doctor_id . "</p>";
        }
    }
    
    // Test the table
    echo "<h3>Testing the Table:</h3>";
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM doctor_availability");
    $stmt->execute();
    $count = $stmt->fetch()['count'];
    echo "<p>Total availability records: " . $count . "</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<br><br>";
echo "<h3>Next Steps:</h3>";
echo "<p>1. Go to doctor availability page: <a href='/App-Demo/doctor/availability.php'>Availability Management</a></p>";
echo "<p>2. Login as a doctor to manage your schedule</p>";

echo "<br><br>";
echo "<a href='/App-Demo/login.php'>Go to Login</a> | ";
echo "<a href='/App-Demo/index.php'>Go to Home</a>";
?>
