<div class="form-group">
    <label for="prop_beds">
        <?php echo houzez_option('cl_bedrooms', 'Bedrooms') . houzez_required_field('bedrooms'); ?>
    </label>
    <input class="form-control" name="prop_beds" id="prop_beds"
        value="<?php
        // Check if the property is being edited
        if (houzez_edit_property()) {
            // Fetch the correct post ID from the URL (edit_property parameter)
            $post_id = isset($_GET['edit_property']) ? intval($_GET['edit_property']) : get_the_ID();

            // Get the existing bedroom values from the database for the correct post ID
            $bedrooms = get_post_meta($post_id, 'fave_property_bedrooms', true);
            
            // Display the existing values if available
            if (!empty($bedrooms)) {
                echo esc_attr($bedrooms); // Populate the input field
            }
        }
        ?>"
        placeholder="<?php echo houzez_option('cl_bedrooms_plac', 'Enter bedrooms (e.g., 1,3,4)'); ?>" />
    <small class="form-text text-muted">Enter multiple bedroom values separated by commas (e.g., 1,2,3).</small>
</div>