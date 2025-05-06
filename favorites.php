<?php
require_once 'config.php';

// Check if user is logged in
if (!is_logged_in()) {
    set_message('You must be logged in to view your favorites.', 'error');
    redirect('login.php');
}

// Define page title
$page_title = get_page_title('favorites');

include_header($page_title);
?>

<!-- Main content -->
<main class="container mx-auto px-4 py-8">
    <div class="bg-white p-6 rounded-lg shadow-md">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">My Favorites</h1>
            <a href="index.php" class="bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 transition duration-300">
                <i class="fas fa-arrow-left mr-2"></i> Back to Shopping
            </a>
        </div>
        
        <div id="favorites-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <!-- Favorites will be loaded here via AJAX -->
            <div class="flex justify-center items-center py-12 col-span-full">
                <div class="loader rounded-full border-4 border-gray-200 h-12 w-12"></div>
            </div>
        </div>
    </div>
</main>

<?php
include_footer();
?>

<script>
$(document).ready(function() {
    // Load favorites
    loadFavorites();
    
    function loadFavorites() {
        $.ajax({
            url: 'ajax_handler.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_products',
                favorites: true
            },
            success: function(response) {
                if (response.success) {
                    const products = response.products;
                    
                    if (products.length === 0) {
                        $('#favorites-grid').html(`
                            <div class="col-span-full text-center py-12">
                                <i class="far fa-heart text-5xl text-gray-300 mb-4"></i>
                                <p class="text-gray-500">You don't have any favorites yet</p>
                                <a href="index.php" class="mt-4 inline-block bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 transition duration-300">
                                    Start Shopping
                                </a>
                            </div>
                        `);
                    } else {
                        let html = '';
                        
                        for (const product of products) {
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
                            
                            html += `
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
                                                <button class="toggle-favorite w-8 h-8 flex items-center justify-center rounded-full bg-red-100 text-red-500 hover:bg-gray-200 transition duration-300" data-id="${product.id}">
                                                    <i class="fas fa-heart"></i>
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
                        
                        $('#favorites-grid').html(html);
                    }
                }
            }
        });
    }
});
</script>