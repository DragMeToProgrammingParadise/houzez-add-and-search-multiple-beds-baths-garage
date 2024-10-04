<div class="form-group">
    <label for="prop_garage">
        <?php echo houzez_option('cl_garage', 'Garages') . houzez_required_field('garages'); ?>
    </label>
    
    <input class="form-control" name="prop_garage" id="prop_garage"
        value="<?php
        // Check if the property is being edited
        if (houzez_edit_property()) {
            // Fetch the correct post ID from the URL (edit_property parameter)
            $post_id = isset($_GET['edit_property']) ? intval($_GET['edit_property']) : get_the_ID();

            // Get the existing garage values from the database for the correct post ID
            $garages = get_post_meta($post_id, 'fave_property_garage', true);
            
            // Display the existing values if available
            if (!empty($garages)) {
                echo esc_attr($garages); // Populate the input field
            }
        }
        ?>"
        placeholder="<?php echo houzez_option('cl_garage_plac', 'Enter garages (e.g., 1,2,3)'); ?>" />

    <small class="form-text text-muted">Enter multiple garage values separated by commas (e.g., 1,2,3).</small>
</div>
