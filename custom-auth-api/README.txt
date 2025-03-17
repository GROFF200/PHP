This is a simple WordPress plugin that exposes a REST service that can be used for logging in users. If you need an external site to validate against WP, this plugin can help you.

Please note that the login feature as implemented currently bypasses 2FA restrictions. This is because some 2FA modules hijack the login process and you cannot change the flow.

Also, please note that you need to enable REST in your WP instance before this plugin will expose the endpoint.

The endpoint to access the service should be:  /custom-auth/v1/login