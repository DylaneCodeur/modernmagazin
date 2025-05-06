<?php
require_once 'config.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    set_message('You do not have permission to access the admin panel.', 'danger');
    redirect('index.php');
}

// Define page title
$page_title = get_page_title('admin');

// Extra head content for this page
$extra_head = '
<style>
    .admin-sidebar {
        transition: all 0.3s ease;
    }
    
    @media (max-width: 768px) {
        .admin-sidebar {
            transform: translateX(-100%);
        }
        
        .admin-sidebar.active {
            transform: translateX(0);
        }
    }
    
    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    
    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: .7;
        }
    }
</style>
';

include_header($page_title, $extra_head);

// Get all categories
$categories_query = "SELECT DISTINCT category FROM products ORDER BY category";
$categories_result = $conn->query($categories_query);
$categories = [];

if ($categories_result->num_rows > 0) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}
?>

<!-- Main content -->
<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row">
        <!-- Sidebar -->
        <div id="admin-sidebar" class="admin-sidebar w-full md:w-64 bg-white shadow-md md:shadow-none p-6 md:mr-6 rounded-lg mb-6 md:mb-0">
            <div class="flex justify-between items-center mb-8 md:mb-10">
                <h2 class="text-xl font-bold text-gray-800">Admin Panel</h2>
                <button id="close-sidebar" class="md:hidden text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <nav>
                <ul class="space-y-2">
                    <li>
                        <a href="#dashboard" class="admin-nav-link flex items-center p-2 rounded-md text-gray-700 hover:bg-indigo-100 hover:text-indigo-600 transition duration-300">
                            <i class="fas fa-tachometer-alt w-6"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="#products" class="admin-nav-link flex items-center p-2 rounded-md text-gray-700 hover:bg-indigo-100 hover:text-indigo-600 transition duration-300">
                            <i class="fas fa-box w-6"></i>
                            <span>Products</span>
                        </a>
                    </li>
                    <li>
                        <a href="#add-product" class="admin-nav-link flex items-center p-2 rounded-md text-gray-700 hover:bg-indigo-100 hover:text-indigo-600 transition duration-300">
                            <i class="fas fa-plus-circle w-6"></i>
                            <span>Add Product</span>
                        </a>
                    </li>
                    <li>
                        <a href="#orders" class="admin-nav-link flex items-center p-2 rounded-md text-gray-700 hover:bg-indigo-100 hover:text-indigo-600 transition duration-300">
                            <i class="fas fa-shopping-cart w-6"></i>
                            <span>Orders</span>
                        </a>
                    </li>
                    <li>
                        <a href="#users" class="admin-nav-link flex items-center p-2 rounded-md text-gray-700 hover:bg-indigo-100 hover:text-indigo-600 transition duration-300">
                            <i class="fas fa-users w-6"></i>
                            <span>Users</span>
                        </a>
                    </li>
                    <li>
                        <a href="#settings" class="admin-nav-link flex items-center p-2 rounded-md text-gray-700 hover:bg-indigo-100 hover:text-indigo-600 transition duration-300">
                            <i class="fas fa-cog w-6"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                    <li class="border-t pt-2 mt-2">
                        <a href="index.php" class="flex items-center p-2 rounded-md text-gray-700 hover:bg-indigo-100 hover:text-indigo-600 transition duration-300">
                            <i class="fas fa-home w-6"></i>
                            <span>Back to Site</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        
        <!-- Mobile Sidebar Toggle -->
        <div class="md:hidden fixed bottom-4 right-4 z-10">
            <button id="toggle-sidebar" class="bg-indigo-600 text-white rounded-full w-12 h-12 flex items-center justify-center shadow-lg">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <!-- Main Content Area -->
        <div class="flex-1">
            <!-- Dashboard Section -->
            <section id="dashboard-section" class="admin-section bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Dashboard</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg p-6 text-white">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm uppercase tracking-wider">Total Products</p>
                                <h3 class="text-3xl font-bold mt-2">
                                    <span id="total-products">...</span>
                                </h3>
                            </div>
                            <div class="bg-white bg-opacity-30 rounded-full p-3">
                                <i class="fas fa-box text-xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-green-400 to-green-600 rounded-lg p-6 text-white">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm uppercase tracking-wider">Total Orders</p>
                                <h3 class="text-3xl font-bold mt-2">
                                    <span id="total-orders">0</span>
                                </h3>
                            </div>
                            <div class="bg-white bg-opacity-30 rounded-full p-3">
                                <i class="fas fa-shopping-cart text-xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-purple-400 to-purple-600 rounded-lg p-6 text-white">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm uppercase tracking-wider">Total Users</p>
                                <h3 class="text-3xl font-bold mt-2">
                                    <span id="total-users">...</span>
                                </h3>
                            </div>
                            <div class="bg-white bg-opacity-30 rounded-full p-3">
                                <i class="fas fa-users text-xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-lg p-6 text-white">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm uppercase tracking-wider">Total Revenue</p>
                                <h3 class="text-3xl font-bold mt-2">$0.00</h3>
                            </div>
                            <div class="bg-white bg-opacity-30 rounded-full p-3">
                                <i class="fas fa-dollar-sign text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Activity</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Product Added</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Admin</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Just now</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Added 'Smartphone Pro Max'</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">User Registered</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">user123</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1 hour ago</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">New user registration</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
            
            <!-- Products Section -->
            <section id="products-section" class="admin-section bg-white rounded-lg shadow-md p-6 mb-6 hidden">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Products</h2>
                    <div class="flex space-x-2">
                        <div class="relative">
                            <input type="text" id="product-search" class="pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Search products">
                            <div class="absolute left-3 top-2.5 text-gray-400">
                                <i class="fas fa-search"></i>
                            </div>
                        </div>
                        <select id="product-category-filter" class="border border-gray-300 rounded-md py-2 px-4 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars(ucfirst($category)); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button id="add-product-btn" class="bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 transition duration-300">
                            <i class="fas fa-plus mr-2"></i> Add Product
                        </button>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" id="products-table">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <!-- Products will be loaded here via AJAX -->
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center">
                                    <div class="loader rounded-full border-4 border-gray-200 h-12 w-12 mx-auto"></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div id="products-pagination" class="mt-6 flex justify-center">
                    <!-- Pagination will be generated here via AJAX -->
                </div>
            </section>
            
            <!-- Add/Edit Product Section -->
            
<!-- Add/Edit Product Section avec design amélioré -->
<section id="add-product-section" class="admin-section bg-white rounded-lg shadow-md p-6 mb-6 hidden">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">
        <span id="product-form-title">Add New Product</span>
    </h2>
    
    <div id="product-form-error" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 hidden" role="alert">
        <p class="font-medium">Error</p>
        <p id="product-form-error-message"></p>
    </div>
    
    <div id="product-form-success" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 hidden" role="alert">
        <p class="font-medium">Success</p>
        <p id="product-form-success-message"></p>
    </div>
    
    <form id="product-form" class="space-y-6">
        <input type="hidden" id="product-id" name="product_id" value="0">
        <input type="hidden" name="action" value="admin_action">
        <input type="hidden" name="admin_action" value="add_product">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="product-name" class="block text-sm font-medium text-gray-700 mb-1">Product Name*</label>
                <input type="text" id="product-name" name="name" class="block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm px-4 py-2" required>
            </div>
            
            <div>
                <label for="product-price" class="block text-sm font-medium text-gray-700 mb-1">Price*</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500">$</span>
                    </div>
                    <input type="number" id="product-price" name="price" min="0.01" step="0.01" class="block w-full pl-7 pr-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                </div>
            </div>
            
            <div>
                <label for="product-category" class="block text-sm font-medium text-gray-700 mb-1">Category*</label>
                <div class="flex space-x-2">
                    <select id="product-category" name="category" class="block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm px-4 py-2">
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars(ucfirst($category)); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" id="add-category-btn" class="bg-gray-200 text-gray-700 py-2 px-3 rounded-md hover:bg-gray-300 transition duration-300 flex items-center justify-center">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <div id="new-category-container" class="mt-2 hidden">
                    <input type="text" id="new-category" class="block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm px-4 py-2" placeholder="Enter new category">
                </div>
            </div>
            
            <div>
                <label for="product-stock" class="block text-sm font-medium text-gray-700 mb-1">Stock*</label>
                <input type="number" id="product-stock" name="stock" min="0" class="block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm px-4 py-2" value="1" required>
            </div>
            
            <div>
                <label for="product-tags" class="block text-sm font-medium text-gray-700 mb-1">Tags (comma separated)</label>
                <input type="text" id="product-tags" name="tags" class="block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm px-4 py-2" placeholder="electronics, gadget, modern">
            </div>
            
            <div>
                <label for="product-discount" class="block text-sm font-medium text-gray-700 mb-1">Discount (%)</label>
                <input type="number" id="product-discount" name="discount_percent" min="0" max="100" class="block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm px-4 py-2" value="0">
            </div>
            
            <div class="col-span-1 md:col-span-2">
                <label for="product-image" class="block text-sm font-medium text-gray-700 mb-1">Image URL*</label>
                <input type="url" id="product-image" name="image_url" class="block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm px-4 py-2" placeholder="https://example.com/image.jpg" required>
                <p class="mt-1 text-sm text-gray-500">Enter a URL or use a placeholder image</p>
                
                <div class="mt-3 flex items-center">
                    <button type="button" id="random-image-btn" class="bg-indigo-100 text-indigo-700 py-2 px-4 rounded-md hover:bg-indigo-200 transition duration-300 text-sm flex items-center">
                        <i class="fas fa-image mr-2"></i> Generate Random Image
                    </button>
                    <div id="image-preview" class="ml-4 w-20 h-20 overflow-hidden rounded-md border border-gray-300 hidden">
                        <img id="preview-img" src="" alt="Preview" class="w-full h-full object-cover">
                    </div>
                </div>
            </div>
            
            <div class="col-span-1 md:col-span-2">
                <label for="product-description" class="block text-sm font-medium text-gray-700 mb-1">Description*</label>
                <textarea id="product-description" name="description" rows="5" class="block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm px-4 py-2" required></textarea>
                <p class="mt-1 text-sm text-gray-500">* Required fields</p>
            </div>
        </div>
        
        <div class="pt-4 border-t border-gray-200 flex justify-end space-x-4">
            <button type="button" id="cancel-product-btn" class="bg-white text-gray-700 py-2 px-6 border border-gray-300 rounded-md hover:bg-gray-50 transition duration-300 flex items-center">
                <i class="fas fa-times mr-2"></i> Cancel
            </button>
            <button type="submit" id="submit-product-btn" class="bg-indigo-600 text-white py-2 px-6 rounded-md hover:bg-indigo-700 transition duration-300 flex items-center">
                <i class="fas fa-save mr-2"></i> <span id="submit-btn-text">Save Product</span>
            </button>
        </div>
    </form>
</section>
            
            <!-- Orders Section -->
            <section id="orders-section" class="admin-section bg-white rounded-lg shadow-md p-6 mb-6 hidden">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Orders</h2>
                
                <div class="flex justify-between items-center mb-6">
                    <div class="flex space-x-2">
                        <div class="relative">
                            <input type="text" id="order-search" class="pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Search orders">
                            <div class="absolute left-3 top-2.5 text-gray-400">
                                <i class="fas fa-search"></i>
                            </div>
                        </div>
                        <select id="order-status-filter" class="border border-gray-300 rounded-md py-2 px-4 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" id="orders-table">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    No orders found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
            
            <!-- Users Section -->
            <section id="users-section" class="admin-section bg-white rounded-lg shadow-md p-6 mb-6 hidden">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Users</h2>
                
                <div class="flex justify-between items-center mb-6">
                    <div class="flex space-x-2">
                        <div class="relative">
                            <input type="text" id="user-search" class="pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Search users">
                            <div class="absolute left-3 top-2.5 text-gray-400">
                                <i class="fas fa-search"></i>
                            </div>
                        </div>
                        <select id="user-role-filter" class="border border-gray-300 rounded-md py-2 px-4 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">All Roles</option>
                            <option value="1">Admin</option>
                            <option value="0">User</option>
                        </select>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" id="users-table">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="users-body">
                            <!-- Users will be loaded here via AJAX -->
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center">
                                    <div class="loader rounded-full border-4 border-gray-200 h-12 w-12 mx-auto"></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div id="users-pagination" class="mt-6 flex justify-center">
                    <!-- Pagination will be generated here via AJAX -->
                </div>
            </section>
            
            <!-- Settings Section -->
            <section id="settings-section" class="admin-section bg-white rounded-lg shadow-md p-6 mb-6 hidden">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Settings</h2>
                
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">General Settings</h3>
                    <form id="general-settings-form" class="space-y-4">
                        <div>
                            <label for="site-name" class="block text-sm font-medium text-gray-700 mb-1">Site Name</label>
                            <input type="text" id="site-name" name="site_name" value="<?php echo htmlspecialchars($config['site_name']); ?>" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        
                        <div>
                            <label for="site-currency" class="block text-sm font-medium text-gray-700 mb-1">Currency Symbol</label>
                            <input type="text" id="site-currency" name="currency" value="<?php echo htmlspecialchars($config['currency']); ?>" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        
                        <div>
                            <label for="items-per-page" class="block text-sm font-medium text-gray-700 mb-1">Items Per Page</label>
                            <input type="number" id="items-per-page" name="items_per_page" value="<?php echo (int)$config['items_per_page']; ?>" min="1" max="100" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        
                        <div>
                            <button type="submit" class="bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 transition duration-300">
                                <i class="fas fa-save mr-2"></i> Save Settings
                            </button>
                        </div>
                    </form>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">System Information</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">PHP Version</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo phpversion(); ?></td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">MySQL Version</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $conn->server_info; ?></td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Server Software</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">System Version</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($config['version']); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
    <div class="fixed inset-0 bg-black bg-opacity-50" id="delete-modal-backdrop"></div>
    <div class="relative bg-white rounded-lg shadow-lg w-full max-w-md p-6 fade-in">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-bold text-gray-800">Confirm Deletion</h3>
            <button id="close-delete-modal" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <p class="text-gray-600 mb-6">Are you sure you want to delete this item? This action cannot be undone.</p>
        
        <div class="flex justify-end space-x-4">
            <button id="cancel-delete" class="bg-gray-200 text-gray-700 py-2 px-4 rounded-md hover:bg-gray-300 transition duration-300">
                Cancel
            </button>
            <button id="confirm-delete" class="bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700 transition duration-300">
                Delete
            </button>
        </div>
    </div>
</div>

<?php
include_footer();
?>

<script>
$(document).ready(function() {
    // Mobile sidebar toggle
    $('#toggle-sidebar').on('click', function() {
        $('#admin-sidebar').toggleClass('active');
    });
    
    $('#close-sidebar').on('click', function() {
        $('#admin-sidebar').removeClass('active');
    });
    
    // Navigation links
    $('.admin-nav-link').on('click', function(e) {
        e.preventDefault();
        
        const target = $(this).attr('href').replace('#', '');
        
        // Hide all sections
        $('.admin-section').addClass('hidden');
        
        // Show target section
        $(`#${target}-section`).removeClass('hidden');
        
        // Close mobile sidebar
        $('#admin-sidebar').removeClass('active');
        
        // Load section data if needed
        if (target === 'products') {
            loadProducts();
        } else if (target === 'users') {
            loadUsers();
        } else if (target === 'dashboard') {
            loadDashboardData();
        }
    });
    
    // Show dashboard by default
    loadDashboardData();
    
    // Add product button
    $('#add-product-btn').on('click', function() {
        // Reset form
        $('#product-form')[0].reset();
        $('#product-id').val(0);
        $('#product-form-title').text('Add New Product');
        $('input[name="admin_action"]').val('add_product');
        
        // Hide error and success messages
        $('#product-form-error, #product-form-success').addClass('hidden');
        
        // Show add product section
        $('.admin-section').addClass('hidden');
        $('#add-product-section').removeClass('hidden');
    });
    
    // Cancel product button
    $('#cancel-product-btn').on('click', function() {
        // Show products section
        $('.admin-section').addClass('hidden');
        $('#products-section').removeClass('hidden');
        
        // Load products
        loadProducts();
    });
    
    // Add category button
    $('#add-category-btn').on('click', function() {
        $('#new-category-container').toggleClass('hidden');
        
        if (!$('#new-category-container').hasClass('hidden')) {
            $('#new-category').focus();
        }
    });
    
    // New category input
    $('#new-category').on('keyup', function(e) {
        if (e.key === 'Enter') {
            const newCategory = $(this).val().trim().toLowerCase();
            
            if (newCategory) {
                // Check if category already exists
                let exists = false;
                
                $('#product-category option').each(function() {
                    if ($(this).val().toLowerCase() === newCategory) {
                        exists = true;
                        return false;
                    }
                });
                
                if (!exists) {
                    // Add new category
                    $('#product-category').append(`<option value="${newCategory}">${newCategory.charAt(0).toUpperCase() + newCategory.slice(1)}</option>`);
                    
                    // Select new category
                    $('#product-category').val(newCategory);
                    
                    // Hide new category input
                    $('#new-category-container').addClass('hidden');
                    $(this).val('');
                }
            }
        }
    });
    
    // Random image button
    $('#random-image-btn').on('click', function() {
        const randomId = Math.floor(Math.random() * 1000);
        const imageUrl = `https://picsum.photos/id/${randomId}/800/800`;
        
        $('#product-image').val(imageUrl);
        $('#preview-img').attr('src', imageUrl);
        $('#image-preview').removeClass('hidden');
    });
    
    // Image URL input
    $('#product-image').on('input', function() {
        const imageUrl = $(this).val().trim();
        
        if (imageUrl) {
            $('#preview-img').attr('src', imageUrl);
            $('#image-preview').removeClass('hidden');
        } else {
            $('#image-preview').addClass('hidden');
        }
    });
    
    // Product form submit
    $('#product-form').on('submit', function(e) {
    e.preventDefault();
    
    // Changez 'const' à 'let':
    let formData = $(this).serialize();
    
    // Maintenant vous pouvez modifier formData sans erreur:
    formData += '&action=admin_action';
        
        // Submit form via AJAX
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            dataType: 'json',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $('#product-form-success-message').text(response.message);
                    $('#product-form-success').removeClass('hidden');
                    
                    // Reset form after 2 seconds
                    setTimeout(function() {
                        // Show products section
                        $('.admin-section').addClass('hidden');
                        $('#products-section').removeClass('hidden');
                        
                        // Load products
                        loadProducts();
                    }, 2000);
                } else {
                    // Show error message
                    $('#product-form-error-message').text(response.message);
                    $('#product-form-error').removeClass('hidden');
                    
                    // Re-enable button
                    $('#submit-product-btn').prop('disabled', false).html('<i class="fas fa-save mr-2"></i> Save Product');
                }
            },
            error: function() {
                // Show error message
                $('#product-form-error-message').text('An error occurred. Please try again.');
                $('#product-form-error').removeClass('hidden');
                
                // Re-enable button
                $('#submit-product-btn').prop('disabled', false).html('<i class="fas fa-save mr-2"></i> Save Product');
            }
        });
    });
    
    // Load dashboard data
    function loadDashboardData() {
        // Load products count
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_products',
                limit: 1
            },
            success: function(response) {
                if (response.success) {
                    $('#total-products').text(response.total);
                }
            }
        });
        
        // Load users count
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_users',
                limit: 1
            },
            success: function(response) {
                if (response.success) {
                    $('#total-users').text(response.total);
                }
            }
        });
    }
    
    // Load products
    function loadProducts(page = 1, search = '', category = '') {
        $('#products-table tbody').html('<tr><td colspan="7" class="px-6 py-4 text-center"><div class="loader rounded-full border-4 border-gray-200 h-12 w-12 mx-auto"></div></td></tr>');
        $('#products-pagination').html('');
        
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_products',
                page: page,
                search: search,
                category: category
            },
            success: function(response) {
                if (response.success) {
                    const products = response.products;
                    
                    if (products.length === 0) {
                        $('#products-table tbody').html('<tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">No products found.</td></tr>');
                    } else {
                        let html = '';
                        
                        for (const product of products) {
                            html += `
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${product.id}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <img src="${product.image_url}" alt="${product.name}" class="h-10 w-10 rounded-full object-cover">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${product.name}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${product.category}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        ${product.discount_percent > 0 ? 
                                            `<span class="line-through text-gray-400">$${product.formatted_price}</span>
                                            <span class="text-indigo-600">$${product.formatted_discounted_price}</span>` : 
                                            `<span>$${product.formatted_price}</span>`
                                        }
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${product.stock}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button class="edit-product text-indigo-600 hover:text-indigo-900 mr-3" data-id="${product.id}">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="delete-product text-red-600 hover:text-red-900" data-id="${product.id}">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            `;
                        }
                        
                        $('#products-table tbody').html(html);
                        
                        // Generate pagination
                        generatePagination(response.current_page, response.pages, search, category, 'products');
                    }
                }
            }
        });
    }
    
    // Load users
    function loadUsers(page = 1, search = '', role = '') {
        $('#users-body').html('<tr><td colspan="6" class="px-6 py-4 text-center"><div class="loader rounded-full border-4 border-gray-200 h-12 w-12 mx-auto"></div></td></tr>');
        $('#users-pagination').html('');
        
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_users',
                page: page,
                search: search,
                role: role
            },
            success: function(response) {
                if (response.success) {
                    const users = response.users;
                    
                    if (users.length === 0) {
                        $('#users-body').html('<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No users found.</td></tr>');
                    } else {
                        let html = '';
                        
                        for (const user of users) {
                            html += `
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${user.id}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${user.username}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${user.email}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${user.is_admin == 1 ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800'}">
                                            ${user.is_admin == 1 ? 'Admin' : 'User'}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${user.created_at}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button class="edit-user text-indigo-600 hover:text-indigo-900 mr-3" data-id="${user.id}">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="delete-user text-red-600 hover:text-red-900" data-id="${user.id}">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            `;
                        }
                        
                        $('#users-body').html(html);
                        
                        // Generate pagination
                        generatePagination(response.current_page, response.pages, search, role, 'users');
                    }
                }
            }
        });
    }
    
    // Generate pagination
    function generatePagination(currentPage, totalPages, search, filter, type) {
        if (totalPages <= 1) {
            $(`#${type}-pagination`).html('');
            return;
        }
        
        let html = '';
        
        // Previous button
        html += `
            <button class="${type}-pagination-link px-3 py-1 rounded-md ${currentPage === 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}" ${currentPage === 1 ? 'disabled' : ''} data-page="${currentPage - 1}" data-search="${search}" data-filter="${filter}">
                <i class="fas fa-chevron-left"></i>
            </button>
        `;
        
        // Page numbers
        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
        
        if (endPage - startPage + 1 < maxVisiblePages) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }
        
        // First page
        if (startPage > 1) {
            html += `
                <button class="${type}-pagination-link px-3 py-1 rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200" data-page="1" data-search="${search}" data-filter="${filter}">1</button>
            `;
            
            if (startPage > 2) {
                html += `<span class="px-2 py-1">...</span>`;
            }
        }
        
        // Page numbers
        for (let i = startPage; i <= endPage; i++) {
            html += `
                <button class="${type}-pagination-link px-3 py-1 rounded-md ${i === currentPage ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}" data-page="${i}" data-search="${search}" data-filter="${filter}">${i}</button>
            `;
        }
        
        // Last page
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                html += `<span class="px-2 py-1">...</span>`;
            }
            
            html += `
                <button class="${type}-pagination-link px-3 py-1 rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200" data-page="${totalPages}" data-search="${search}" data-filter="${filter}">${totalPages}</button>
            `;
        }
        
        // Next button
        html += `
            <button class="${type}-pagination-link px-3 py-1 rounded-md ${currentPage === totalPages ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}" ${currentPage === totalPages ? 'disabled' : ''} data-page="${currentPage + 1}" data-search="${search}" data-filter="${filter}">
                <i class="fas fa-chevron-right"></i>
            </button>
        `;
        
        $(`#${type}-pagination`).html(html);
        
        // Pagination click handler
        $(`.${type}-pagination-link`).on('click', function() {
            if (!$(this).prop('disabled')) {
                const page = $(this).data('page');
                const search = $(this).data('search');
                const filter = $(this).data('filter');
                
                if (type === 'products') {
                    loadProducts(page, search, filter);
                } else if (type === 'users') {
                    loadUsers(page, search, filter);
                }
            }
        });
    }
    
    // Edit product
    $(document).on('click', '.edit-product', function() {
        const productId = $(this).data('id');
        
        // Show loading
        $('.admin-section').addClass('hidden');
        $('#add-product-section').removeClass('hidden');
        $('#product-form-title').text('Edit Product');
        $('input[name="admin_action"]').val('edit_product');
        
        // Reset form
        $('#product-form')[0].reset();
        
        // Hide error and success messages
        $('#product-form-error, #product-form-success').addClass('hidden');
        
        // Show loading in the form
        $('#submit-product-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Loading...');
        
        // Get product data
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'quick_view',
                product_id: productId
            },
            success: function(response) {
                if (response.success) {
                    const product = response.product;
                    
                    // Set form values
                    $('#product-id').val(product.id);
                    $('#product-name').val(product.name);
                    $('#product-description').val(product.description);
                    $('#product-price').val(product.price);
                    $('#product-stock').val(product.stock);
                    $('#product-category').val(product.category);
                    $('#product-tags').val(product.tags);
                    $('#product-discount').val(product.discount_percent);
                    $('#product-image').val(product.image_url);
                    
                    // Show image preview
                    $('#preview-img').attr('src', product.image_url);
                    $('#image-preview').removeClass('hidden');
                    
                    // Re-enable button
                    $('#submit-product-btn').prop('disabled', false).html('<i class="fas fa-save mr-2"></i> Update Product');
                } else {
                    // Show error
                    $('#product-form-error-message').text(response.message);
                    $('#product-form-error').removeClass('hidden');
                    
                    // Re-enable button
                    $('#submit-product-btn').prop('disabled', false).html('<i class="fas fa-save mr-2"></i> Save Product');
                }
            },
            error: function() {
                // Show error
                $('#product-form-error-message').text('An error occurred. Please try again.');
                $('#product-form-error').removeClass('hidden');
                
                // Re-enable button
                $('#submit-product-btn').prop('disabled', false).html('<i class="fas fa-save mr-2"></i> Save Product');
            }
        });
    });
    
    // Delete product
    $(document).on('click', '.delete-product', function() {
        const productId = $(this).data('id');
        
        // Show confirmation modal
        $('#delete-modal').removeClass('hidden');
        
        // Set delete button data
        $('#confirm-delete').data('id', productId).data('type', 'product');
    });
    
    // Close delete modal
    $('#close-delete-modal, #delete-modal-backdrop, #cancel-delete').on('click', function() {
        $('#delete-modal').addClass('hidden');
    });
    
    // Confirm delete
    $('#confirm-delete').on('click', function() {
        const itemId = $(this).data('id');
        const itemType = $(this).data('type');
        
        // Disable button and show loading
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Deleting...');
        
        if (itemType === 'product') {
            // Delete product
            $.ajax({
                url: 'ajax_handler.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'admin_action',
                    admin_action: 'delete_product',
                    product_id: itemId
                },
                success: function(response) {
                    if (response.success) {
                        // Close modal
                        $('#delete-modal').addClass('hidden');
                        
                        // Reload products
                        loadProducts();
                        
                        // Show toast
                        showToast('success', response.message);
                    } else {
                        // Show error
                        showToast('error', response.message);
                        
                        // Re-enable button
                        $('#confirm-delete').prop('disabled', false).text('Delete');
                    }
                },
                error: function() {
                    // Show error
                    showToast('error', 'An error occurred. Please try again.');
                    
                    // Re-enable button
                    $('#confirm-delete').prop('disabled', false).text('Delete');
                }
            });
        }
    });
    
    // Product search
    $('#product-search').on('keyup', function(e) {
        if (e.key === 'Enter') {
            const searchTerm = $(this).val().trim();
            const category = $('#product-category-filter').val();
            
            loadProducts(1, searchTerm, category);
        }
    });
    
    // Product category filter
    $('#product-category-filter').on('change', function() {
        const category = $(this).val();
        const searchTerm = $('#product-search').val().trim();
        
        loadProducts(1, searchTerm, category);
    });
    
    // User search
    $('#user-search').on('keyup', function(e) {
        if (e.key === 'Enter') {
            const searchTerm = $(this).val().trim();
            const role = $('#user-role-filter').val();
            
            loadUsers(1, searchTerm, role);
        }
    });
    
    // User role filter
    $('#user-role-filter').on('change', function() {
        const role = $(this).val();
        const searchTerm = $('#user-search').val().trim();
        
        loadUsers(1, searchTerm, role);
    });
    
    // General settings form
    $('#general-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        // Get form data
        const siteName = $('#site-name').val();
        const currency = $('#site-currency').val();
        const itemsPerPage = $('#items-per-page').val();
        
        // Show toast
        showToast('success', 'Settings saved successfully.');
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