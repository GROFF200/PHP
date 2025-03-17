////////////////////////////////////////////////////////////////////////////////////////////
//If a WP instance is using mysql and the Ultimate Member Login plugin, then this script
//will backup inactive accounts to a CSV file
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
$file = fopen($backupFile, 'w');
if (!$file) {
    die("Failed to open backup file for writing.");
}

// Write header row
fputcsv($file, ['User ID', 'Username', 'Email', 'Last Login']);

// Query users with `_um_last_login` inactive for more than a year
$query = "
    SELECT u.ID, u.user_login, u.user_email, um.meta_value AS last_login
    FROM portal_users u
    LEFT JOIN portal_usermeta um ON u.ID = um.user_id AND um.meta_key = '_um_last_login'
    WHERE um.meta_value IS NULL OR um.meta_value < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 2 YEAR))
";
$result = $mysqli->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Convert Unix timestamp to readable date
        $last_login = !empty($row['last_login']) ? date('Y-m-d H:i:s', $row['last_login']) : 'Never';

        // Write user data to CSV
        fputcsv($file, [$row['ID'], $row['user_login'], $row['user_email'], $last_login]);
    }
    echo "Backup completed. File saved to $backupFile<br>\n";
} else {
    echo "No inactive users found.<br>\n";
}

fclose($file);
$mysqli->close();

