php
// Connect to the database
require_once 'shared/includes/db_connection.php';

// Read the SQL file
$sql = file_get_contents('sample_complaints.sql');

// Execute the SQL statements
if ($conn->multi_query($sql)) {
    echo "Sample complaints data added successfully.";
} else {
    echo "Error adding sample data: " . $conn->error;
}
?>
