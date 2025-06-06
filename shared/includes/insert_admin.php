<?php
require_once 'db_connection.php';

$name = 'Goh Jun Boon';
$ic_number = '010203040506';
$contact_no = '0123456789';
$email = 'adminboon@mmu.edu.my';
$username = 'admin02';
$plainPassword = 'password123';
$password = password_hash($plainPassword, PASSWORD_DEFAULT);
$profile_pic = null;
$office_number = "A-101"; 

$query = "INSERT INTO admins (name, ic_number, contact_no, email, username, password, 
          profile_pic, office_number, created_at, last_login, deleted_at) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, NULL, NULL)";

$stmt = $conn->prepare($query);
$stmt->bind_param("ssssssss", $name, $ic_number, $contact_no, $email, $username, $password, $profile_pic, $office_number);

if ($stmt->execute()) {
    echo "Admin user created successfully!\n";
} else {
    echo "Error creating admin user: " . $stmt->error . "\n";
}

$stmt->close();
$conn->close();
?>
