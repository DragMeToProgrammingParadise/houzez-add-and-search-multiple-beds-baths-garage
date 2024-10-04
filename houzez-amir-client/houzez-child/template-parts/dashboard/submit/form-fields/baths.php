<div class="form-group">
    <label for="prop_baths">
        <?php echo houzez_option('cl_bathrooms', 'Bathrooms') . houzez_required_field('bathrooms'); ?>
    </label>
    
    <input class="form-control" name="prop_baths" id="prop_baths"
        value="<?php
        // Check if the property is being edited
        if (houzez_edit_property()) {
            // Fetch the correct post ID from the URL (edit_property parameter)
            $post_id = isset($_GET['edit_property']) ? intval($_GET['edit_property']) : get_the_ID();

            // Get the existing bathroom values from the database for the correct post ID
            $bathrooms = get_post_meta($post_id, 'fave_property_bathrooms', true);
            
            // Display the existing values if available
            if (!empty($bathrooms)) {
                echo esc_attr($bathrooms); // Populate the input field
            }
        }
        ?>"
        placeholder="<?php echo houzez_option('cl_bathrooms_plac', 'Enter bathrooms (e.g., 1,2,3)'); ?>" />

    <small class="form-text text-muted">Enter multiple bathroom values separated by commas (e.g., 1,2,3).</small>
</div>