<?php
require_once 'config.php';

// Check if user is already logged in
if (is_logged_in()) {
    redirect('index.php');
}

// Define page title
$page_title = get_page_title('register');

// Extra head content for this page
$extra_head = '
<style>
    .auth-container {
        background-image: url("https://images.unsplash.com/photo-1573164713988-8665fc963095?ixlib=rb-1.2.1&auto=format&fit=crop&w=2000&q=80");
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
            <h1 class="text-3xl font-extrabold text-gray-900 mb-6">Create an account</h1>
            <p class="text-gray-600 mb-8">Join us today and start shopping!</p>
        </div>

        <div id="register-error" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 hidden" role="alert">
            <p class="font-medium">Error</p>
            <p id="register-error-message"></p>
        </div>

        <div id="register-success" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 hidden" role="alert">
            <p class="font-medium">Success!</p>
            <p id="register-success-message"></p>
        </div>

        <form id="register-form" class="mt-8 space-y-6">
            <input type="hidden" name="action" value="register">
            
            <div class="space-y-4">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <input id="username" name="username" type="text" required class="appearance-none block w-full px-3 py-3 pl-10 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" placeholder="Choose a username">
                    </div>
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input id="email" name="email" type="email" required class="appearance-none block w-full px-3 py-3 pl-10 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" placeholder="Enter your email address">
                    </div>
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input id="password" name="password" type="password" required class="appearance-none block w-full px-3 py-3 pl-10 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" placeholder="Choose a password">
                    </div>
                </div>
                
                <div>
                    <label for="confirm-password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input id="confirm-password" name="confirm_password" type="password" required class="appearance-none block w-full px-3 py-3 pl-10 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" placeholder="Confirm your password">
                    </div>
                </div>
            </div>

            <div class="flex items-center mt-4">
                <input id="terms" name="terms" type="checkbox" required class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                <label for="terms" class="ml-2 block text-sm text-gray-900">
                    I agree to the <a href="#" class="text-indigo-600 hover:text-indigo-500">Terms and Conditions</a> and <a href="#" class="text-indigo-600 hover:text-indigo-500">Privacy Policy</a>
                </label>
            </div>

            <div class="mt-6">
                <button type="submit" id="register-submit" class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-user-plus text-indigo-500 group-hover:text-indigo-400"></i>
                    </span>
                    Create Account
                </button>
            </div>
        </form>

        <div class="text-center mt-4">
            <p class="text-sm text-gray-600">
                Already have an account? 
                <a href="login.php" class="font-medium text-indigo-600 hover:text-indigo-500">
                    Sign in
                </a>
            </p>
        </div>
    </div>
</main>

<?php
include_footer(false);
?>

<script>
$(document).ready(function() {
    // Register form submit
    $('#register-form').on('submit', function(e) {
        e.preventDefault();
        
        // Hide messages
        $('#register-error, #register-success').addClass('hidden');
        
        // Validate passwords match
        const password = $('#password').val();
        const confirmPassword = $('#confirm-password').val();
        
        if (password !== confirmPassword) {
            $('#register-error-message').text('Passwords do not match.');
            $('#register-error').removeClass('hidden');
            return;
        }
        
        // Validate password length
        if (password.length < 6) {
            $('#register-error-message').text('Password must be at least 6 characters long.');
            $('#register-error').removeClass('hidden');
            return;
        }
        
        // Validate email
        const email = $('#email').val();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            $('#register-error-message').text('Please enter a valid email address.');
            $('#register-error').removeClass('hidden');
            return;
        }
        
        // Check terms agreement
        if (!$('#terms').is(':checked')) {
            $('#register-error-message').text('You must agree to the Terms and Conditions.');
            $('#register-error').removeClass('hidden');
            return;
        }
        
        // Disable button and show loading
        $('#register-submit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Creating account...');
        
        // Get form data
        const formData = {
            action: 'register',
            username: $('#username').val(),
            email: email,
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
                    $('#register-success-message').text(response.message);
                    $('#register-success').removeClass('hidden');
                    
                    // Reset form
                    $('#register-form')[0].reset();
                    
                    // Re-enable button
                    $('#register-submit').prop('disabled', false).html('<span class="absolute left-0 inset-y-0 flex items-center pl-3"><i class="fas fa-user-plus text-indigo-500 group-hover:text-indigo-400"></i></span> Create Account');
                    
                    // Redirect to login page after 2 seconds
                    setTimeout(function() {
                        window.location.href = 'login.php';
                    }, 2000);
                } else {
                    // Show error message
                    $('#register-error-message').text(response.message);
                    $('#register-error').removeClass('hidden');
                    
                    // Re-enable button
                    $('#register-submit').prop('disabled', false).html('<span class="absolute left-0 inset-y-0 flex items-center pl-3"><i class="fas fa-user-plus text-indigo-500 group-hover:text-indigo-400"></i></span> Create Account');
                }
            },
            error: function() {
                // Show error message
                $('#register-error-message').text('An error occurred. Please try again.');
                $('#register-error').removeClass('hidden');
                
                // Re-enable button
                $('#register-submit').prop('disabled', false).html('<span class="absolute left-0 inset-y-0 flex items-center pl-3"><i class="fas fa-user-plus text-indigo-500 group-hover:text-indigo-400"></i></span> Create Account');
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
    
    // Close toast notification
    $('#close-toast').on('click', function() {
        $('#toast').addClass('hidden');
    });
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