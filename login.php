<?php
require_once 'config.php';

// Check if user is already logged in
if (is_logged_in()) {
    redirect('index.php');
}

// Define page title
$page_title = get_page_title('login');

// Extra head content for this page
$extra_head = '
<style>
    .auth-container {
        background-image: url("https://images.unsplash.com/photo-1607082349566-187342175e2f?ixlib=rb-1.2.1&auto=format&fit=crop&w=2000&q=80");
        background-size: cover;
        background-position: center;
    }
    
    .auth-form-container {
        backdrop-filter: blur(8px);
        background-color: rgba(255, 255, 255, 0.8);
    }
</style>
';

include_header($page_title, $extra_head);
?>

<!-- Main content -->
<main class="auth-container min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="auth-form-container max-w-md w-full space-y-8 p-10 rounded-xl shadow-lg">
        <div class="text-center">
            <h1 class="text-3xl font-extrabold text-gray-900 mb-6">Sign in to your account</h1>
            <p class="text-gray-600 mb-8">Welcome back! Please enter your credentials to continue.</p>
        </div>

        <div id="login-error" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 hidden" role="alert">
            <p class="font-medium">Error</p>
            <p id="login-error-message"></p>
        </div>

        <form id="login-form" class="mt-8 space-y-6">
            <input type="hidden" name="action" value="login">
            
            <div class="space-y-4">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <input id="username" name="username" type="text" required class="appearance-none block w-full px-3 py-3 pl-10 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" placeholder="Enter your username">
                    </div>
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input id="password" name="password" type="password" required class="appearance-none block w-full px-3 py-3 pl-10 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" placeholder="Enter your password">
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between mt-4">
                <div class="flex items-center">
                    <input id="remember-me" name="remember-me" type="checkbox" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                    <label for="remember-me" class="ml-2 block text-sm text-gray-900">
                        Remember me
                    </label>
                </div>

                <div class="text-sm">
                    <a href="#" id="forgot-password-btn" class="font-medium text-indigo-600 hover:text-indigo-500">
                        Forgot your password?
                    </a>
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" id="login-submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-sign-in-alt text-indigo-500 group-hover:text-indigo-400"></i>
                    </span>
                    Sign in
                </button>
            </div>
        </form>

        <div class="text-center mt-4">
            <p class="text-sm text-gray-600">
                Don't have an account? 
                <a href="register.php" class="font-medium text-indigo-600 hover:text-indigo-500">
                    Sign up
                </a>
            </p>
        </div>
    </div>
</main>

<!-- Forgot Password Modal -->
<div id="forgot-password-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
    <div class="fixed inset-0 bg-black bg-opacity-50" id="forgot-password-backdrop"></div>
    <div class="relative bg-white rounded-lg shadow-lg w-full max-w-md p-6 fade-in">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold text-gray-800">Reset Password</h3>
            <button id="close-forgot-password" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <div id="forgot-password-error" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 hidden" role="alert">
            <p class="font-medium">Error</p>
            <p id="forgot-password-error-message"></p>
        </div>
        
        <div id="forgot-password-success" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 hidden" role="alert">
            <p class="font-medium">Success</p>
            <p id="forgot-password-success-message"></p>
        </div>
        
        <div id="forgot-password-form-container">
            <p class="mb-4 text-gray-600">Enter your email address and we'll send you a link to reset your password.</p>
            
            <form id="forgot-password-form" class="space-y-4">
                <input type="hidden" name="action" value="forgot_password">
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input type="email" id="email" name="email" class="pl-10 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm py-3 border" placeholder="Enter your email" required>
                    </div>
                </div>
                
                <div class="mt-6">
                    <button type="submit" id="forgot-password-submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Send Reset Link
                    </button>
                </div>
            </form>
        </div>
        
        <div id="reset-password-form-container" class="hidden">
            <p class="mb-4 text-gray-600">Enter your new password below.</p>
            
            <form id="reset-password-form" class="space-y-4">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" id="reset-token" name="token">
                
                <div>
                    <label for="new-password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" id="new-password" name="password" class="pl-10 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm py-3 border" placeholder="Enter new password" required>
                    </div>
                </div>
                
                <div class="mt-4">
                    <label for="confirm-new-password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" id="confirm-new-password" name="confirm_password" class="pl-10 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm py-3 border" placeholder="Confirm new password" required>
                    </div>
                </div>
                
                <div class="mt-6">
                    <button type="submit" id="reset-password-submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
include_footer(false);
?>

<script>
$(document).ready(function() {
    // Login form submit
    $('#login-form').on('submit', function(e) {
        e.preventDefault();
        
        // Hide error message
        $('#login-error').addClass('hidden');
        
        // Disable button and show loading
        $('#login-submit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Signing in...');
        
        // Get form data
        const formData = {
            action: 'login',
            username: $('#username').val(),
            password: $('#password').val()
        };
        
        // Submit form via AJAX
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            dataType: 'json',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Show success message
                    showToast('success', response.message);
                    
                    // Redirect to home page
                    setTimeout(function() {
                        window.location.href = 'index.php';
                    }, 1000);
                } else {
                    // Show error message
                    $('#login-error-message').text(response.message);
                    $('#login-error').removeClass('hidden');
                    
                    // Re-enable button
                    $('#login-submit').prop('disabled', false).html('Sign in');
                }
            },
            error: function() {
                // Show error message
                $('#login-error-message').text('An error occurred. Please try again.');
                $('#login-error').removeClass('hidden');
                
                // Re-enable button
                $('#login-submit').prop('disabled', false).html('Sign in');
            }
        });
    });
    
    // Forgot password button
    $('#forgot-password-btn').on('click', function(e) {
        e.preventDefault();
        $('#forgot-password-modal').removeClass('hidden');
    });
    
    // Close forgot password modal
    $('#close-forgot-password, #forgot-password-backdrop').on('click', function() {
        $('#forgot-password-modal').addClass('hidden');
    });
    
    // Forgot password form submit
    $('#forgot-password-form').on('submit', function(e) {
        e.preventDefault();
        
        // Hide messages
        $('#forgot-password-error, #forgot-password-success').addClass('hidden');
        
        // Disable button and show loading
        $('#forgot-password-submit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Sending...');
        
        // Get form data
        const formData = {
            action: 'forgot_password',
            email: $('#email').val()
        };
        
        // Submit form via AJAX
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            dataType: 'json',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $('#forgot-password-success-message').text(response.message);
                    $('#forgot-password-success').removeClass('hidden');
                    
                    // For demo purposes, show reset form directly
                    // In a real app, you would send an email with the token
                    setTimeout(function() {
                        $('#forgot-password-form-container').addClass('hidden');
                        $('#reset-password-form-container').removeClass('hidden');
                        $('#reset-token').val(response.token);
                    }, 2000);
                } else {
                    // Show error message
                    $('#forgot-password-error-message').text(response.message);
                    $('#forgot-password-error').removeClass('hidden');
                    
                    // Re-enable button
                    $('#forgot-password-submit').prop('disabled', false).html('Send Reset Link');
                }
            },
            error: function() {
                // Show error message
                $('#forgot-password-error-message').text('An error occurred. Please try again.');
                $('#forgot-password-error').removeClass('hidden');
                
                // Re-enable button
                $('#forgot-password-submit').prop('disabled', false).html('Send Reset Link');
            }
        });
    });
    
    // Reset password form submit
    $('#reset-password-form').on('submit', function(e) {
        e.preventDefault();
        
        // Hide messages
        $('#forgot-password-error, #forgot-password-success').addClass('hidden');
        
        // Validate passwords match
        const password = $('#new-password').val();
        const confirmPassword = $('#confirm-new-password').val();
        
        if (password !== confirmPassword) {
            $('#forgot-password-error-message').text('Passwords do not match.');
            $('#forgot-password-error').removeClass('hidden');
            return;
        }
        
        // Disable button and show loading
        $('#reset-password-submit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Resetting...');
        
        // Get form data
        const formData = {
            action: 'reset_password',
            token: $('#reset-token').val(),
            password: password,
            confirm_password: confirmPassword
        };
        
        // Submit form via AJAX
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            dataType: 'json',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $('#forgot-password-success-message').text(response.message);
                    $('#forgot-password-success').removeClass('hidden');
                    
                    // Close modal after 2 seconds
                    setTimeout(function() {
                        $('#forgot-password-modal').addClass('hidden');
                        
                        // Reset form
                        $('#forgot-password-form-container').removeClass('hidden');
                        $('#reset-password-form-container').addClass('hidden');
                        $('#forgot-password-form')[0].reset();
                        $('#reset-password-form')[0].reset();
                    }, 2000);
                } else {
                    // Show error message
                    $('#forgot-password-error-message').text(response.message);
                    $('#forgot-password-error').removeClass('hidden');
                    
                    // Re-enable button
                    $('#reset-password-submit').prop('disabled', false).html('Reset Password');
                }
            },
            error: function() {
                // Show error message
                $('#forgot-password-error-message').text('An error occurred. Please try again.');
                $('#forgot-password-error').removeClass('hidden');
                
                // Re-enable button
                $('#reset-password-submit').prop('disabled', false).html('Reset Password');
            }
        });
    });
    
    // Show toast notification
    function showToast(type, message) {
        const toast = $('#toast');
        const toastMessage = $('#toast-message');
        const toastIcon = $('#toast-icon');
        
        if (type === 'success') {
            toastIcon.html('<i class="fas fa-check-circle text-green-500 text-xl"></i>');
        } else if (type === 'error') {
            toastIcon.html('<i class="fas fa-exclamation-circle text-red-500 text-xl"></i>');
        } else if (type === 'info') {
            toastIcon.html('<i class="fas fa-info-circle text-blue-500 text-xl"></i>');
        }
        
        toastMessage.text(message);
        toast.removeClass('hidden');
        
        // Hide after 3 seconds
        setTimeout(function() {
            toast.addClass('hidden');
        }, 3000);
    }
});
</script>

<!-- Toast Notification -->
<div id="toast" class="toast hidden">
    <div class="bg-white rounded-lg shadow-lg p-4 max-w-md">
        <div class="flex items-center">
            <div id="toast-icon" class="flex-shrink-0 mr-3">
                <i class="fas fa-check-circle text-green-500 text-xl"></i>
            </div>
            <div class="flex-1">
                <p id="toast-message" class="text-sm font-medium text-gray-900"></p>
            </div>
            <button id="close-toast" class="ml-4 text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
</div>