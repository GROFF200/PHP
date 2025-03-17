<?php
/**
 * Plugin Name: Custom Authentication API
 * Description: Provides a REST API for handling authentication and 2FA in WordPress
ress.
 * Version: 1.0
 * Author: Aaron DeLong
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register the custom login endpoint
add_action('rest_api_init', function () {
    error_log("✅ Custom Auth API Routes are being registered.");

    register_rest_route('custom-auth/v1', '/login', [
        'methods'  => 'POST',
        'callback' => 'custom_auth_api_login',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('custom-auth/v1', '/verify-2fa', [
        'methods'  => 'POST',
        'callback' => 'custom_auth_api_verify_2fa',
        'permission_callback' => '__return_true',
    ]);

});

// Debug function to check registered REST routes
add_action('rest_api_init', function () {
    global $wp_rest_server;
    $routes = $wp_rest_server->get_routes();
    //error_log(print_r($routes, true)); // Log routes to WordPress debug.log
}, 99);


/**
 * Handles user login and bypasses 2FA entirely.
 */
function custom_auth_api_login($request) {
    error_log("🔹 custom_auth_api_login() called.");

    $params = $request->get_json_params();
    error_log("🔹 Received credentials: " . print_r($params, true));

    $username = sanitize_text_field($params['username'] ?? '');
    $password = $params['password'] ?? '';

    if (empty($username) || empty($password)) {
        error_log("❌ Missing username or password.");
        return new WP_Error('missing_credentials', 'Username and password are re
quired.', ['status' => 400]);
    }

    error_log("🔹 Attempting login for username: $username");

    // Manually fetch user and verify password
    $user = get_user_by('login', $username);
    if (!$user) {
        error_log("❌ User not found.");
        return new WP_Error('authentication_failed', 'Invalid username.', ['stat
us' => 401]);
    }

    // Verify password manually
    if (!wp_check_password($password, $user->user_pass, $user->ID)) {
        error_log("❌ Password check failed.");
        return new WP_Error('authentication_failed', 'Invalid password.', ['stat
us' => 401]);
    }

    error_log("✅ Password verified, setting auth cookie.");
    wp_set_auth_cookie($user->ID, true);

    return rest_ensure_response([
        'status' => 'success',
        'user_id' => $user->ID,
        'message' => 'Login successful.',
    ]);
}

/**
 * Handles 2FA verification and completes login.
 */
function custom_auth_api_verify_2fa($request) {
    error_log("🔹 custom_auth_api_verify_2fa() called.");

    $params = $request->get_json_params();
    $user_id = intval($params['user_id'] ?? 0);
    $twofa_code = sanitize_text_field($params['twofa_code'] ?? '');

    if (!$user_id || empty($twofa_code)) {
        error_log("❌ Missing user_id or 2FA code.");
        return new WP_Error('missing_data', 'User ID and 2FA code are required.'
, ['status' => 400]);
    }

    // Verify the 2FA code
    if (function_exists('wp_2fa_verify_user_code')) {
        $verified = wp_2fa_verify_user_code($user_id, $twofa_code);
        if ($verified) {
            error_log("✅ 2FA code verified for user ID: " . $user_id);
            wp_set_auth_cookie($user_id, true);
            return rest_ensure_response([
                'status' => 'success',
                'user_id' => $user_id,
                'message' => '2FA verification successful.',
            ]);
        }
    }

    error_log("❌ 2FA verification failed for user ID: " . $user_id);
    return new WP_Error('invalid_2fa', 'Invalid 2FA code.', ['status' => 401]);
}
