<?php
// AJAX handler for getting a single tile's data
add_action('wp_ajax_funkytiles_get_tile', 'funkytiles_ajax_get_tile');

function funkytiles_ajax_get_tile() {
    // Security check - nonce verification
    check_ajax_referer('funkytiles_nonce', 'nonce');

    // Check if the current user has permission to edit posts
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions to edit tiles.']);
        return;
    }

    // Sanitize and validate the index of the tile
    $index = isset($_POST['index']) ? intval($_POST['index']) : null;
    if (null === $index) {
        wp_send_json_error(['message' => 'Invalid tile index.']);
        return;
    }

    // Retrieve the tiles from the database
    $tiles = get_option('funkytiles_tiles', []);

    // Check if the tile exists
    if (!isset($tiles[$index])) {
        wp_send_json_error(['message' => 'Tile not found.']);
        return;
    }

    // Send back the tile's data
    wp_send_json_success(['tile' => $tiles[$index]]);
}

//AJAX for getting Categories
add_action('wp_ajax_funkytiles_get_category', 'funkytiles_ajax_get_category');
function funkytiles_ajax_get_category() {
	// Security check - nonce verification
    check_ajax_referer('funkytiles_nonce', 'nonce');

    // Check if the current user has permission to edit posts
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions to edit tiles.']);
        return;
    }
	 $category_name = isset($_POST['category_name']) ? sanitize_text_field($_POST['category_name']) : '';

    if (empty($category_name)) {
        wp_send_json_error(['message' => 'Category name cannot be empty.']);
        return;
    }

    $categories = get_option('funkytiles_categories', []);
    if (isset($categories[$category_name])) {
        wp_send_json_success(['styles' => $categories[$category_name]]);
    } else {
        wp_send_json_error(['message' => 'Category not found.']);
    }
}


// AJAX handler for saving tiles
add_action('wp_ajax_funkytiles_save_tile', 'funkytiles_ajax_save_tile');
function funkytiles_ajax_save_tile() {
    // Verify nonce for security
    check_ajax_referer('funkytiles_nonce', 'nonce');
    
    // Validate that tile data is set and is an array
    if (!isset($_POST['tile']) || !is_array($_POST['tile'])) {
        wp_send_json_error(['message' => 'Invalid tile data provided.']);
        return;
    }

    $tile_data = $_POST['tile'];

    // Sanitize and validate tile data
    $sanitized_tile_data = [
        'image' => esc_url_raw($tile_data['image'] ?? ''),
        'header' => sanitize_text_field($tile_data['header'] ?? ''),
        'animated_text' => sanitize_text_field($tile_data['animated_text'] ?? ''),
        'paragraph' => sanitize_textarea_field($tile_data['paragraph'] ?? ''),
        'link_url' => esc_url_raw($tile_data['link_url'] ?? ''),
        'category' => sanitize_text_field($tile_data['category'] ?? '')
    ];

    // Retrieve existing tiles
    $tiles = get_option('funkytiles_tiles', []);
    
    // Append the new tile data
    $tiles[] = $sanitized_tile_data;
    
    // Attempt to save the updated tiles list
    $tiles_updated = update_option('funkytiles_tiles', $tiles);

    // Update categories separately to avoid unnecessary updates
    if ($tiles_updated && !empty($sanitized_tile_data['category'])) {
        $categories = get_option('funkytiles_categories', []);
        $new_category_added = false;

        // Check if the category exists; if not, add it
        if (!array_key_exists($sanitized_tile_data['category'], $categories)) {
            $categories[$sanitized_tile_data['category']] = []; // Assuming you might want to store additional data in the future
            $new_category_added = update_option('funkytiles_categories', $categories);
        }

        // Respond with success and additional data
        wp_send_json_success([
            'message' => 'Tile saved successfully.',
            'tile' => $sanitized_tile_data + ['index' => array_key_last($tiles)],
            'new_category_added' => $new_category_added,
            'new_category_name' => $sanitized_tile_data['category']
        ]);
    } elseif ($tiles_updated) {
        // Tile was updated but no new category was added
        wp_send_json_success([
            'message' => 'Tile saved successfully.',
            'tile' => $sanitized_tile_data + ['index' => array_key_last($tiles)],
        ]);
    } else {
        wp_send_json_error(['message' => 'Failed to save the tile.']);
    }
}

// AJAX handler for deleting tiles
add_action('wp_ajax_funkytiles_delete_tile', 'funkytiles_ajax_delete_tile');
function funkytiles_ajax_delete_tile() {
    // Verify nonce for security
    check_ajax_referer('funkytiles_nonce', 'nonce');
    
    // Ensure current user has the capability to edit tiles
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions to delete tile.']);
        return;
    }

    // The tile index to delete is expected to be passed in the AJAX request
    $index = intval($_POST['index']);
    $tiles = get_option('funkytiles_tiles', []);
    
    // Verify that the tile exists at the specified index
    if (isset($tiles[$index])) {
        // Remove the tile from the array
        array_splice($tiles, $index, 1); // Preferred over unset for associative array elements
        
        // Save the updated tiles back to the database
        $updated = update_option('funkytiles_tiles', $tiles);
        if ($updated) {
            // Send a success response with the index of the deleted tile
            wp_send_json_success([
                'message' => 'Tile deleted successfully.',
                'deletedIndex' => $index // Provide the index of the deleted tile for the UI to remove
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to update tiles after deletion.']);
        }
    } else {
        wp_send_json_error(['message' => 'Tile not found.']);
    }
}

// AJAX handler for updating an existing tile
add_action('wp_ajax_funkytiles_update_tile', 'funkytiles_ajax_update_tile');
function funkytiles_ajax_update_tile() {
    // Verify nonce for security
    check_ajax_referer('funkytiles_nonce', 'nonce');

    // Check user permissions
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions to update tile.']);
        return;
    }

    // Validate and sanitize input data
    $index = isset($_POST['index']) ? intval($_POST['index']) : null;
    $tileData = isset($_POST['tile']) ? $_POST['tile'] : [];

    // Basic validation
    if (null === $index || !is_array($tileData)) {
        wp_send_json_error(['message' => 'Invalid request.']);
        return;
    }

    // Retrieve the current tiles
    $tiles = get_option('funkytiles_tiles', []);
    
    // Ensure the tile at the given index exists
    if (!isset($tiles[$index])) {
        wp_send_json_error(['message' => 'Tile not found.']);
        return;
    }

    // Sanitize each piece of tile data
    $sanitizedTile = [
        'category'      => sanitize_text_field($tileData['category'] ?? ''),
        'link_url'      => esc_url_raw($tileData['link_url'] ?? ''),
        'image'         => esc_url_raw($tileData['image'] ?? ''),
        'header'        => sanitize_text_field($tileData['header'] ?? ''),
        'animated_text' => sanitize_text_field($tileData['animated_text'] ?? ''),
        'paragraph'     => sanitize_textarea_field($tileData['paragraph'] ?? ''),
    ];

    // Update the tile at the given index
    $tiles[$index] = $sanitizedTile;

    // Save the updated tiles back to the database
    if (update_option('funkytiles_tiles', $tiles)) {
        wp_send_json_success([
            'message' => 'Tile updated successfully.',
            'tile' => $sanitizedTile
        ]);
    } else {
        wp_send_json_error(['message' => 'No changes were made to the tile.']);
    }
}


// AJAX handler for saving a new category
add_action('wp_ajax_funkytiles_save_new_category', 'funkytiles_ajax_save_new_category');
function funkytiles_ajax_save_new_category() {
    check_ajax_referer('funkytiles_nonce', 'nonce');

    // Ensure current user has the capability to add categories
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions to add category.']);
        return;
    }

    // Sanitize all input data
    $category_name = sanitize_text_field($_POST['category_name'] ?? '');
    if (empty($category_name)) {
        wp_send_json_error(['message' => 'Category name cannot be empty.']);
        return;
    }

    $categories = get_option('funkytiles_categories', []);

    // Prevent duplicate category names
    if (array_key_exists($category_name, $categories)) {
        wp_send_json_error(['message' => 'Category already exists.']);
        return;
    }

     // Sanitize color inputs
     $background_color = sanitize_hex_color($_POST['background_color'] ?? '#ffffff');
     $text_color = sanitize_hex_color($_POST['text_color'] ?? '#000000');
     $h1_color = sanitize_hex_color($_POST['h1_color'] ?? '#000000');
     $h2_color = sanitize_hex_color($_POST['h2_color'] ?? '#000000');
     $p_color = sanitize_hex_color($_POST['p_color'] ?? '#000000');
     $overlay_color = sanitize_hex_color($_POST['overlay_color'] ?? 'rgba(0, 0, 0, 0.4)'); // New overlay color input
     $font_family = sanitize_text_field($_POST['font_family'] ?? 'Arial');
 
     // Add new category with overlay color
     $categories[$category_name] = [
         'background-color' => $background_color,
         'text-color' => $text_color,
         'h1-color' => $h1_color,
         'h2-color' => $h2_color,
         'p-color' => $p_color,
         'overlay-color' => $overlay_color, // Include the new overlay color in the category settings
         'font-family' => $font_family
     ];

    // Update the categories option
    if (update_option('funkytiles_categories', $categories)) {
        wp_send_json_success([
            'message' => 'Category saved successfully.',
            'categoryName' => $category_name,
            'styles' => $categories[$category_name]
        ]);
    } else {
        wp_send_json_error(['message' => 'Failed to save the category.']);
    }
}


// AJAX handler for updating a category
add_action('wp_ajax_funkytiles_update_category', 'funkytiles_ajax_update_category');
function funkytiles_ajax_update_category() {
    check_ajax_referer('funkytiles_nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions to edit category.']);
        return;
    }

    // Retrieve both old and new category names
    $old_category_name = sanitize_text_field($_POST['old_category_name'] ?? '');
    $new_category_name = sanitize_text_field($_POST['new_category_name'] ?? '');

    if (empty($new_category_name)) {
        wp_send_json_error(['message' => 'New category name cannot be empty.']);
        return;
    }

    // Fetch existing categories
    $categories = get_option('funkytiles_categories', []);

    // Check if the old category exists when renaming
    if (!array_key_exists($old_category_name, $categories)) {
        wp_send_json_error(['message' => 'Old category not found.']);
        return;
    }

    // Update the category name if it has been changed
    if ($old_category_name !== $new_category_name) {
        $categories[$new_category_name] = $categories[$old_category_name];
        unset($categories[$old_category_name]);
    }

    // Update the styles for the (possibly new) category name key
    $categories[$new_category_name] = [
        'background-color' => sanitize_hex_color($_POST['background_color'] ?? ''),
        'text-color' => sanitize_hex_color($_POST['text_color'] ?? ''),
        'h1-color' => sanitize_hex_color($_POST['h1_color'] ?? ''),
        'h2-color' => sanitize_hex_color($_POST['h2_color'] ?? ''),
        'p-color' => sanitize_hex_color($_POST['p_color'] ?? ''),
        'overlay-color' => $_POST['overlay_color'] ?? '', // You may need a custom validation for rgba values
        'font-family' => sanitize_text_field($_POST['font_family'] ?? 'Arial'),
    ];

    // Save the updated category data
    if (update_option('funkytiles_categories', $categories)) {
        wp_send_json_success([
            'message' => 'Category updated successfully.',
            'categoryName' => $new_category_name,
            'styles' => $categories[$new_category_name]
        ]);
    } else {
        wp_send_json_error(['message' => 'Failed to update the category.']);
    }
}


// AJAX handler for deleting a category
add_action('wp_ajax_funkytiles_delete_category', 'funkytiles_ajax_delete_category');
function funkytiles_ajax_delete_category() {
    check_ajax_referer('funkytiles_nonce', 'nonce');

    // Permissions check
    if (!current_user_can('delete_posts')) {
        wp_send_json_error(['message' => 'Insufficient permissions to delete category.']);
        return;
    }

    $category_name = sanitize_text_field($_POST['category_name'] ?? '');
    $categories = get_option('funkytiles_categories', []);

    if (isset($categories[$category_name])) {
        unset($categories[$category_name]);
        if (update_option('funkytiles_categories', $categories)) {
            wp_send_json_success([
                'message' => 'Category deleted successfully.',
                'categoryName' => $category_name
            ]);
        } else {
            wp_send_json_error(['message' => 'Failed to delete the category.']);
        }
    } else {
        wp_send_json_error(['message' => 'Category not found.']);
    }
}
