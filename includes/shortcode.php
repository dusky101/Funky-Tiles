<?php
	function funkytiles_shortcode($atts = []) {
    // Enqueue the styles and scripts specifically for this shortcode
    wp_enqueue_style('funkytiles-style', FUNKYTILES_PLUGIN_URL . 'css/style.css', [], FUNKYTILES_VERSION);
    wp_enqueue_script('funkytiles-frontend', FUNKYTILES_PLUGIN_URL . 'js/frontend.js', ['jquery'], FUNKYTILES_VERSION, true);

    // Extract category attribute from the shortcode
    $attributes = shortcode_atts(['category' => ''], $atts);
    $category_filter = $attributes['category'];

    // Get the tiles and categories from the database
    $tiles = get_option('funkytiles_tiles', []);
    $categories = get_option('funkytiles_categories', []);

    // Start output buffering
    ob_start();
    echo '<div class="ft-wrap">';

    foreach ($tiles as $index => $tile) {
        // Skip tiles not in the specified category if a category filter is set
        if (!empty($category_filter) && $tile['category'] !== $category_filter) {
            continue;
        }

        // Retrieve category-specific styles if available
        $catStyles = $categories[$tile['category']] ?? [];

        // Display the tile with inline styles applied from category settings
        echo '<div class="ft-tile" style="';
        echo 'background-color: ' . esc_attr($catStyles['background-color'] ?? '#99aeff') . ';';
        echo 'color: ' . esc_attr($catStyles['text-color'] ?? 'white') . ';';
        echo 'font-family: ' . esc_attr($catStyles['font-family'] ?? 'Roboto') . ';"';
        echo ' data-href="' . esc_url($tile['link_url']) . '">';

        if (isset($tile['image'])) {
            echo '<img src="' . esc_url($tile['image']) . '" alt="Tile Image"/>';
            // Overlay layer
            echo '<div class="ft-overlay" style="background-color: ' . esc_attr($catStyles['overlay-color'] ?? 'rgba(0, 0, 0, 0.4)') . ';"></div>';
        }

        // Display the text content
        echo '<div class="ft-text">';
        if (isset($tile['header'])) {
            echo '<h1 style="color: ' . esc_attr($catStyles['h1-color'] ?? 'white') . ';">' . esc_html($tile['header']) . '</h1>';
        }
        if (isset($tile['animated_text'])) {
            echo '<h2 style="color: ' . esc_attr($catStyles['h2-color'] ?? 'white') . ';" class="ft-animate-text">' . esc_html($tile['animated_text']) . '</h2>';
        }
        if (isset($tile['paragraph'])) {
            echo '<p style="color: ' . esc_attr($catStyles['p-color'] ?? 'white') . ';" class="ft-animate-text">' . esc_html($tile['paragraph']) . '</p>';
        }
		echo '<div class="ft-dots"><span></span><span></span><span></span></div>'; // Updated class name
		
        echo '</div>'; // Close .ft-text

        echo '</div>'; // Close .ft-tile
    }

    echo '</div>'; // Close .ft-wrap
    return ob_get_clean();
	}

add_shortcode('funkytiles', 'funkytiles_shortcode');

    