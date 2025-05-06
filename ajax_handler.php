<?php
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
    
    // Get total count WITHOUT pagination parameters
    if (empty($param_types)) {
        // No parameters - execute simple query
        $count_result = $conn->query($count_query);
        $total_count = $count_result->fetch_assoc()["total"];
    } else {
        // With parameters - use prepared statement
        $count_stmt = $conn->prepare($count_query);
        $count_stmt->bind_param($param_types, ...$params);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $total_count = $count_result->fetch_assoc()["total"];
        $count_stmt->close();
    }
    
    // Add pagination to the main query
    $query .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $param_types .= "ii";
    
    // Get products with pagination
    $products = [];
    $stmt = $conn->prepare($query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
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


            // poıur analyser plutart la gestıon des recuperation

            /*
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
            */
        }
    }
    
    $stmt->close();
    
    $response["products"] = $products;
    $response["total"] = $total_count;
    $response["pages"] = ceil($total_count / $limit);
    $response["current_page"] = $page;
}
    // Get users AJAX handler
    else if ($action === "get_users" && is_admin()) {
        $response["success"] = true;
        $response["users"] = [];
        
        $search = sanitize_input($_POST["search"] ?? "");
        $role = sanitize_input($_POST["role"] ?? "");
        $page = (int)($_POST["page"] ?? 1);
        $limit = (int)($_POST["limit"] ?? 10);
        $offset = ($page - 1) * $limit;
        
        // Build query
        $query = "SELECT * FROM users WHERE 1=1";
        $count_query = "SELECT COUNT(*) as total FROM users WHERE 1=1";
        $params = [];
        $param_types = "";
        
        // Add role filter
        if ($role !== "") {
            $query .= " AND is_admin = ?";
            $count_query .= " AND is_admin = ?";
            $params[] = $role;
            $param_types .= "i";
        }
        
        // Add search filter
        if (!empty($search)) {
            $search_term = "%" . $search . "%";
            $query .= " AND (username LIKE ? OR email LIKE ?)";
            $count_query .= " AND (username LIKE ? OR email LIKE ?)";
            $params[] = $search_term;
            $params[] = $search_term;
            $param_types .= "ss";
        }
        
        // Add sorting
        $query .= " ORDER BY id ASC";
        
        // Add pagination
        $query .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $param_types .= "ii";
        
        // Get total count - FIX HERE: Make sure param_types is not empty
        $count_stmt = $conn->prepare($count_query);
        if (!empty($param_types) && !empty($params)) {
            // For count query, we don't need the limit and offset params
            $count_param_types = substr($param_types, 0, -2);
            if (!empty($count_param_types)) {
                $count_stmt->bind_param($count_param_types, ...array_slice($params, 0, -2));
            }
        }
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $total_count = $count_result->fetch_assoc()["total"];
        $count_stmt->close();
        
        // Get users - FIX HERE: Make sure param_types is not empty
        $stmt = $conn->prepare($query);
        if (!empty($param_types) && !empty($params)) {
            $stmt->bind_param($param_types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $users = [];
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Remove password from response
                unset($row["password"]);
                $users[] = $row;
            }
        }
        
        $stmt->close();
        
        $response["users"] = $users;
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
                $stmt->bind_param("issdssiss", $user_id, $name, $description, $price, $stock, $category, $tags, $discount_percent, $image_url);
                
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
                $stmt->bind_param("ssdssiisi", $name, $description, $price, $stock, $category, $tags, $discount_percent, $image_url, $product_id);
                
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
        $response["message"] = "Invalid action or insufficient permissions.";
    }
    
    // Return JSON response
    header("Content-Type: application/json");
    echo json_encode($response);
    exit;
} else {
    // Redirect to home page if accessed directly
    redirect("index.php");
}
?>