<?php
require_once 'config.php';

// Check if AJAX request
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    $response = ['success' => false, 'message' => ''];
    
    $product_id = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);
    
    if ($product_id <= 0 || $quantity <= 0) {
        $response['message'] = 'Invalid product or quantity.';
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
            $current_quantity = isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id] : 0;
            
            if ($quantity + $current_quantity > $product['stock']) {
                $response['message'] = 'Not enough stock available.';
            } else {
                if (add_to_cart($product_id, $quantity)) {
                    $response['success'] = true;
                    $response['message'] = 'Product added to cart.';
                    $response['cart_count'] = get_cart_count();
                    $response['cart_total'] = number_format(get_cart_total(), 2);
                } else {
                    $response['message'] = 'Error adding product to cart.';
                }
            }
        } else {
            $response['message'] = 'Product not found.';
        }
        
        $stmt->close();
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
} else {
    // Redirect to home page if accessed directly
    redirect('index.php');
}
?>