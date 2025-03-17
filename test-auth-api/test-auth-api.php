<?php
// Ensure error reporting is enabled
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define API endpoints
$login_url = "https://<WP URL>/wp-json/custom-auth/v1/login";
$verify_2fa_url = "https://<WP URL>/wp-json/custom-auth/v1/verify-
2fa";


// Hardcoded test credentials
$username = "adelong";
$password = 'zY8cg~hYDi$FF2,';

// Function to make an API request
function api_request($url, $data) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    if (strpos($content_type, 'text/html') !== false) {
        return [$http_status, $response]; // This is the 2FA page HTML
    }

    return [$http_status, json_decode($response, true)];
}

list($status, $login_response) = api_request($login_url, [
    "username" => $username,
    "password" => $password
]);

if ($status !== 200) {
    echo "❌ Login failed with HTTP status $status.\n";
    print_r($login_response);
    exit;
}

// If the response is HTML, display it
if (is_string($login_response) && strpos($login_response, '<html') !== false) {
    echo "🔐 2FA is required. Displaying the 2FA page:\n";
    echo $login_response; // This should render the WP 2FA form
    exit;
}

// If login was successful
if (isset($login_response["status"]) && $login_response["status"] === "success")
 {
    echo "✅ Login successful. User authenticated.\n";
}



if (!$login_response) {
    echo "❌ API response could not be decoded. Check the raw response below:\n"
;
    list($status, $raw_response) = api_request($login_url, [
        "username" => $username,
        "password" => $password
    ]);
    echo "🔹 HTTP Status: $status\n";
    echo "🔹 Raw API Response:\n";
    var_dump($raw_response); // Debug the raw response
    exit;
}

// If 2FA is required
if ($login_response["status"] === "2fa_required") {
    echo "🔐 2FA is required for user '{$username}'.\n";
    echo "Enter the 2FA code sent via SMS or Authenticator: ";

    if (php_sapi_name() === "cli") {
        $twofa_code = trim(fgets(STDIN));
    } else {
        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["twofa_code"])
) {
            $twofa_code = trim($_POST["twofa_code"]);
        } else {
            echo "<form method='POST'>
                    <input type='text' name='twofa_code' placeholder='Enter 2FA
Code' required>
                    <button type='submit'>Verify</button>
                  </form>";
            exit;
        }
    }

    // Step 2: Submit 2FA code
    list($status, $verify_response) = api_request($verify_2fa_url, [
        "user_id" => $login_response["user_id"],
        "twofa_code" => $twofa_code
    ]);

    if ($status === 200 && $verify_response["status"] === "success") {
        echo "✅ 2FA verification succeeded. User authenticated.\n";
    } else {
        die("❌ 2FA verification failed: " . ($verify_response["message"] ?? "Un
known error") . "\n");
    }
}

?>
