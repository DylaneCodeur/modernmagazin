<?php
// Database connection configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "e_commerce_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set
$conn->set_charset("utf8mb4");

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Site configuration
$config = [
    'site_name' => 'ModernShop',
    'site_url' => 'http://localhost/modernshop',
    'currency' => '$',
    'items_per_page' => 8,
    'version' => '1.0.0',
];

// Helper functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

function redirect($location) {
    header("Location: $location");
    exit;
}

function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function check_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] === $token;
}

// Flash messages
function set_message($message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function display_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'];
        
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        
        return "<div class='alert alert-$type'>$message</div>";
    }
    return '';
}

// Cart functions
function get_cart_items() {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    return $_SESSION['cart'];
}

function add_to_cart($product_id, $quantity = 1) {
    $product_id = (int)$product_id;
    $quantity = (int)$quantity;
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }
    
    return true;
}

function update_cart_quantity($product_id, $quantity) {
    $product_id = (int)$product_id;
    $quantity = (int)$quantity;
    
    if (!isset($_SESSION['cart'])) {
        return false;
    }
    
    if ($quantity <= 0) {
        remove_from_cart($product_id);
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }
    
    return true;
}

function remove_from_cart($product_id) {
    $product_id = (int)$product_id;
    
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        return true;
    }
    
    return false;
}

function get_cart_total() {
    global $conn;
    
    $total = 0;
    $cart = get_cart_items();
    
    if (empty($cart)) {
        return $total;
    }
    
    $product_ids = array_keys($cart);
    $ids_str = implode(',', $product_ids);
    
    $sql = "SELECT id, price, discount_percent FROM products WHERE id IN ($ids_str)";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $price = $row['price'];
            if ($row['discount_percent'] > 0) {
                $price = $price * (1 - $row['discount_percent'] / 100);
            }
            $total += $price * $cart[$row['id']];
        }
    }
    
    return $total;
}

function get_cart_count() {
    $cart = get_cart_items();
    return array_sum($cart);
}

// Favorites functions
function get_favorites() {
    if (!is_logged_in() || !isset($_SESSION['favorites'])) {
        $_SESSION['favorites'] = [];
    }
    return $_SESSION['favorites'];
}

function add_to_favorites($product_id) {
    if (!is_logged_in()) {
        return false;
    }
    
    $product_id = (int)$product_id;
    
    if (!isset($_SESSION['favorites'])) {
        $_SESSION['favorites'] = [];
    }
    
    if (!in_array($product_id, $_SESSION['favorites'])) {
        $_SESSION['favorites'][] = $product_id;
        
        // Update database
        global $conn;
        $user_id = $_SESSION['user_id'];
        
        $sql = "INSERT INTO user_favorites (user_id, product_id) VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE user_id = user_id";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $stmt->close();
    }
    
    return true;
}

function remove_from_favorites($product_id) {
    if (!is_logged_in()) {
        return false;
    }
    
    $product_id = (int)$product_id;
    
    if (isset($_SESSION['favorites']) && in_array($product_id, $_SESSION['favorites'])) {
        $_SESSION['favorites'] = array_diff($_SESSION['favorites'], [$product_id]);
        
        // Update database
        global $conn;
        $user_id = $_SESSION['user_id'];
        
        $sql = "DELETE FROM user_favorites WHERE user_id = ? AND product_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $stmt->close();
    }
    
    return true;
}

function is_favorite($product_id) {
    if (!is_logged_in() || !isset($_SESSION['favorites'])) {
        return false;
    }
    
    return in_array($product_id, $_SESSION['favorites']);
}

function load_user_favorites() {
    if (!is_logged_in()) {
        return;
    }
    
    global $conn;
    $user_id = $_SESSION['user_id'];
    
    $sql = "SELECT product_id FROM user_favorites WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $_SESSION['favorites'] = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $_SESSION['favorites'][] = $row['product_id'];
        }
    }
    
    $stmt->close();
}

// Password recovery functions
function generate_reset_token($email) {
    global $conn;
    
    // Check if the email exists
    $sql = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return false;
    }
    
    $user = $result->fetch_assoc();
    $user_id = $user['id'];
    
    // Generate token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Delete any existing tokens for this user
    $sql = "DELETE FROM password_resets WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    // Save new token
    $sql = "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $user_id, $token, $expires);
    $stmt->execute();
    $stmt->close();
    
    return $token;
}

function verify_reset_token($token) {
    global $conn;
    
    $sql = "SELECT pr.user_id, u.email FROM password_resets pr 
            JOIN users u ON pr.user_id = u.id 
            WHERE pr.token = ? AND pr.expires_at > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return false;
    }
    
    $data = $result->fetch_assoc();
    $stmt->close();
    
    return $data;
}

function reset_password($user_id, $password) {
    global $conn;
    
    // Hash the new password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Update user's password
    $sql = "UPDATE users SET password = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $hashed_password, $user_id);
    $result = $stmt->execute();
    
    // Delete the token
    $sql = "DELETE FROM password_resets WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    
    return $result;
}

// Page specific functions
function get_page_title($page) {
    global $config;
    
    $titles = [
        'home' => 'Home',
        'shop' => 'Shop',
        'cart' => 'Shopping Cart',
        'account' => 'My Account',
        'admin' => 'Admin Panel',
        'login' => 'Login',
        'register' => 'Register',
    ];
    
    $title = $titles[$page] ?? 'Welcome';
    
    return $title . ' - ' . $config['site_name'];
}

// Load favorites when user logs in
if (is_logged_in() && !isset($_SESSION['favorites'])) {
    load_user_favorites();
}

// Function to handle AJAX responses
function send_json_response($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Common header function
function include_header($page_title = '', $extra_head = '') {
    global $config;
    
    if (empty($page_title)) {
        $page_title = $config['site_name'];
    }
    
    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . $page_title . '</title>
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom styles -->
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --secondary-color: #ec4899;
            --accent-color: #8b5cf6;
        }
        
        body {
            font-family: "Poppins", sans-serif;
            overflow-x: hidden;
        }
        
        .bg-primary {
            background-color: var(--primary-color);
        }
        
        .bg-primary-dark {
            background-color: var(--primary-dark);
        }
        
        .text-primary {
            color: var(--primary-color);
        }
        
        .border-primary {
            border-color: var(--primary-color);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            opacity: 0.9;
        }
        
        .product-card {
            transition: all 0.3s ease;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .loader {
            border-top-color: var(--primary-color);
            animation: spinner 1.5s linear infinite;
        }
        
        @keyframes spinner {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #c7c7c7;
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        /* Rating stars */
        .stars {
            color: #fbbf24;
        }
        
        /* Category pills */
        .category-pill {
            transition: all 0.3s ease;
        }
        
        .category-pill:hover, .category-pill.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        /* Animations */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .slide-in {
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        /* Toast notification */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            animation: toastIn 0.3s, toastOut 0.3s 2.7s;
            animation-fill-mode: forwards;
        }
        
        @keyframes toastIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes toastOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        
        /* Line clamp */
        .line-clamp-1 {
            overflow: hidden;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 1;
        }
        
        .line-clamp-2 {
            overflow: hidden;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
        }
    </style>
    ' . $extra_head . '
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <!-- Logo -->
                <a href="index.php" class="flex items-center space-x-2">
                    <span class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500">' . $config['site_name'] . '</span>
                </a>
                
                <!-- Middle: Search -->
                <div class="hidden md:flex items-center flex-1 max-w-md mx-6">
                    <div class="relative w-full">
                        <input type="text" id="search-input" class="w-full pl-10 pr-4 py-2 rounded-full border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Search products...">
                        <div class="absolute left-3 top-2.5 text-gray-400">
                            <i class="fas fa-search"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Right: User menu & cart -->
                <div class="flex items-center space-x-4">
                    <!-- Mobile search icon -->
                    <button id="mobile-search-btn" class="md:hidden text-gray-700 hover:text-indigo-600 transition duration-300">
                        <i class="fas fa-search text-xl"></i>
                    </button>
                    
                    <!-- Cart -->
                    <div class="relative group">
                        <button id="cart-btn" class="text-gray-700 hover:text-indigo-600 transition duration-300">
                            <i class="fas fa-shopping-cart text-xl"></i>
                            <span id="cart-count" class="absolute -top-2 -right-2 bg-pink-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                ' . get_cart_count() . '
                            </span>
                        </button>
                    </div>
                    
                    <!-- User menu -->
                    <div class="relative">
                        <button id="user-menu-btn" class="flex items-center space-x-2 text-gray-700 hover:text-indigo-600 transition duration-300">
                            ';
                            
    if (is_logged_in()) {
        echo '<span class="hidden sm:inline">' . htmlspecialchars($_SESSION['username']) . '</span>';
    }
    
    echo '<i class="fas fa-user-circle text-xl"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Mobile search -->
            <div id="mobile-search" class="mt-3 md:hidden hidden">
                <input type="text" id="mobile-search-input" class="w-full pl-10 pr-4 py-2 rounded-full border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Search products...">
                <div class="absolute left-7 top-[4.7rem] text-gray-400">
                    <i class="fas fa-search"></i>
                </div>
            </div>
        </div>
    </nav>';
}

// Common footer function
function include_footer($include_modals = true) {
    global $config;
    
    echo '<!-- Footer -->
    <footer class="bg-gray-800 text-white py-10 mt-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4">' . $config['site_name'] . '</h3>
                    <p class="text-gray-400">Your one-stop destination for all your shopping needs. We offer high-quality products at competitive prices.</p>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">About Us</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">Contact</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">Terms of Service</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">Privacy Policy</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xl font-bold mb-4">Connect With Us</h3>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white transition duration-300"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white transition duration-300"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white transition duration-300"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white transition duration-300"><i class="fab fa-linkedin fa-lg"></i></a>
                    </div>
                    <div class="mt-4">
                        <h4 class="text-sm font-semibold mb-2">Subscribe to our newsletter</h4>
                        <div class="flex">
                            <input type="email" placeholder="Your email" class="px-4 py-2 w-full text-gray-800 rounded-l focus:outline-none">
                            <button class="bg-indigo-600 px-4 py-2 rounded-r hover:bg-indigo-700 transition duration-300">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center">
                <p class="text-gray-400">&copy; ' . date('Y') . ' ' . $config['site_name'] . '. All rights reserved.</p>
            </div>
        </div>
    </footer>';
    
    if ($include_modals) {
        include_modals();
    }
    
    echo '<!-- Toast Notification -->
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
    
    <!-- Common JavaScript -->
    <script>
        $(document).ready(function() {
            // Mobile search toggle
            $("#mobile-search-btn").on("click", function() {
                $("#mobile-search").toggleClass("hidden");
            });
            
            // User menu toggle
            $("#user-menu-btn").on("click", function() {
                $("#user-menu-modal").removeClass("hidden");
            });
            
            $(document).on("click", "#close-user-menu, #user-menu-backdrop", function() {
                $("#user-menu-modal").addClass("hidden");
            });
            
            // Cart toggle
            $("#cart-btn").on("click", function() {
                $("#cart-modal").removeClass("hidden");
                loadCart();
            });
            
            $(document).on("click", "#close-cart, #cart-backdrop", function() {
                $("#cart-modal").addClass("hidden");
            });
            
            $(document).on("click", "#continue-shopping", function() {
                $("#cart-modal").addClass("hidden");
            });
            
            // Toast notification
            $("#close-toast").on("click", function() {
                $("#toast").addClass("hidden");
            });
            
            // Logout link
            $(document).on("click", "#logout-link", function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: "ajax_handler.php",
                    type: "POST",
                    dataType: "json",
                    data: {
                        action: "logout"
                    },
                    success: function(response) {
                        if (response.success) {
                            window.location.href = "index.php";
                        }
                    }
                });
            });
            
            // Search functionality
            $("#search-input, #mobile-search-input").on("keyup", function(e) {
                if (e.key === "Enter") {
                    const searchTerm = $(this).val().trim();
                    if (searchTerm.length > 0) {
                        window.location.href = "index.php?search=" + encodeURIComponent(searchTerm);
                    }
                }
            });
            
            // Cart functionality
            function loadCart() {
                $("#cart-items").html(\'<div class="flex justify-center items-center py-8"><div class="loader rounded-full border-4 border-gray-200 h-12 w-12"></div></div>\');
                $("#cart-empty, #cart-footer").addClass("hidden");
                
                $.ajax({
                    url: "ajax_handler.php",
                    type: "POST",
                    dataType: "json",
                    data: {
                        action: "get_cart"
                    },
                    success: function(response) {
                        if (response.success) {
                            const items = response.items;
                            
                            if (items.length === 0) {
                                $("#cart-items").html("");
                                $("#cart-empty").removeClass("hidden");
                            } else {
                                let html = "";
                                
                                for (const item of items) {
                                    html += `
                                        <div class="flex items-center border-b pb-4" data-id="${item.id}">
                                            <div class="w-20 h-20 flex-shrink-0">
                                                <img src="${item.image_url}" alt="${item.name}" class="w-full h-full object-cover rounded">
                                            </div>
                                            <div class="ml-4 flex-1">
                                                <h4 class="text-sm font-medium text-gray-900">${item.name}</h4>
                                                <p class="text-sm text-gray-500">$${item.formatted_price} x ${item.quantity}</p>
                                                <div class="flex items-center mt-2">
                                                    <button class="decrease-quantity bg-gray-200 text-gray-600 w-6 h-6 rounded-full flex items-center justify-center"><i class="fas fa-minus text-xs"></i></button>
                                                    <span class="item-quantity mx-2">${item.quantity}</span>
                                                    <button class="increase-quantity bg-gray-200 text-gray-600 w-6 h-6 rounded-full flex items-center justify-center ${item.quantity >= item.stock ? "opacity-50 cursor-not-allowed" : ""}"><i class="fas fa-plus text-xs"></i></button>
                                                </div>
                                            </div>
                                            <div class="ml-4 text-right">
                                                <p class="text-sm font-medium text-gray-900">$${item.formatted_subtotal}</p>
                                                <button class="remove-item text-red-500 hover:text-red-700 text-sm mt-2"><i class="fas fa-trash-alt mr-1"></i> Remove</button>
                                            </div>
                                        </div>
                                    `;
                                }
                                
                                $("#cart-items").html(html);
                                $("#cart-total").text("$" + response.cart_total);
                                $("#cart-footer").removeClass("hidden");
                                
                                // Update cart count
                                $("#cart-count").text(response.cart_count);
                            }
                        }
                    }
                });
            }
            
            // Remove item from cart
            $(document).on("click", ".remove-item", function() {
                const itemContainer = $(this).closest("[data-id]");
                const productId = itemContainer.data("id");
                
                $.ajax({
                    url: "ajax_handler.php",
                    type: "POST",
                    dataType: "json",
                    data: {
                        action: "update_cart",
                        product_id: productId,
                        quantity: 0
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update cart count
                            $("#cart-count").text(response.cart_count);
                            
                            // Remove item from cart
                            itemContainer.fadeOut(300, function() {
                                $(this).remove();
                                
                                // Update cart total
                                $("#cart-total").text("$" + response.cart_total);
                                
                                // Show empty message if no items left
                                if ($("#cart-items").children().length === 0) {
                                    $("#cart-empty").removeClass("hidden");
                                    $("#cart-footer").addClass("hidden");
                                }
                            });
                            
                            showToast("success", "Item removed from cart");
                        }
                    }
                });
            });
            
            // Decrease quantity
            $(document).on("click", ".decrease-quantity", function() {
                const itemContainer = $(this).closest("[data-id]");
                const productId = itemContainer.data("id");
                const quantityElement = itemContainer.find(".item-quantity");
                const currentQuantity = parseInt(quantityElement.text());
                
                if (currentQuantity > 1) {
                    const newQuantity = currentQuantity - 1;
                    
                    $.ajax({
                        url: "ajax_handler.php",
                        type: "POST",
                        dataType: "json",
                        data: {
                            action: "update_cart",
                            product_id: productId,
                            quantity: newQuantity
                        },
                        success: function(response) {
                            if (response.success) {
                                // Update quantity
                                quantityElement.text(newQuantity);
                                
                                // Update cart count
                                $("#cart-count").text(response.cart_count);
                                
                                // Update subtotal
                                const priceElement = itemContainer.find(".text-right p");
                                const unitPrice = parseFloat(itemContainer.find(".text-gray-500").text().replace("$", "").split(" x ")[0]);
                                const newSubtotal = (unitPrice * newQuantity).toFixed(2);
                                priceElement.text("$" + newSubtotal);
                                
                                // Update cart total
                                $("#cart-total").text("$" + response.cart_total);
                                
                                // Enable increase button
                                itemContainer.find(".increase-quantity").removeClass("opacity-50 cursor-not-allowed");
                            }
                        }
                    });
                }
            });
            
            // Increase quantity
            $(document).on("click", ".increase-quantity", function() {
                if ($(this).hasClass("cursor-not-allowed")) {
                    return;
                }
                
                const itemContainer = $(this).closest("[data-id]");
                const productId = itemContainer.data("id");
                const quantityElement = itemContainer.find(".item-quantity");
                const currentQuantity = parseInt(quantityElement.text());
                const newQuantity = currentQuantity + 1;
                
                $.ajax({
                    url: "ajax_handler.php",
                    type: "POST",
                    dataType: "json",
                    data: {
                        action: "update_cart",
                        product_id: productId,
                        quantity: newQuantity
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update quantity
                            quantityElement.text(newQuantity);
                            
                            // Update cart count
                            $("#cart-count").text(response.cart_count);
                            
                            // Update subtotal
                            const priceElement = itemContainer.find(".text-right p");
                            const unitPrice = parseFloat(itemContainer.find(".text-gray-500").text().replace("$", "").split(" x ")[0]);
                            const newSubtotal = (unitPrice * newQuantity).toFixed(2);
                            priceElement.text("$" + newSubtotal);
                            
                            // Update cart total
                            $("#cart-total").text("$" + response.cart_total);
                            
                            // Check if stock limit reached
                            const stockLimit = parseInt(itemContainer.find(".increase-quantity").data("stock"));
                            if (newQuantity >= stockLimit) {
                                itemContainer.find(".increase-quantity").addClass("opacity-50 cursor-not-allowed");
                            }
                        } else {
                            showToast("error", response.message);
                        }
                    }
                });
            });
            
            // Add to cart
            $(document).on("click", ".add-to-cart", function() {
                const productId = $(this).data("id");
                const quantity = $(this).data("quantity") || 1;
                
                $.ajax({
                    url: "ajax_handler.php",
                    type: "POST",
                    dataType: "json",
                    data: {
                        action: "add_to_cart",
                        product_id: productId,
                        quantity: quantity
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update cart count
                            $("#cart-count").text(response.cart_count);
                            
                            // Close quick view if open
                            $("#quick-view-modal").addClass("hidden");
                            
                            showToast("success", response.message);
                        } else {
                            showToast("error", response.message);
                        }
                    }
                });
            });
            
            // Toggle favorite
            $(document).on("click", ".toggle-favorite", function() {
                const button = $(this);
                const productId = button.data("id");
                
                $.ajax({
                    url: "ajax_handler.php",
                    type: "POST",
                    dataType: "json",
                    data: {
                        action: "toggle_favorite",
                        product_id: productId
                    },
                    success: function(response) {
                        if (response.success) {
                            if (response.is_favorite) {
                                button.addClass("bg-red-100 text-red-500").removeClass("bg-gray-100 text-gray-400");
                                button.find("i").addClass("fas").removeClass("far");
                                showToast("success", response.message);
                            } else {
                                button.removeClass("bg-red-100 text-red-500").addClass("bg-gray-100 text-gray-400");
                                button.find("i").removeClass("fas").addClass("far");
                                showToast("success", response.message);
                            }
                            
                            // Update all instances of this product\'s favorite button
                            $(`.product-card[data-id="${productId}"] .toggle-favorite`).each(function() {
                                if (response.is_favorite) {
                                    $(this).addClass("bg-red-100 text-red-500").removeClass("bg-gray-100 text-gray-400");
                                    $(this).find("i").addClass("fas").removeClass("far");
                                } else {
                                    $(this).removeClass("bg-red-100 text-red-500").addClass("bg-gray-100 text-gray-400");
                                    $(this).find("i").removeClass("fas").addClass("far");
                                }
                            });
                        } else {
                            showToast("error", response.message);
                            
                            // If not logged in, show login modal
                            if (response.message.includes("logged in")) {
                                window.location.href = "login.php";
                            }
                        }
                    }
                });
            });
            
            // Quick view
            $(document).on("click", ".quick-view", function() {
                const productId = $(this).data("id");
                
                // Show loading
                $("#quick-view-modal").removeClass("hidden");
                $("#quick-view-loading").removeClass("hidden");
                $("#quick-view-content").html("");
                
                $.ajax({
                    url: "ajax_handler.php",
                    type: "POST",
                    dataType: "json",
                    data: {
                        action: "quick_view",
                        product_id: productId
                    },
                    success: function(response) {
                        if (response.success) {
                            const product = response.product;
                            
                            // Generate rating stars
                            let stars = "";
                            for (let i = 1; i <= 5; i++) {
                                if (i <= Math.floor(product.rating)) {
                                    stars += \'<i class="fas fa-star"></i>\';
                                } else if (i === Math.ceil(product.rating) && product.rating % 1 !== 0) {
                                    stars += \'<i class="fas fa-star-half-alt"></i>\';
                                } else {
                                    stars += \'<i class="far fa-star"></i>\';
                                }
                            }
                            
                            // Build HTML
                            let html = `
                                <div class="h-full bg-gray-100 p-0">
                                    <img src="${product.image_url}" alt="${product.name}" class="w-full h-full object-cover">
                                </div>
                                <div class="p-8">
                                    <h2 class="text-2xl font-bold text-gray-800 mb-2">${product.name}</h2>
                                    <div class="flex items-center mb-4">
                                        <div class="stars text-yellow-400 mr-2">
                                            ${stars}
                                        </div>
                                        <span class="text-gray-600">(${product.rating})</span>
                                    </div>
                                    
                                    <div class="mb-6">
                                        ${product.discount_percent > 0 ? 
                                            `<div class="flex items-center">
                                                <span class="text-2xl font-bold text-indigo-600 mr-2">$${product.formatted_discounted_price}</span>
                                                <span class="text-lg text-gray-500 line-through">$${product.formatted_original_price}</span>
                                                <span class="ml-2 bg-red-100 text-red-800 text-xs font-semibold px-2 py-1 rounded">${product.discount_percent}% OFF</span>
                                            </div>` : 
                                            `<span class="text-2xl font-bold text-indigo-600">$${product.formatted_original_price}</span>`
                                        }
                                    </div>
                                    
                                    <div class="mb-6">
                                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Description</h3>
                                        <p class="text-gray-600">${product.description}</p>
                                    </div>
                                    
                                    <div class="mb-6">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center">
                                                <span class="text-gray-600 mr-2">Quantity:</span>
                                                <div class="flex items-center">
                                                    <button class="quick-view-decrease bg-gray-200 text-gray-600 w-8 h-8 rounded-full flex items-center justify-center"><i class="fas fa-minus"></i></button>
                                                    <span class="quick-view-quantity mx-4 text-lg font-semibold">1</span>
                                                    <button class="quick-view-increase bg-gray-200 text-gray-600 w-8 h-8 rounded-full flex items-center justify-center"><i class="fas fa-plus"></i></button>
                                                </div>
                                            </div>
                                            <div>
                                                <span class="text-gray-600">Available: <span class="font-semibold">${product.stock}</span></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center space-x-4">
                                        <button class="add-to-cart flex-1 bg-indigo-600 text-white py-3 px-4 rounded-md hover:bg-indigo-700 transition duration-300" data-id="${product.id}" data-quantity="1">
                                            <i class="fas fa-shopping-cart mr-2"></i> Add to Cart
                                        </button>
                                        
                                        <button class="toggle-favorite w-12 h-12 flex items-center justify-center rounded-full ${product.is_favorite ? "bg-red-100 text-red-500" : "bg-gray-100 text-gray-400"} hover:bg-gray-200 transition duration-300" data-id="${product.id}">
                                            <i class="${product.is_favorite ? "fas" : "far"} fa-heart text-xl"></i>
                                        </button>
                                    </div>
                                    
                                    <div class="mt-6 pt-6 border-t">
                                        <div class="flex items-center text-sm text-gray-500">
                                            <span>Category: <span class="font-semibold">${product.category}</span></span>
                                            <span class="mx-2">•</span>
                                            <span>Added by: <span class="font-semibold">${product.username}</span></span>
                                        </div>
                                    </div>
                                </div>
                            `;
                            
                            $("#quick-view-content").html(html);
                        } else {
                            $("#quick-view-content").html(`
                                <div class="col-span-2 p-8">
                                    <div class="text-center">
                                        <i class="fas fa-exclamation-circle text-red-500 text-5xl mb-4"></i>
                                        <p class="text-gray-600">${response.message}</p>
                                    </div>
                                </div>
                            `);
                        }
                    },
                    error: function() {
                        $("#quick-view-content").html(`
                            <div class="col-span-2 p-8">
                                <div class="text-center">
                                    <i class="fas fa-exclamation-circle text-red-500 text-5xl mb-4"></i>
                                    <p class="text-gray-600">An error occurred. Please try again.</p>
                                </div>
                            </div>
                        `);
                    },
                    complete: function() {
                        $("#quick-view-loading").addClass("hidden");
                    }
                });
            });
            
            // Close quick view modal
            $(document).on("click", "#close-quick-view, #quick-view-backdrop", function() {
                $("#quick-view-modal").addClass("hidden");
            });
            
            // Quick view quantity controls
            $(document).on("click", ".quick-view-decrease", function() {
                const quantityElement = $(".quick-view-quantity");
                const currentQuantity = parseInt(quantityElement.text());
                
                if (currentQuantity > 1) {
                    quantityElement.text(currentQuantity - 1);
                    $(".add-to-cart").data("quantity", currentQuantity - 1);
                }
            });
            
            $(document).on("click", ".quick-view-increase", function() {
                const quantityElement = $(".quick-view-quantity");
                const currentQuantity = parseInt(quantityElement.text());
                const maxStock = parseInt($(".add-to-cart").closest(".p-8").find(".font-semibold").last().text());
                
                if (currentQuantity < maxStock) {
                    quantityElement.text(currentQuantity + 1);
                    $(".add-to-cart").data("quantity", currentQuantity + 1);
                }
            });
            
            // Show toast notification
            function showToast(type, message) {
                const toast = $("#toast");
                const toastMessage = $("#toast-message");
                const toastIcon = $("#toast-icon");
                
                if (type === "success") {
                    toastIcon.html(\'<i class="fas fa-check-circle text-green-500 text-xl"></i>\');
                } else if (type === "error") {
                    toastIcon.html(\'<i class="fas fa-exclamation-circle text-red-500 text-xl"></i>\');
                } else if (type === "info") {
                    toastIcon.html(\'<i class="fas fa-info-circle text-blue-500 text-xl"></i>\');
                }
                
                toastMessage.text(message);
                toast.removeClass("hidden");
                
                // Hide after 3 seconds
                setTimeout(function() {
                    toast.addClass("hidden");
                }, 3000);
            }
        });
    </script>
</body>
</html>';
}

// Common modals
function include_modals() {
    echo '<!-- User Menu Modal -->
    <div id="user-menu-modal" class="fixed inset-0 z-50 flex items-start justify-end hidden">
        <div class="fixed inset-0 bg-black bg-opacity-50" id="user-menu-backdrop"></div>
        <div class="relative bg-white shadow-lg w-full max-w-sm h-screen overflow-auto slide-in">
            <div class="p-6">
                ';
                
    if (is_logged_in()) {
        echo '<div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold">Hello, ' . htmlspecialchars($_SESSION['username']) . '</h3>
                    <button id="close-user-menu" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <ul class="space-y-4">
                    <li>
                        <a href="#" class="flex items-center space-x-3 text-gray-700 hover:text-indigo-600 transition duration-300">
                            <i class="fas fa-user-circle w-6"></i>
                            <span>My Profile</span>
                        </a>
                    </li>
                    <li>
                        <a href="#" id="my-orders-link" class="flex items-center space-x-3 text-gray-700 hover:text-indigo-600 transition duration-300">
                            <i class="fas fa-box w-6"></i>
                            <span>My Orders</span>
                        </a>
                    </li>
                    <li>
                        <a href="favorites.php" id="favorites-link" class="flex items-center space-x-3 text-gray-700 hover:text-indigo-600 transition duration-300">
                            <i class="fas fa-heart w-6"></i>
                            <span>Favorites</span>
                        </a>
                    </li>';
        
        if (is_admin()) {
            echo '<li>
                        <a href="admin.php" id="admin-link" class="flex items-center space-x-3 text-gray-700 hover:text-indigo-600 transition duration-300">
                            <i class="fas fa-cog w-6"></i>
                            <span>Admin Panel</span>
                        </a>
                    </li>';
        }
        
        echo '<li class="border-t pt-4 mt-4">
                        <a href="#" id="logout-link" class="flex items-center space-x-3 text-red-600 hover:text-red-800 transition duration-300">
                            <i class="fas fa-sign-out-alt w-6"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>';
    } else {
        echo '<div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold">Account</h3>
                    <button id="close-user-menu" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="space-y-4">
                    <p class="text-gray-600 mb-4">Login to your account or create a new one to start shopping.</p>
                    <a href="login.php" class="block w-full bg-indigo-600 text-white py-2 rounded-md hover:bg-indigo-700 transition duration-300 text-center">Login</a>
                    <a href="register.php" class="block w-full bg-white text-indigo-600 py-2 rounded-md border border-indigo-600 hover:bg-gray-50 transition duration-300 text-center">Register</a>
                </div>';
    }
    
    echo '</div>
        </div>
    </div>

    <!-- Cart Modal -->
    <div id="cart-modal" class="fixed inset-0 z-50 flex items-start justify-end hidden">
        <div class="fixed inset-0 bg-black bg-opacity-50" id="cart-backdrop"></div>
        <div class="relative bg-white shadow-lg w-full max-w-md h-screen overflow-auto slide-in">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold">Shopping Cart</h3>
                    <button id="close-cart" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <div id="cart-items" class="space-y-4">
                    <!-- Cart items will be loaded here -->
                    <div class="flex justify-center items-center py-8">
                        <div class="loader rounded-full border-4 border-gray-200 h-12 w-12"></div>
                    </div>
                </div>
                
                <div id="cart-empty" class="hidden text-center py-8">
                    <i class="fas fa-shopping-cart text-5xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500">Your cart is empty</p>
                    <button id="continue-shopping" class="mt-4 px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition duration-300">
                        Continue Shopping
                    </button>
                </div>
                
                <div id="cart-footer" class="mt-6 pt-6 border-t hidden">
                    <div class="flex justify-between font-bold text-lg mb-6">
                        <span>Total:</span>
                        <span id="cart-total" class="text-indigo-600">$0.00</span>
                    </div>
                    <button id="checkout-btn" class="w-full bg-indigo-600 text-white py-3 rounded-md hover:bg-indigo-700 transition duration-300">
                        Proceed to Checkout
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick View Modal -->
    <div id="quick-view-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="fixed inset-0 bg-black bg-opacity-70" id="quick-view-backdrop"></div>
        <div class="relative bg-white rounded-lg shadow-lg w-full max-w-4xl p-0 fade-in max-h-[90vh] overflow-auto">
            <button id="close-quick-view" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 z-10">
                <i class="fas fa-times text-xl"></i>
            </button>
            
            <div id="quick-view-content" class="grid grid-cols-1 md:grid-cols-2">
                <!-- Loading spinner -->
                <div id="quick-view-loading" class="col-span-2 flex justify-center items-center p-16">
                    <div class="loader rounded-full border-4 border-gray-200 h-16 w-16"></div>
                </div>
                
                <!-- Product details will be loaded here -->
            </div>
        </div>
    </div>';
}

// Function to create AJAX handler
function create_ajax_handler() {
    global $conn;
    
    // Create ajax_handler.php
    $ajax_handler = '<?php
require_once "config.php";

// Check if AJAX request
if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest") {
    $action = $_POST["action"] ?? "";
    $response = ["success" => false, "message" => ""];
    
    // Login AJAX handler
    if ($action === "login") {
        $username = sanitize_input($_POST["username"] ?? "");
        $password = $_POST["password"] ?? "";
        
        if (empty($username) || empty($password)) {
            $response["message"] = "Please enter both username and password.";
        } else {
            $sql = "SELECT id, username, password, is_admin FROM users WHERE username = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                if (password_verify($password, $user["password"])) {
                    $_SESSION["user_id"] = $user["id"];
                    $_SESSION["username"] = $user["username"];
                    $_SESSION["is_admin"] = $user["is_admin"];
                    
                    load_user_favorites();
                    
                    $response["success"] = true;
                    $response["message"] = "Login successful";
                    $response["isAdmin"] = $user["is_admin"] == 1;
                } else {
                    $response["message"] = "Invalid username or password.";
                }
            } else {
                $response["message"] = "Invalid username or password.";
            }
            
            $stmt->close();
        }
    }
    
    // Register AJAX handler
    else if ($action === "register") {
        $username = sanitize_input($_POST["username"] ?? "");
        $email = sanitize_input($_POST["email"] ?? "");
        $password = $_POST["password"] ?? "";
        $confirm_password = $_POST["confirm_password"] ?? "";
        
        if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
            $response["message"] = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response["message"] = "Please enter a valid email address.";
        } elseif (strlen($password) < 6) {
            $response["message"] = "Password must be at least 6 characters long.";
        } elseif ($password !== $confirm_password) {
            $response["message"] = "Passwords do not match.";
        } else {
            $sql = "SELECT id FROM users WHERE username = ? OR email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $response["message"] = "Username or email already exists.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sss", $username, $email, $hashed_password);
                
                if ($stmt->execute()) {
                    $response["success"] = true;
                    $response["message"] = "Registration successful! You can now login.";
                } else {
                    $response["message"] = "Error: " . $stmt->error;
                }
            }
            
            $stmt->close();
        }
    }
    
    // Forgot password AJAX handler
    else if ($action === "forgot_password") {
        $email = sanitize_input($_POST["email"] ?? "");
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response["message"] = "Please enter a valid email address.";
        } else {
            $token = generate_reset_token($email);
            
            if ($token) {
                // In a real application, send an email with the reset link
                // Here we just return the token for demo purposes
                $response["success"] = true;
                $response["message"] = "Password reset instructions sent to your email.";
                $response["token"] = $token; // For demo only
            } else {
                $response["message"] = "Email address not found.";
            }
        }
    }
    
    // Reset password AJAX handler
    else if ($action === "reset_password") {
        $token = sanitize_input($_POST["token"] ?? "");
        $password = $_POST["password"] ?? "";
        $confirm_password = $_POST["confirm_password"] ?? "";
        
        if (empty($token) || empty($password) || empty($confirm_password)) {
            $response["message"] = "All fields are required.";
        } elseif (strlen($password) < 6) {
            $response["message"] = "Password must be at least 6 characters long.";
        } elseif ($password !== $confirm_password) {
            $response["message"] = "Passwords do not match.";
        } else {
            $user_data = verify_reset_token($token);
            
            if ($user_data) {
                if (reset_password($user_data["user_id"], $password)) {
                    $response["success"] = true;
                    $response["message"] = "Password has been reset successfully.";
                } else {
                    $response["message"] = "Error resetting password.";
                }
            } else {
                $response["message"] = "Invalid or expired token.";
            }
        }
    }
    
    // Add to cart AJAX handler
    else if ($action === "add_to_cart") {
        $product_id = (int)($_POST["product_id"] ?? 0);
        $quantity = (int)($_POST["quantity"] ?? 1);
        
        if ($product_id <= 0 || $quantity <= 0) {
            $response["message"] = "Invalid product or quantity.";
        } else {
            // Check if product exists and has enough stock
            $sql = "SELECT id, name, stock FROM products WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $product = $result->fetch_assoc();
                
                // Get current cart quantity for this product
                $current_quantity = isset($_SESSION["cart"][$product_id]) ? $_SESSION["cart"][$product_id] : 0;
                
                if ($quantity + $current_quantity > $product["stock"]) {
                    $response["message"] = "Not enough stock available.";
                } else {
                    if (add_to_cart($product_id, $quantity)) {
                        $response["success"] = true;
                        $response["message"] = "Product added to cart.";
                        $response["cart_count"] = get_cart_count();
                        $response["cart_total"] = number_format(get_cart_total(), 2);
                    } else {
                        $response["message"] = "Error adding product to cart.";
                    }
                }
            } else {
                $response["message"] = "Product not found.";
            }
            
            $stmt->close();
        }
    }
    
    // Update cart AJAX handler
    else if ($action === "update_cart") {
        $product_id = (int)($_POST["product_id"] ?? 0);
        $quantity = (int)($_POST["quantity"] ?? 0);
        
        if ($product_id <= 0) {
            $response["message"] = "Invalid product.";
        } else {
            if ($quantity > 0) {
                // Check stock before updating
                $sql = "SELECT stock FROM products WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $product = $result->fetch_assoc();
                    
                    if ($quantity > $product["stock"]) {
                        $response["message"] = "Not enough stock available.";
                    } else {
                        update_cart_quantity($product_id, $quantity);
                        $response["success"] = true;
                        $response["message"] = "Cart updated.";
                    }
                } else {
                    $response["message"] = "Product not found.";
                }
                
                $stmt->close();
            } else {
                remove_from_cart($product_id);
                $response["success"] = true;
                $response["message"] = "Product removed from cart.";
            }
            
            $response["cart_count"] = get_cart_count();
            $response["cart_total"] = number_format(get_cart_total(), 2);
        }
    }
    
    // Get cart AJAX handler
    else if ($action === "get_cart") {
        $response["success"] = true;
        $response["items"] = [];
        
        $cart = get_cart_items();
        
        if (!empty($cart)) {
            $product_ids = array_keys($cart);
            $ids_str = implode(",", $product_ids);
            
            $sql = "SELECT id, name, price, image_url, stock, discount_percent FROM products WHERE id IN ($ids_str)";
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $product_id = $row["id"];
                    $quantity = $cart[$product_id];
                    
                    // Apply discount if applicable
                    $price = $row["price"];
                    if ($row["discount_percent"] > 0) {
                        $price = $price * (1 - $row["discount_percent"] / 100);
                    }
                    
                    $subtotal = $price * $quantity;
                    
                    $response["items"][] = [
                        "id" => $product_id,
                        "name" => $row["name"],
                        "price" => $price,
                        "formatted_price" => number_format($price, 2),
                        "image_url" => $row["image_url"],
                        "quantity" => $quantity,
                        "stock" => $row["stock"],
                        "subtotal" => $subtotal,
                        "formatted_subtotal" => number_format($subtotal, 2)
                    ];
                }
            }
        }
        
        $response["cart_count"] = get_cart_count();
        $response["cart_total"] = number_format(get_cart_total(), 2);
    }
    
    // Toggle favorite AJAX handler
    else if ($action === "toggle_favorite") {
        if (!is_logged_in()) {
            $response["message"] = "You must be logged in to add favorites.";
        } else {
            $product_id = (int)($_POST["product_id"] ?? 0);
            
            if ($product_id <= 0) {
                $response["message"] = "Invalid product.";
            } else {
                $is_favorite = is_favorite($product_id);
                
                if ($is_favorite) {
                    remove_from_favorites($product_id);
                    $response["message"] = "Product removed from favorites.";
                    $response["is_favorite"] = false;
                } else {
                    add_to_favorites($product_id);
                    $response["message"] = "Product added to favorites.";
                    $response["is_favorite"] = true;
                }
                
                $response["success"] = true;
            }
        }
    }
    
    // Get product quick view AJAX handler
    else if ($action === "quick_view") {
        $product_id = (int)($_POST["product_id"] ?? 0);
        
        if ($product_id <= 0) {
            $response["message"] = "Invalid product.";
        } else {
            $sql = "SELECT p.*, u.username FROM products p 
                    LEFT JOIN users u ON p.user_id = u.id 
                    WHERE p.id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $product = $result->fetch_assoc();
                
                // Format price with discount if applicable
                $original_price = $product["price"];
                $discount_percent = $product["discount_percent"];
                $discounted_price = $original_price;
                
                if ($discount_percent > 0) {
                    $discounted_price = $original_price * (1 - $discount_percent / 100);
                }
                
                $product["original_price"] = $original_price;
                $product["formatted_original_price"] = number_format($original_price, 2);
                $product["discounted_price"] = $discounted_price;
                $product["formatted_discounted_price"] = number_format($discounted_price, 2);
                $product["is_favorite"] = is_favorite($product_id);
                
                $response["success"] = true;
                $response["product"] = $product;
            } else {
                $response["message"] = "Product not found.";
            }
            
            $stmt->close();
        }
    }
    
    // Get products AJAX handler
    else if ($action === "get_products") {
        $response["success"] = true;
        $response["products"] = [];
        
        $category = sanitize_input($_POST["category"] ?? "");
        $search = sanitize_input($_POST["search"] ?? "");
        $sort = sanitize_input($_POST["sort"] ?? "newest");
        $page = (int)($_POST["page"] ?? 1);
        $limit = (int)($_POST["limit"] ?? 8);
        $offset = ($page - 1) * $limit;
        $favorites_only = isset($_POST["favorites"]) && $_POST["favorites"] === "true";
        
        // Build query
        $query = "SELECT p.*, u.username FROM products p LEFT JOIN users u ON p.user_id = u.id WHERE 1=1";
        $count_query = "SELECT COUNT(*) as total FROM products p WHERE 1=1";
        $params = [];
        $param_types = "";
        
        // Add favorites filter
        if ($favorites_only && is_logged_in()) {
            $user_id = $_SESSION["user_id"];
            $query = "SELECT p.*, u.username FROM products p
                    LEFT JOIN users u ON p.user_id = u.id
                    INNER JOIN user_favorites uf ON p.id = uf.product_id
                    WHERE uf.user_id = ?";
            $count_query = "SELECT COUNT(*) as total FROM products p
                          INNER JOIN user_favorites uf ON p.id = uf.product_id
                          WHERE uf.user_id = ?";
            $params[] = $user_id;
            $param_types .= "i";
        }
        
        // Add category filter
        if (!empty($category) && $category !== "all") {
            $query .= " AND p.category = ?";
            $count_query .= " AND p.category = ?";
            $params[] = $category;
            $param_types .= "s";
        }
        
        // Add search filter
        if (!empty($search)) {
            $search_term = "%" . $search . "%";
            $query .= " AND (p.name LIKE ? OR p.description LIKE ? OR p.tags LIKE ?)";
            $count_query .= " AND (p.name LIKE ? OR p.description LIKE ? OR p.tags LIKE ?)";
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
            $param_types .= "sss";
        }
        
        // Add sorting
        switch ($sort) {
            case "price_low":
                $query .= " ORDER BY p.price ASC";
                break;
            case "price_high":
                $query .= " ORDER BY p.price DESC";
                break;
            case "rating":
                $query .= " ORDER BY p.rating DESC";
                break;
            case "discount":
                $query .= " ORDER BY p.discount_percent DESC";
                break;
            case "oldest":
                $query .= " ORDER BY p.created_at ASC";
                break;
            case "newest":
            default:
                $query .= " ORDER BY p.created_at DESC";
                break;
        }
        
        // Add pagination
        $query .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $param_types .= "ii";
        
        // Get total count
        $count_stmt = $conn->prepare($count_query);
        if (!empty($param_types)) {
            // For count query, we dont need the limit and offset params
            $count_stmt->bind_param(substr($param_types, 0, -2), ...array_slice($params, 0, -2));
        }
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $total_count = $count_result->fetch_assoc()["total"];
        $count_stmt->close();
        
        // Get products
        $stmt = $conn->prepare($query);
        if (!empty($param_types)) {
            $stmt->bind_param($param_types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = [];
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $product_id = $row["id"];
                
                // Calculate discounted price
                $original_price = $row["price"];
                $discount_percent = $row["discount_percent"];
                $discounted_price = $original_price;
                
                if ($discount_percent > 0) {
                    $discounted_price = $original_price * (1 - $discount_percent / 100);
                }
                
                $products[] = [
                    "id" => $product_id,
                    "name" => $row["name"],
                    "description" => $row["description"],
                    "price" => $original_price,
                    "formatted_price" => number_format($original_price, 2),
                    "discounted_price" => $discounted_price,
                    "formatted_discounted_price" => number_format($discounted_price, 2),
                    "discount_percent" => $discount_percent,
                    "image_url" => $row["image_url"],
                    "stock" => $row["stock"],
                    "category" => $row["category"],
                    "tags" => $row["tags"],
                    "rating" => $row["rating"],
                    "username" => $row["username"],
                    "is_favorite" => is_favorite($product_id)
                ];
            }
        }
        
        $stmt->close();
        
        $response["products"] = $products;
        $response["total"] = $total_count;
        $response["pages"] = ceil($total_count / $limit);
        $response["current_page"] = $page;
    }
    
    // Admin actions
    else if ($action === "admin_action" && is_admin()) {
        $admin_action = sanitize_input($_POST["admin_action"] ?? "");
        
        // Add product
        if ($admin_action === "add_product") {
            $name = sanitize_input($_POST["name"] ?? "");
            $description = sanitize_input($_POST["description"] ?? "");
            $price = floatval($_POST["price"] ?? 0);
            $stock = intval($_POST["stock"] ?? 0);
            $category = sanitize_input($_POST["category"] ?? "");
            $tags = sanitize_input($_POST["tags"] ?? "");
            $discount_percent = intval($_POST["discount_percent"] ?? 0);
            $image_url = sanitize_input($_POST["image_url"] ?? "");
            
            if (empty($name) || empty($description) || $price <= 0) {
                $response["message"] = "Please fill in all required fields.";
            } else {
                $user_id = $_SESSION["user_id"];
                
                $sql = "INSERT INTO products (user_id, name, description, price, stock, category, tags, discount_percent, image_url) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issdsssds", $user_id, $name, $description, $price, $stock, $category, $tags, $discount_percent, $image_url);
                
                if ($stmt->execute()) {
                    $product_id = $stmt->insert_id;
                    $response["success"] = true;
                    $response["message"] = "Product added successfully.";
                    $response["product_id"] = $product_id;
                } else {
                    $response["message"] = "Error adding product: " . $stmt->error;
                }
                
                $stmt->close();
            }
        }
        
        // Edit product
        else if ($admin_action === "edit_product") {
            $product_id = intval($_POST["product_id"] ?? 0);
            $name = sanitize_input($_POST["name"] ?? "");
            $description = sanitize_input($_POST["description"] ?? "");
            $price = floatval($_POST["price"] ?? 0);
            $stock = intval($_POST["stock"] ?? 0);
            $category = sanitize_input($_POST["category"] ?? "");
            $tags = sanitize_input($_POST["tags"] ?? "");
            $discount_percent = intval($_POST["discount_percent"] ?? 0);
            $image_url = sanitize_input($_POST["image_url"] ?? "");
            
            if ($product_id <= 0 || empty($name) || empty($description) || $price <= 0) {
                $response["message"] = "Please fill in all required fields.";
            } else {
                $sql = "UPDATE products SET name = ?, description = ?, price = ?, stock = ?, 
                        category = ?, tags = ?, discount_percent = ?, image_url = ? 
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssdsssisi", $name, $description, $price, $stock, $category, $tags, $discount_percent, $image_url, $product_id);
                
                if ($stmt->execute()) {
                    $response["success"] = true;
                    $response["message"] = "Product updated successfully.";
                } else {
                    $response["message"] = "Error updating product: " . $stmt->error;
                }
                
                $stmt->close();
            }
        }
        
        // Delete product
        else if ($admin_action === "delete_product") {
            $product_id = intval($_POST["product_id"] ?? 0);
            
            if ($product_id <= 0) {
                $response["message"] = "Invalid product ID.";
            } else {
                $sql = "DELETE FROM products WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $product_id);
                
                if ($stmt->execute()) {
                    $response["success"] = true;
                    $response["message"] = "Product deleted successfully.";
                } else {
                    $response["message"] = "Error deleting product: " . $stmt->error;
                }
                
                $stmt->close();
            }
        }
        
        // Invalid admin action
        else {
            $response["message"] = "Invalid admin action.";
        }
    }
    
    // Logout AJAX handler
    else if ($action === "logout") {
        session_destroy();
        $response["success"] = true;
        $response["message"] = "Logged out successfully.";
    }
    
    // Invalid action
    else {
        $response["message"] = "Invalid action.";
    }
    
    // Return JSON response
    header("Content-Type: application/json");
    echo json_encode($response);
    exit;
} else {
    // Redirect to home page if accessed directly
    redirect("index.php");
}
?>';

    file_put_contents('ajax_handler.php', $ajax_handler);
}
?>