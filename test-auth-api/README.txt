This is a utility that can help with testing the custom auth API plugin, custom-auth-api.

You will need to modify the URLs to match your environment. Otherwise, it will validate if login credentials are correct or not.
Although there is code to handle 2FA, in practice it isn't very useful because 2FA modules hijack the login flow. But I left the code
as it's helpful for showing how it could be handled, in theory.