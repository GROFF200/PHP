////////////////////////////////////////////////////////////////////////////////////////////
//If a WP instance is using mysql and the Ultimate Member Login plugin, then this script
//will delete inactive accounts
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

// Query inactive users
$query = "
    SELECT u.ID
    FROM portal_users u
    LEFT JOIN portal_usermeta um ON u.ID = um.user_id AND um.meta_key = '_um_last_login'
    WHERE um.meta_value IS NULL OR um.meta_value < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 2 YEAR))
";
$result = $mysqli->query($query);

if ($result && $result->num_rows > 0) {
    echo "Deleting " . $result->num_rows . " inactive users.<br>\n";

    while ($row = $result->fetch_assoc()) {
        $user_id = $row['ID'];

        // Delete user meta data
        $delete_meta_query = $mysqli->prepare("DELETE FROM portal_usermeta WHERE user_id = ?");
        $delete_meta_query->bind_param('i', $user_id);
        $delete_meta_query->execute();
        $delete_meta_query->close();

        // Delete user account
        $delete_user_query = $mysqli->prepare("DELETE FROM portal_users WHERE ID = ?");
        $delete_user_query->bind_param('i', $user_id);
        $delete_user_query->execute();
        $delete_user_query->close();

        echo "Deleted User ID $user_id<br>\n";
    }

    echo "Inactive user deletion completed.<br>\n";
} else {
    echo "No inactive users found.<br>\n";
}

$mysqli->close();

