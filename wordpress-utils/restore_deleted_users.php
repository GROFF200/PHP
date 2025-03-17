////////////////////////////////////////////////////////////////////////////////////////////
//If a WP instance is using mysql and the Ultimate Member Login plugin, then this script
//will restore deleted accounts which were backed up to a CSV
////////////////////////////////////////////////////////////////////////////////////////////
<?php
// Database connection details
$host = 'localhost';
$username = '<username>';
$password = '<password>';
$database = '<wordpress DB>';

// Connect to the database
$mysqli = new mysqli($host, $username, $password, $database);
if ($mysqli->connect_error) {
    die("Database connection failed: " . $mysqli->connect_error);
}

// Define the backup file
$backupFile = __DIR__ . '/inactive_users_backup.csv';
$file = fopen($backupFile, 'r');
if (!$file) {
    die("Failed to open backup file.");
}

// Skip header row
fgetcsv($file);

while (($data = fgetcsv($file)) !== false) {
    $user_id = $data[0];
    $username = $data[1];
    $email = $data[2];
    $last_login = !empty($data[3]) ? strtotime($data[3]) : null; // Convert readable date back to Unix timestamp

    // Restore user to portal_users
    $user_query = $mysqli->prepare("
        INSERT INTO portal_users (ID, user_login, user_email)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE user_login = VALUES(user_login), user_email = VALUES(user_email)
    ");
    $user_query->bind_param('iss', $user_id, $username, $email);
    $user_query->execute();
    $user_query->close();

    // Restore last login to portal_usermeta
    if ($last_login) {
        $meta_query = $mysqli->prepare("
            INSERT INTO portal_usermeta (user_id, meta_key, meta_value)
            VALUES (?, '_um_last_login', ?)
            ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value)
        ");
        $meta_query->bind_param('is', $user_id, $last_login);
        $meta_query->execute();
        $meta_query->close();
    }

    echo "Restored User ID $user_id with last login: " . ($last_login ? date('Y-m-d H:i:s', $last_login) : 'Never') . "<br>\n";
}

fclose($file);
$mysqli->close();
echo "Restore process completed.<br>\n";

