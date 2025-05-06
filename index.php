<?php
require_once 'config.php';

// Get categories for filter
$categories_query = "SELECT DISTINCT category FROM products ORDER BY category";
$categories_result = $conn->query($categories_query);
$categories = [];

if ($categories_result && $categories_result->num_rows > 0) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}

// Check if there's a search parameter
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

// Check if there's a category parameter
$category = isset($_GET['category']) ? sanitize_input($_GET['category']) : 'all';

// Define page title
$page_title = $search ? "Search: $search" : ($category !== 'all' ? "Category: " . ucfirst($category) : "Home");
$page_title = get_page_title($page_title);

include_header($page_title);
?>

<!-- Main content -->
<main class="container mx-auto px-4 py-8">
    <?php if (empty($search) && $category === 'all'): ?>
    <!-- Hero Banner -->
    <div class="bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500 text-white py-16 px-4 rounded-xl mb-8">
        <div class="max-w-4xl mx-auto text-center">
            <h1 class="text-4xl md:text-6xl font-bold mb-4">Welcome to <?php echo $config['site_name']; ?></h1>
            <p class="text-xl md:text-2xl mb-8">Discover amazing products with our modern e-commerce platform</p>
            <a href="index.php?category=all" class="px-8 py-3 bg-white text-indigo-600 rounded-full font-bold hover:bg-gray-100 transition duration-300 inline-block">
                Shop Now
            </a>
        </div>
    </div>
    
    <!-- Featured Categories -->
    <div class="mb-12">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Shop by Category</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <?php
            $category_icons = [
                'electronics' => 'mobile-alt',
                'computers' => 'laptop',
                'audio' => 'headphones',
                'wearables' => 'watch',
                'photography' => 'camera',
                'home' => 'home'
            ];
            
            $category_colors = [
                'electronics' => 'from-blue-500 to-indigo-600',
                'computers' => 'from-green-500 to-teal-500',
                'audio' => 'from-yellow-500 to-orange-500',
                'wearables' => 'from-purple-500 to-pink-500',
                'photography' => 'from-red-500 to-pink-600',
                'home' => 'from-teal-500 to-cyan-500'
            ];
            
            foreach ($categories as $index => $cat):
                if ($index >= 4) break; // Only show first 4 categories
                $cat_lower = strtolower($cat);
                $icon = isset($category_icons[$cat_lower]) ? $category_icons[$cat_lower] : 'tag';
                $color = isset($category_colors[$cat_lower]) ? $category_colors[$cat_lower] : 'from-gray-500 to-gray-700';
            ?>
            <a href="index.php?category=<?php echo urlencode($cat); ?>" class="bg-gradient-to-br <?php echo $color; ?> p-6 rounded-lg text-white text-center transform hover:scale-105 transition duration-300">
                <i class="fas fa-<?php echo $icon; ?> text-4xl mb-4"></i>
                <h3 class="text-lg font-semibold"><?php echo ucfirst($cat); ?></h3>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Featured Products -->
    <div id="featured-products" class="mb-12">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Featured Products</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6" id="featured-products-grid">
            <!-- Featured products will be loaded here via AJAX -->
            <div class="flex justify-center items-center py-12 col-span-full">
                <div class="loader rounded-full border-4 border-gray-200 h-12 w-12"></div>
            </div>
        </div>
    </div>
    
    <!-- CTA Banner -->
    <div class="bg-gray-900 text-white py-16 px-4 rounded-xl mb-12">
        <div class="max-w-4xl mx-auto text-center">
            <h2 class="text-3xl font-bold mb-4">Join Our Newsletter</h2>
            <p class="text-gray-300 mb-8">Subscribe to get special offers, free giveaways, and once-in-a-lifetime deals.</p>
            <div class="flex max-w-md mx-auto">
                <input type="email" placeholder="Your email address" class="flex-1 px-4 py-3 rounded-l-md text-gray-900 focus:outline-none">
                <button class="bg-indigo-600 px-6 py-3 rounded-r-md hover:bg-indigo-700 transition duration-300">
                    Subscribe
                </button>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Products page -->
    <div class="flex flex-col md:flex-row space-y-6 md:space-y-0">
        <!-- Sidebar -->
        <div class="w-full md:w-64 flex-shrink-0 md:pr-6">
            <div class="bg-white p-6 rounded-lg shadow-sm mb-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Categories</h3>
                <div class="space-y-2">
                    <a href="index.php?category=all<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="category-pill block w-full text-left px-4 py-2 rounded-md text-sm <?php echo $category === 'all' ? 'active bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                        All Categories
                    </a>
                    <?php foreach ($categories as $cat): ?>
                    <a href="index.php?category=<?php echo urlencode($cat); ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" class="category-pill block w-full text-left px-4 py-2 rounded-md text-sm <?php echo $category === $cat ? 'active bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                        <?php echo ucfirst($cat); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Sort By</h3>
                <select id="sort-select" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="newest">Newest</option>
                    <option value="price_low">Price: Low to High</option>
                    <option value="price_high">Price: High to Low</option>
                    <option value="rating">Best Rating</option>
                    <option value="discount">Biggest Discount</option>
                </select>
            </div>
        </div>
        
        <!-- Products -->
        <div class="flex-1">
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
                    <h2 id="products-heading" class="text-2xl font-bold text-gray-800">
                        <?php
                        if (!empty($search)) {
                            echo "Search: \"" . htmlspecialchars($search) . "\"";
                        } else {
                            echo $category === 'all' ? "All Products" : "Category: " . ucfirst($category);
                        }
                        ?>
                    </h2>
                    <div id="products-count" class="text-gray-500 text-sm">Loading products...</div>
                </div>
                
                <div id="products-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Products will be loaded here via AJAX -->
                    <div class="flex justify-center items-center py-12 col-span-full">
                        <div class="loader rounded-full border-4 border-gray-200 h-12 w-12"></div>
                    </div>
                </div>
                
                <div id="pagination" class="mt-8 flex justify-center space-x-2">
                    <!-- Pagination will be generated here via AJAX -->
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</main>

<?php
include_footer();
?>

<script>
$(document).ready(function() {
    <?php if (empty($search) && $category === 'all'): ?>
    // Load featured products for homepage
    loadFeaturedProducts();
    <?php else: ?>
    // Load products for products page
    loadProducts(1, '<?php echo $category; ?>', '<?php echo $search; ?>', 'newest');
    
    // Sort products
    $('#sort-select').on('change', function() {
        const sort = $(this).val();
        loadProducts(1, '<?php echo $category; ?>', '<?php echo $search; ?>', sort);
    });
    <?php endif; ?>
    
    // Load featured products
    function loadFeaturedProducts() {
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_products',
                sort: 'discount',
                limit: 4
            },
            success: function(response) {
                if (response.success) {
                    const products = response.products;
                    
                    if (products.length === 0) {
                        $('#featured-products-grid').html('<p class="text-center col-span-full text-gray-500">No products found</p>');
                    } else {
                        let html = '';
                        
                        for (const product of products) {
                            html += generateProductCard(product);
                        }
                        
                        $('#featured-products-grid').html(html);
                    }
                }
            }
        });
    }
    
    // Load products
    function loadProducts(page, category, search, sort) {
        $('#products-grid').html('<div class="flex justify-center items-center py-12 col-span-full"><div class="loader rounded-full border-4 border-gray-200 h-12 w-12"></div></div>');
        $('#pagination').html('');
        
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_products',
                category: category,
                search: search,
                sort: sort,
                page: page
            },
            success: function(response) {
                if (response.success) {
                    const products = response.products;
                    
                    $('#products-count').text(`Showing ${products.length} of ${response.total} products`);
                    
                    if (products.length === 0) {
                        $('#products-grid').html('<p class="text-center col-span-full py-12 text-gray-500">No products found</p>');
                    } else {
                        let html = '';
                        
                        for (const product of products) {
                            html += generateProductCard(product);
                        }
                        
                        $('#products-grid').html(html);
                    }
                    
                    // Generate pagination
                    generatePagination(response.current_page, response.pages, category, search, sort);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error);
                $('#products-grid').html('<p class="text-center col-span-full py-12 text-red-500">Error loading products. Please try again.</p>');
            }
        });
    }
    
    // Generate product card
    function generateProductCard(product) {
        // Generate rating stars
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            if (i <= Math.floor(product.rating)) {
                stars += '<i class="fas fa-star"></i>';
            } else if (i === Math.ceil(product.rating) && product.rating % 1 !== 0) {
                stars += '<i class="fas fa-star-half-alt"></i>';
            } else {
                stars += '<i class="far fa-star"></i>';
            }
        }
        
        return `
            <div class="product-card bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-md transition duration-300" data-id="${product.id}">
                <div class="relative">
                    <img src="${product.image_url}" alt="${product.name}" class="w-full h-48 sm:h-56 object-cover">
                    
                    ${product.discount_percent > 0 ? 
                        `<div class="absolute top-2 right-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-md">
                            ${product.discount_percent}% OFF
                        </div>` : ''
                    }
                    
                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black to-transparent p-4">
                        <div class="flex justify-between items-end">
                            <div class="stars text-yellow-400 text-sm">
                                ${stars}
                            </div>
                            <button class="toggle-favorite w-8 h-8 flex items-center justify-center rounded-full ${product.is_favorite ? 'bg-red-100 text-red-500' : 'bg-gray-100 text-gray-400'} hover:bg-gray-200 transition duration-300" data-id="${product.id}">
                                <i class="${product.is_favorite ? 'fas' : 'far'} fa-heart"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2 line-clamp-1">${product.name}</h3>
                    <p class="text-gray-600 text-sm mb-4 line-clamp-2">${product.description.substring(0, 80)}${product.description.length > 80 ? '...' : ''}</p>
                    
                    <div class="flex justify-between items-center">
                        <div>
                            ${product.discount_percent > 0 ? 
                                `<div class="flex items-center">
                                    <span class="font-bold text-indigo-600 mr-2">$${product.formatted_discounted_price}</span>
                                    <span class="text-sm text-gray-500 line-through">$${product.formatted_price}</span>
                                </div>` : 
                                `<span class="font-bold text-indigo-600">$${product.formatted_price}</span>`
                            }
                        </div>
                        <div class="flex space-x-2">
                            <button class="quick-view w-8 h-8 bg-gray-100 text-gray-700 rounded-full flex items-center justify-center hover:bg-gray-200 transition duration-300" data-id="${product.id}">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="add-to-cart w-8 h-8 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center hover:bg-indigo-200 transition duration-300" data-id="${product.id}">
                                <i class="fas fa-shopping-cart"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Generate pagination - FIXED VERSION
    function generatePagination(currentPage, totalPages, category, search, sort) {
        // Bail early if no pages to display
        if (totalPages <= 1) {
            $('#pagination').html('');
            return;
        }
        
        let html = '';
        
        // Previous button
        html += `
            <button class="pagination-link px-3 py-1 rounded-md ${currentPage === 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}" ${currentPage === 1 ? 'disabled' : ''} data-page="${currentPage - 1}">
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
                <button class="pagination-link px-3 py-1 rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200" data-page="1">1</button>
            `;
            
            if (startPage > 2) {
                html += `<span class="px-2 py-1">...</span>`;
            }
        }
        
        // Page numbers
        for (let i = startPage; i <= endPage; i++) {
            html += `
                <button class="pagination-link px-3 py-1 rounded-md ${i === currentPage ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}" data-page="${i}">${i}</button>
            `;
        }
        
        // Last page
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                html += `<span class="px-2 py-1">...</span>`;
            }
            
            html += `
                <button class="pagination-link px-3 py-1 rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200" data-page="${totalPages}">${totalPages}</button>
            `;
        }
        
        // Next button
        html += `
            <button class="pagination-link px-3 py-1 rounded-md ${currentPage === totalPages ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}" ${currentPage === totalPages ? 'disabled' : ''} data-page="${currentPage + 1}">
                <i class="fas fa-chevron-right"></i>
            </button>
        `;
        
        $('#pagination').html(html);
        
        // Attach event handlers to pagination links
        $('.pagination-link').on('click', function(e) {
            e.preventDefault();
            
            if ($(this).attr('disabled')) {
                return;
            }
            
            const page = parseInt($(this).data('page'));
            
            if (!isNaN(page)) {
                loadProducts(page, category, search, sort);
                
                // Scroll to top of products
                $('html, body').animate({
                    scrollTop: $('#products-heading').offset().top - 100
                }, 300);
            }
        });
    }
});
</script>