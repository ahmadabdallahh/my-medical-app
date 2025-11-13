<?php
require_once 'config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

echo "<h2>Adding Test Completed Appointments</h2>";

try {
    // Get some users and doctors for testing
    $users_stmt = $conn->prepare("SELECT id, full_name FROM users WHERE role = 'patient' LIMIT 3");
    $users_stmt->execute();
    $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $doctors_stmt = $conn->prepare("SELECT id, full_name FROM doctors LIMIT 3");
    $doctors_stmt->execute();
    $doctors = $doctors_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get clinics for appointments
    $clinics_stmt = $conn->prepare("SELECT id, name FROM clinics LIMIT 5");
    $clinics_stmt->execute();
    $clinics = $clinics_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($users) && !empty($doctors) && !empty($clinics)) {
        // Clear existing test appointments
        $conn->exec("DELETE FROM appointments WHERE notes LIKE 'test_appointment_%'");
        
        // Add completed appointments for testing ratings
        $test_appointments = [];
        foreach ($doctors as $doctor) {
            foreach ($users as $user) {
                $clinic = $clinics[array_rand($clinics)]; // Random clinic
                $test_appointments[] = [
                    'user_id' => $user['id'],
                    'doctor_id' => $doctor['id'],
                    'clinic_id' => $clinic['id'],
                    'appointment_date' => date('Y-m-d', strtotime('-' . rand(1, 30) . ' days')),
                    'appointment_time' => sprintf('%02d:%02d:00', rand(9, 17), rand(0, 59)),
                    'status' => 'completed',
                    'notes' => 'test_appointment_' . $user['id'] . '_' . $doctor['id']
                ];
            }
        }
        
        foreach ($test_appointments as $index => $appointment) {
            try {
                $stmt = $conn->prepare("
                    INSERT INTO appointments (user_id, doctor_id, clinic_id, appointment_date, appointment_time, status, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $appointment['user_id'],
                    $appointment['doctor_id'],
                    $appointment['clinic_id'],
                    $appointment['appointment_date'],
                    $appointment['appointment_time'],
                    $appointment['status'],
                    $appointment['notes']
                ]);
                echo "<p style='color: green;'>✅ Added appointment " . ($index + 1) . "</p>";
            } catch (PDOException $e) {
                echo "<p style='color: red;'>❌ Error adding appointment " . ($index + 1) . ": " . $e->getMessage() . "</p>";
                echo "<p style='color: orange;'>Data: user_id={$appointment['user_id']}, doctor_id={$appointment['doctor_id']}, clinic_id={$appointment['clinic_id']}</p>";
                break;
            }
        }
        
        echo "<p style='color: green;'>✅ Added " . count($test_appointments) . " completed appointments for testing</p>";
        
        // Show the appointments
        $stmt = $conn->prepare("
            SELECT a.*, u1.full_name as patient_name, d.full_name as doctor_name, c.name as clinic_name
            FROM appointments a
            LEFT JOIN users u1 ON a.user_id = u1.id
            LEFT JOIN doctors d ON a.doctor_id = d.id
            LEFT JOIN clinics c ON a.clinic_id = c.id
            WHERE a.status = 'completed' AND a.notes LIKE 'test_appointment_%'
            ORDER BY a.appointment_date DESC
            LIMIT 10
        ");
        $stmt->execute();
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Test Appointments:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Patient</th><th>Doctor</th><th>Clinic</th><th>Date</th><th>Time</th><th>Status</th></tr>";
        
        foreach ($appointments as $apt) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($apt['patient_name']) . "</td>";
            echo "<td>" . htmlspecialchars($apt['doctor_name']) . "</td>";
            echo "<td>" . htmlspecialchars($apt['clinic_name']) . "</td>";
            echo "<td>" . $apt['appointment_date'] . "</td>";
            echo "<td>" . $apt['appointment_time'] . "</td>";
            echo "<td style='color: green;'>" . $apt['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>Now you can:</h3>";
        echo "<p>1. Login as a patient who has completed appointments</p>";
        echo "<p>2. Go to doctor profile page and test the rating system</p>";
        echo "<p>3. Example: <a href='/App-Demo/doctor_profile.php?id=220'>Doctor Profile</a></p>";
        
    } else {
        echo "<p style='color: red;'>❌ No users, doctors, or clinics found</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<br><br>";
echo "<a href='/App-Demo/login.php'>Go to Login</a> | ";
echo "<a href='/App-Demo/index.php'>Go to Home</a>";
?>
