<?php
require_once '../shared/includes/db_connection.php';

$name = 'Ong Jia Yu';
$ic_number = '020203040506';
$contact_no = '0183456789';
$email = 'adminyu@mmu.edu.my';
$username = 'admin03';
$plainPassword = 'password123';
$password = password_hash($plainPassword, PASSWORD_DEFAULT);
$profile_pic = null;
$office_number = "A-102"; 

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
