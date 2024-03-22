jQuery(document).ready(function($) {
    var mediaUploader;

    // Toggle for adding a new tile
    $('#funkytiles-show-add-tile-form').click(function(e) {
        e.preventDefault();
        $('#funkytiles-new-tile-form').slideToggle(); // Use slideToggle for a nice effect
    });

    // Handle image upload
    $('#new-tile-upload-button').click(function(e) {
        e.preventDefault();
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media({
            title: 'Select Image',
            button: { text: 'Use this image' },
            multiple: false
        }).on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#new-tile-image').val(attachment.url);
            var preview = '<img src="' + attachment.url + '" class="funkytiles-thumbnail"/>';
            $('#new-tile-image').after(preview);
        }).open();
    });

    // Trigger for editing an existing tile
	$(document).on('click', '.funkytiles-edit-tile', function(e) {
		e.preventDefault();
		var tileIndex = $(this).data('index');

		// Make an AJAX call to get the tile data for editing
		$.ajax({
			url: funkytiles_ajax.ajax_url,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'funkytiles_get_tile',
				nonce: funkytiles_ajax.nonce,
				index: tileIndex
			},
			success: function(response) {
				if(response.success) {
					// Populate the form fields with the tile data
					$('#new-tile-category').val(response.data.tile.category);
					$('#new-tile-link-url').val(response.data.tile.link_url);
					$('#new-tile-header').val(response.data.tile.header);
					$('#new-tile-animated-text').val(response.data.tile.animated_text);
					$('#new-tile-paragraph').val(response.data.tile.paragraph);
					// Assuming you have a container with an ID #image-preview for the image
					$('#image-preview').html('<img src="' + response.data.tile.image + '" class="funkytiles-thumbnail"/>');
					$('#new-tile-image').val(response.data.tile.image);

					// Update the button to say "Update Tile" and hold the index of the tile
					$('#save-new-tile').text('Update Tile').data('index', tileIndex);

					// Show the form
					$('#funkytiles-new-tile-form').slideDown();
				} else {
					alert('Error retrieving tile data.');
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				alert('Failed to retrieve tile data: ' + textStatus);
			}
		});
	});


    // Trigger for saving a new tile or updating an existing one
	 $('#save-new-tile').click(function(e) {
        e.preventDefault();

        var tileIndex = $(this).data('index');
        var isEditing = typeof tileIndex === 'number' && tileIndex >= 0;
        var action = isEditing ? 'funkytiles_update_tile' : 'funkytiles_save_tile';

        var formData = {
			action: action, // 'funkytiles_save_tile' or 'funkytiles_update_tile'
			nonce: funkytiles_ajax.nonce,
			index: isEditing ? tileIndex : undefined, // Only add index if editing
			tile: {
				category: $('#new-tile-category').val(),
				link_url: $('#new-tile-link-url').val(),
				image: $('#new-tile-image').val(),
				header: $('#new-tile-header').val(),
				animated_text: $('#new-tile-animated-text').val(),
				paragraph: $('#new-tile-paragraph').val()
			},
			new_category: $('#new-tile-category').val() // Send the category for adding to the list if necessary
		};

        $.ajax({
            url: funkytiles_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
				if (response.success) {
					var tileData = response.data.tile; // The tile data from the server response

					if (isEditing) {
						// Update the existing tile's display
						var tileElement = $('.funkytiles-tile[data-index="' + tileData.index + '"]');
						tileElement.find('.funkytiles-thumbnail').attr('src', tileData.image);
						tileElement.find('p.category').text('Category: ' + tileData.category);
						tileElement.find('p.header').text('Header: ' + tileData.header);
						tileElement.find('p.link_url').text('Link URL: ' + tileData.link_url);
						tileElement.find('p.animated_text').text('Animated Text: ' + tileData.animated_text);
						tileElement.find('p.paragraph').text('Paragraph Text: ' + tileData.paragraph);
					} else {
						// Add the new tile's display
						var newTileHtml = `<div class="funkytiles-tile" data-index="${tileData.index}">
							<img class="funkytiles-thumbnail" src="${tileData.image}" alt="">
							<p class="category">Category: ${tileData.category}</p>
							<p class="header">Header: ${tileData.header}</p>
							<p class="link_url">Link URL: ${tileData.link_url}</p>
							<p class="animated_text">Animated Text: ${tileData.animated_text}</p>
							<p class="paragraph">Paragraph Text: ${tileData.paragraph}</p>
							<button type="button" class="funkytiles-edit-tile button" data-index="${tileData.index}">Edit Tile</button>
							<button type="button" class="funkytiles-remove-tile button" data-index="${tileData.index}">Remove Tile</button>
						</div>`;
						$('#funkytiles-tiles').append(newTileHtml);
					}

					// Check if a new category was added
                    if (response.data.new_category_added) {
                        // Use response.data.new_category_name to ensure we're using the newly added category name
                        var newCategoryHtml = `
                        <li data-category-name="${response.data.categoryName}">
                            <form class="category-edit-form">
                                <strong>${response.data.categoryName}</strong>
                                <input type="color" name="background_color" value="#ffffff" />
                                <input type="color" name="text_color" value="#000000" />
                                <input type="color" name="h1_color" value="#000000" />
                                <input type="color" name="h2_color" value="#000000" />
                                <input type="color" name="p_color" value="#000000" />
                                <input type="color" name="overlay_color" value="rgba(0, 0, 0, 0.4)" /> <!-- Overlay color picker -->
                                <select name="font_family">
                                    <option value="Roboto">Roboto</option>
                                    <option value="Arial">Arial</option>
                                    <option value="Georgia">Georgia</option>
                                    <!-- Add more fonts as needed -->
                                </select>
                                <button type="submit" class="wp-core-ui button-primary">Update Category</button>
                                <button type="button" class="delete-category button" data-category-name="${response.data.categoryName}">Delete Category</button>
                            </form>
                        </li>`;
                    $('#category-list ul').append(newCategoryHtml);
                                        }

                    // Clear the form for the next entry
					$('#new-tile-category').val('');
					$('#new-tile-link-url').val('');
					$('#new-tile-header').val('');
					$('#new-tile-animated-text').val('');
					$('#new-tile-paragraph').val('');
					$('#new-tile-image').next('img').remove(); // Remove the image preview
					$('#new-tile-image').val(''); // Clear the hidden image input

					// Reset the button text and remove the index data
					$('#save-new-tile').text('Save New Tile').removeData('index');

					// Hide the form
					$('#funkytiles-new-tile-form').slideUp();
				} else {
					alert('Error: ' + (response.data && response.data.message ? response.data.message : 'Could not save tile.'));
				}
			},
    });
});

   // Functionality for removing a tile
    $(document).on('click', '.funkytiles-remove-tile', function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to remove this tile? This action cannot be undone.')) {
            return;
        }

        var $thisButton = $(this);
        var tileIndex = $thisButton.data('index');

        $.ajax({
            url: funkytiles_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'funkytiles_delete_tile',
                index: tileIndex,
                nonce: funkytiles_ajax.nonce
            },
            success: function(response) {
                if(response.success) {
                    alert('Tile removed successfully.');
                    $thisButton.closest('.funkytiles-tile').remove();
                } else {
                    alert('Error removing tile: ' + (response.data || 'Unknown error'));
                }
            }
        });
    });

	 // Toggle for adding a new category
	$('#funkytiles-show-add-category-form').click(function(e) {
		e.preventDefault();
		$('#funkytiles-new-category-form').slideToggle(); // Simpler toggle for visibility

		// Set the form to "add" mode
		$('#funkytiles-category-form-title').text('Add New Category');
		$('#save-new-category').removeClass('hidden'); // Show the save button
		$('#update-category').addClass('hidden'); // Hide the update button

		// Clear any previous values
		$('#new-category-name').val('');
		$('.funkytiles-color-picker').each(function() {
			$(this).val($(this).data('default-color'));
		});
		$('#category-font-family').val('Roboto'); // Set to default or any value you see fit
	});

	// Trigger for editing an existing category
	$(document).on('click', '.edit-category', function(e) {
		e.preventDefault();
		var categoryName = $(this).data('category-name');

		// Make an AJAX call to get the category data for editing
		$.ajax({
			url: funkytiles_ajax.ajax_url,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'funkytiles_get_category', // The PHP function to get category data
				nonce: funkytiles_ajax.nonce, // Security nonce
				category_name: categoryName // Name of the category being edited
			},
			success: function(response) {
				if(response.success) {
					// Change form title and button text to indicate editing
					$('#funkytiles-category-form-title').text('Edit Category');
					$('#save-new-category').addClass('hidden'); // Hide the save button
					$('#update-category').removeClass('hidden'); // Show the update button

					// Populate the form fields with the category data
					$('#new-category-name').val(categoryName);
					$('#category-background-color').val(response.data.styles['background-color']);
					$('#category-text-color').val(response.data.styles['text-color']);
					$('#category-h1-color').val(response.data.styles['h1-color']);
					$('#category-h2-color').val(response.data.styles['h2-color']);
					$('#category-p-color').val(response.data.styles['p-color']);
					$('#category-overlay-color').val(response.data.styles['overlay-color']);
					$('#category-font-family').val(response.data.styles['font-family']).change();

					// Trigger the color picker change event so that the UI updates
					$('.funkytiles-color-picker').trigger('change');

					// Show the form
					$('#funkytiles-category-form').slideDown();

					// Keep track of the original category name for updates
					$('#funkytiles-original-category-name').val(categoryName);
				} else {
					alert('Error retrieving category data: ' + response.message);
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				alert('Failed to retrieve category data: ' + errorThrown);
			}
		});
	});

	// Event handler for the 'Update Category' button
	$('#update-category').on('click', function(e) {
    e.preventDefault();
    var originalCategoryName = $('#funkytiles-original-category-name').val(); // Get the original name for comparison
    var categoryName = $('#new-category-name').val();
    var backgroundColor = $('#category-background-color').val();
    var textColor = $('#category-text-color').val();
    var h1Color = $('#category-h1-color').val();
    var h2Color = $('#category-h2-color').val();
    var pColor = $('#category-p-color').val();
    var overlayColor = $('#category-overlay-color').val();
    var fontFamily = $('#category-font-family').val();

    $.ajax({
        url: funkytiles_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'funkytiles_update_category',
            old_category_name: originalCategoryName, // You need to send the original category name
            new_category_name: categoryName, // And the new one
            background_color: backgroundColor,
            text_color: textColor,
            h1_color: h1Color,
            h2_color: h2Color,
            p_color: pColor,
            overlay_color: overlayColor,
            font_family: fontFamily,
            nonce: funkytiles_ajax.nonce
        },
        success: function(response) {
    if (response.success) {
        alert('Category updated successfully.');

        // Update the category in the UI without adding a new one
        var categoryItem = $('li[data-category-name="' + originalCategoryName + '"]');
        categoryItem.attr('data-category-name', categoryName); // Update the data attribute
        categoryItem.find('strong').text(categoryName); // Update the category name display

        // Update the colors and font for the category in the UI
        categoryItem.find('.category-background-color-display').css('background-color', backgroundColor);
        categoryItem.find('.category-text-color-display').css('color', textColor);
        categoryItem.find('.category-h1-color-display').css('color', h1Color);
        categoryItem.find('.category-h2-color-display').css('color', h2Color);
        categoryItem.find('.category-p-color-display').css('color', pColor);
        categoryItem.find('.category-overlay-color-display').css('background-color', overlayColor); // Assuming overlay color is a background-color
        categoryItem.find('.category-font-family-display').css('font-family', fontFamily);
        
        // This assumes you have elements with these classes or similar for each property
        // where the category styles are being displayed on the page.
    } else {
        alert('Error updating category: ' + (response.data.message || 'Unknown error'));
    }
},
error: function(jqXHR, textStatus, errorThrown) {
    alert('Failed to update category: ' + errorThrown);
}
    });
});


    // Saving a new category with style settings
    $('#save-new-category').click(function(e) {
        e.preventDefault();
        var categoryName = $('#new-category-name').val();
        var backgroundColor = $('#category-background-color').val();
        var textColor = $('#category-text-color').val();
        var h1Color = $('#category-h1-color').val();
        var h2Color = $('#category-h2-color').val();
        var pColor = $('#category-p-color').val();
        var overlayColor = $('#category-overlay-color').val(); // Adding overlay color
        var fontFamily = $('#category-font-family').val();


        $.ajax({
            url: funkytiles_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'funkytiles_save_new_category',
                category_name: categoryName,
                background_color: backgroundColor,
                text_color: textColor,
                h1_color: h1Color,
                h2_color: h2Color,
                p_color: pColor,
                overlay_color: overlayColor, // Ensure to send overlay color
                font_family: fontFamily,
                nonce: funkytiles_ajax.nonce
            },
            success: function(response) {
                if(response.success) {
                    alert('Category added successfully.');
                        var newCategoryHtml = `
                        <li data-category-name="${response.data.categoryName}">
                            <form class="category-edit-form">
                                <strong>${response.data.categoryName}</strong>
                                <input type="color" name="background_color" value="#ffffff" />
                                <input type="color" name="text_color" value="#000000" />
                                <input type="color" name="h1_color" value="#000000" />
                                <input type="color" name="h2_color" value="#000000" />
                                <input type="color" name="p_color" value="#000000" />
                                <input type="color" name="overlay_color" value="rgba(0, 0, 0, 0.4)" /> <!-- Overlay color picker -->
                                <select name="font_family">
                                    <option value="Roboto">Roboto</option>
                                    <option value="Arial">Arial</option>
                                    <option value="Georgia">Georgia</option>
                                    <!-- Add more fonts as needed -->
                                </select>
                                <button type="submit" class="wp-core-ui button-primary">Update Category</button>
                                <button type="button" class="delete-category button" data-category-name="${response.data.categoryName}">Delete Category</button>
                            </form>
                        </li>`;
                    $('#category-list ul').append(newCategoryHtml);
                } else {
                    alert('Error adding category: ' + (response.data || 'Unknown error'));
                }
            }
            
        });
    });

    // Functionality for removing a category
    $(document).on('click', '.delete-category', function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this category? This action cannot be undone.')) {
            return;
        }

        var $thisButton = $(this);
        var categoryName = $thisButton.data('category-name');

        $.ajax({
            url: funkytiles_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'funkytiles_delete_category',
                category_name: categoryName,
                nonce: funkytiles_ajax.nonce
            },
            success: function(response) {
                if(response.success) {
                    alert('Category deleted successfully.');
                    $thisButton.closest('li').remove();
                } else {
                    alert('Error deleting category: ' + (response.data || 'Unknown error'));
                }
            }
        });
    });

	// Toggle category name editability
		$(document).on('click', '.edit-category-name', function(e) {
			e.preventDefault();
			var $categoryItem = $(this).closest('li');
			var categoryName = $categoryItem.data('category-name');
			$categoryItem.find('strong').replaceWith('<input type="text" name="new_category_name" value="' + categoryName + '">');
			$(this).replaceWith('<button type="submit" class="wp-core-ui button-primary save-category-edits">Save Changes</button>');
		});

	// Trigger for saving updated category styles and name
	$(document).on('submit', '.category-edit-form', function(e) {
		e.preventDefault();
		var $form = $(this);
		var formData = $form.serializeArray();
		 console.log("AJAX Success Response:", response);

		// Retrieve the old category name from the data attribute
		var oldCategoryName = $form.data('category-name');
		formData.push({name: 'old_category_name', value: oldCategoryName});

		// Retrieve the new category name from the input field if it's been edited
		var newCategoryName = $form.find('[name="new_category_name"]').val() || oldCategoryName;
		formData.push({name: 'new_category_name', value: newCategoryName});

		// Add the action and nonce
		formData.push({name: 'action', value: 'funkytiles_update_category'});
		formData.push({name: 'nonce', value: funkytiles_ajax.nonce});


		$.ajax({
			url: funkytiles_ajax.ajax_url,
			type: 'POST',
			data: formData,
			success: function(response) {
				if (response.success) {
					alert('Category updated successfully.');
					// Update the UI here
					// Example: If you're displaying the category name somewhere on the page:
					$('.category-display[data-category-name="' + oldCategoryName + '"]').text(newCategoryName);
					// Update the data attribute to the new category name
					$form.data('category-name', newCategoryName);
				} else {
					alert('Error updating category: ' + (response.data.message || 'Unknown error'));
				}
			},
			error: function(response) {
				alert('Error adding category: ' + response.responseJSON.data.message);
				console.log("AJAX Error Response:", response);


			}
		});
	});

    // Initialize the color picker
    $('.funkytiles-color-picker').wpColorPicker();
});
