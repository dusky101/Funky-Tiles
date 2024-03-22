<?php

function funkytiles_admin_html() {

    // Fetch the options from the database
    $tiles = get_option('funkytiles_tiles', []);
    $categories = get_option('funkytiles_categories', []);

	// Start outputting HTML
	echo '<div class="funkytiles-admin-wrap">';
	echo '  <div class="wrap">';
	echo '      <h1>FunkyTiles Settings</h1>';
    
    // Instruction for best photo sizes
    echo '<p>For best results, upload images with dimensions of 800x600 pixels.</p>';

	echo '<div class="funkytiles-form">';
	echo '<button id="funkytiles-show-add-tile-form" class="button button-secondary">Add New Tile</button>';

	// Form for adding a new tile
	echo '<div id="funkytiles-new-tile-form" class="hidden">';
	echo '<h2>Add New Tile</h2>';
	echo '<input type="text" id="new-tile-category" placeholder="Category">';
	echo '<input type="url" id="new-tile-link-url" placeholder="Link URL">';
	echo '<input type="hidden" id="new-tile-image-value">'; // Changed the ID to differentiate between the image URL storage and the image display
	echo '<div id="image-preview"></div>'; // This is where the image will be displayed
	echo '<button type="button" id="new-tile-upload-button" class="button button-secondary">Select Image</button>';
	echo '<input type="text" id="new-tile-header" placeholder="Header Text">';
	echo '<input type="text" id="new-tile-animated-text" placeholder="Animated Text">';
	echo '<textarea id="new-tile-paragraph" placeholder="Paragraph Text"></textarea>';
	echo '<button type="button" id="save-new-tile" class="wp-core-ui button-primary">Save New Tile</button>';
	echo '</div>'; // Close .hidden

    // Instructions for color selection
    echo '<p>Colour selections apply to the category\'s appearance. Choose contrasting colours for text and background for better readability.</p>';

    // Button to show form for adding a new category
    echo '<button id="funkytiles-show-add-category-form" class="button button-secondary">Add New Category</button>';
	
	// Form for adding or editing a category
	echo '<div id="funkytiles-category-form" class="funkytiles-form-section hidden">';
	echo '<h2 id="funkytiles-category-form-title">Add New Category</h2>';
	// Input field for category name - it will be populated when editing an existing category
	echo '<input type="text" id="new-category-name" placeholder="Category Name">';

	// Hidden input field to store the original category name when editing
	echo '<input type="hidden" id="funkytiles-original-category-name">';

	// Begin color picker section
	echo '<div class="funkytiles-color-pickers">';

	// Color pickers for category styling with labels
	echo '<div class="color-picker-group">';
	echo '<label for="category-background-color">Background Colour</label>';
	echo '<input type="color" class="funkytiles-color-picker" id="category-background-color" data-default-color="#99aeff">';
	echo '</div>';

	echo '<div class="color-picker-group">';
	echo '<label for="category-text-color">Text Colour</label>';
	echo '<input type="color" class="funkytiles-color-picker" id="category-text-color" data-default-color="#ffffff">';
	echo '</div>';

	echo '<div class="color-picker-group">';
	echo '<label for="category-h1-color">H1 Colour</label>';
	echo '<input type="color" class="funkytiles-color-picker" id="category-h1-color" data-default-color="#ffffff">';
	echo '</div>';

	echo '<div class="color-picker-group">';
	echo '<label for="category-h2-color">H2 Colour</label>';
	echo '<input type="color" class="funkytiles-color-picker" id="category-h2-color" data-default-color="#ffffff">';
	echo '</div>';

	echo '<div class="color-picker-group">';
	echo '<label for="category-p-color">Paragraph Colour</label>';
	echo '<input type="color" class="funkytiles-color-picker" id="category-p-color" data-default-color="#ffffff">';
	echo '</div>';

	// Adding an overlay color picker
	echo '<div class="color-picker-group">';
	echo '<label for="category-overlay-color">Overlay Colour</label>';
	echo '<input type="color" class="funkytiles-color-picker" id="category-overlay-color" data-default-color="#000000">'; // The default color should be in hex format.
	echo '</div>';

	echo '</div>'; // End color picker section

	echo '<select id="category-font-family">';
	echo '<option value="Roboto">Roboto</option>';
	echo '<option value="Arial">Arial</option>';
	echo '<option value="Georgia">Georgia</option>';
	// Add more fonts as needed
	echo '</select>';
	// Save New Category button
	echo '<button type="button" id="save-new-category" class="wp-core-ui button-primary">Save New Category</button>';

	// Update Category button (hidden by default)
	echo '<button type="button" id="update-category" class="wp-core-ui button-primary hidden">Update Category</button>';
	
	echo '</div>'; // Close .hidden
	echo '</div>'; // Close .funkytiles-form

    // Group tiles by category
	$category_tiles = [];
	foreach ($tiles as $index => $tile) {
		$category_tiles[$tile['category']][$index] = $tile;
	}

	// Display tiles grouped by category
	foreach ($category_tiles as $category_name => $tiles_in_category) {
		echo '<h2 class="funkytiles-category-header">' . esc_html($category_name) . '</h2>';
		echo '<div class="funkytiles-category-row">'; // Container for a row of tiles

		foreach ($tiles_in_category as $index => $tile) {
			echo '<div class="funkytiles-tile" data-index="' . esc_attr($index) . '">';
			echo '<img class="funkytiles-thumbnail" src="' . esc_url($tile['image']) . '" alt="">';
			echo '<div>';
			echo '<p>Header: ' . esc_html($tile['header']) . '</p>';
			echo '<p>Link URL: ' . esc_url($tile['link_url']) . '</p>';
			echo '<p>Animated Text: ' . esc_html($tile['animated_text']) . '</p>';
			echo '<p>Paragraph Text: ' . esc_html($tile['paragraph']) . '</p>';
			echo '</div>'; // Close the text container div
			echo '<div class="funkytiles-tile-actions">';
			echo '<button type="button" class="funkytiles-edit-tile button button-secondary" data-index="' . esc_attr($index) . '">Edit Tile</button>';
			echo '<button type="button" class="funkytiles-remove-tile button" data-index="' . esc_attr($index) . '">Remove Tile</button>';
			echo '</div>'; // Close .funkytiles-tile-actions
			echo '</div>'; // Close .funkytiles-tile
		}

		echo '</div>'; // Close .funkytiles-category-row
		}
	// Display existing categories with edit capability
	echo '<div id="category-list">';
	echo '<h2>Categories</h2>';
	if (!empty($categories)) {
		echo '<ul>';
		foreach ($categories as $category_name => $styles) {
			$safe_category_name = esc_attr($category_name); // Safe variable for use in HTML attributes

			echo '<li data-category-name="' . $safe_category_name . '">';
			echo '<div class="category-display">';

			// Category name heading with an Edit button next to it
			echo '<strong>' . esc_html($category_name) . '</strong> ';
			echo '<button type="button" class="edit-category button" data-category-name="' . $safe_category_name . '">Edit</button>';

			echo '</div>'; // Close .category-display
			echo '</li>';
		}
		echo '</ul>';
	} else {
		echo '<p>No categories found.</p>';
	}
	echo '</div>'; // Close #category-list


	echo '</div>'; // Close .wrap
	echo '</div>'; // Close .funkytiles-admin-wrap

}