<?php

/*-----------------------------------------------------------------------------------*/
// Submit Property filter
/*-----------------------------------------------------------------------------------*/
add_filter('houzez_submit_listing', 'houzez_submit_listing');

if( !function_exists('houzez_submit_listing') ) {
    function houzez_submit_listing($new_property) {

        $userID = get_current_user_id();
        $post_author = $userID;
        $userIdPackage = $userID;
        $listings_admin_approved = houzez_option('listings_admin_approved');
        $edit_listings_admin_approved = houzez_option('edit_listings_admin_approved');
        $enable_paid_submission = houzez_option('enable_paid_submission');

        $agent_agency_id = houzez_get_agent_agency_id( $userID );
        if( $agent_agency_id ) {
            $userIdPackage = $agent_agency_id;
        }

        // Title
        if( isset( $_POST['prop_title']) ) {
            $new_property['post_title'] = sanitize_text_field( $_POST['prop_title'] );
        }

        if( $enable_paid_submission == 'membership' ) {
            $user_submit_has_no_membership = isset($_POST['user_submit_has_no_membership']) ? $_POST['user_submit_has_no_membership'] : '';
        } else {
            $user_submit_has_no_membership = 'no';
        }

        // Description
        if( isset( $_POST['prop_des'] ) ) {
            $new_property['post_content'] = wp_kses_post( wpautop( wptexturize( $_POST['prop_des'] ) ) );
        }

        if( isset($_POST['property_author']) && ! empty( $_POST['property_author'] ) ) {
            $post_author = $_POST['property_author'];
        }

        $new_property['post_author'] = $post_author;

        $submission_action = $_POST['action'];
        $prop_id = 0;

        if( $submission_action == 'add_property' ) {

            if( houzez_is_admin() ) {
                $new_property['post_status'] = 'publish';
            } else {
                if( $listings_admin_approved != 'yes' && ( $enable_paid_submission == 'no' || $enable_paid_submission == 'free_paid_listing' || $enable_paid_submission == 'membership' ) ) {
                    if( $user_submit_has_no_membership == 'yes' ) {
                        $new_property['post_status'] = 'draft';
                    } else {
                        $new_property['post_status'] = 'publish';
                    }
                } else {
                    if( $user_submit_has_no_membership == 'yes' && $enable_paid_submission = 'membership' ) {
                        $new_property['post_status'] = 'draft';
                    } else {
                        $new_property['post_status'] = 'pending';
                    }
                }
            }

            /*
             * Filter submission arguments before insert into database.
             */
            $new_property = apply_filters( 'houzez_before_submit_property', $new_property );
            $prop_id = wp_insert_post( $new_property );

            if( $prop_id > 0 ) {
                $submitted_successfully = true;
                if( $enable_paid_submission == 'membership'){ // update package status
                    houzez_update_package_listings( $userIdPackage );
                }
            }

        } else if( $submission_action == 'update_property' ) {

            $new_property['ID'] = intval( $_POST['prop_id'] );

            if( get_post_status( intval( $_POST['prop_id'] ) ) == 'draft' ) {
                if( $enable_paid_submission == 'membership') {
                    houzez_update_package_listings($userIdPackage);
                }
                if( $listings_admin_approved != 'yes' && ( $enable_paid_submission == 'no' || $enable_paid_submission == 'free_paid_listing' || $enable_paid_submission == 'membership' ) ) {
                    $new_property['post_status'] = 'publish';
                } else {
                    $new_property['post_status'] = 'pending';
                }
            } elseif( $edit_listings_admin_approved == 'yes' ) {
                    $new_property['post_status'] = 'pending';
            }

            if( ! houzez_user_has_membership($userIdPackage) && $enable_paid_submission == 'membership' ) {
                $new_property['post_status'] = 'draft';

            }

            if( houzez_is_admin() ) {
                $new_property['post_status'] = 'publish';
            }

            /*
             * Filter submission arguments before update property.
             */
            $new_property = apply_filters( 'houzez_before_update_property', $new_property );
            $prop_id = wp_update_post( $new_property );

        }

        if( $prop_id > 0 ) {


            if(class_exists('Houzez_Fields_Builder')) {
                $fields_array = Houzez_Fields_Builder::get_form_fields();
                if(!empty($fields_array)):
                    foreach ( $fields_array as $value ):
                        $field_name = $value->field_id;
                        $field_type = $value->type;

                        if( isset( $_POST[$field_name] ) && !empty( $_POST[$field_name] ) ) {

                            if( $field_type == 'checkbox_list' || $field_type == 'multiselect' ) {
                                delete_post_meta( $prop_id, 'fave_'.$field_name );
                                foreach ( $_POST[ $field_name ] as $value ) {
                                    add_post_meta( $prop_id, 'fave_'.$field_name, sanitize_text_field( $value ) );
                                }
                            } else {
                                update_post_meta( $prop_id, 'fave_'.$field_name, sanitize_text_field( $_POST[$field_name] ) );
                            }

                        } else {
                            delete_post_meta( $prop_id, 'fave_'.$field_name );
                        }

                    endforeach; 
                endif;
            }


            if( $user_submit_has_no_membership == 'yes' ) {
                update_user_meta( $userID, 'user_submit_has_no_membership', $prop_id );
                update_user_meta( $userID, 'user_submitted_without_membership', 'yes' );
            }

            // Add price post meta
            if( isset( $_POST['prop_price'] ) ) {
                update_post_meta( $prop_id, 'fave_property_price', sanitize_text_field( $_POST['prop_price'] ) );

                if( isset( $_POST['prop_label'] ) ) {
                    update_post_meta( $prop_id, 'fave_property_price_postfix', sanitize_text_field( $_POST['prop_label']) );
                }
            }


            // Show Price Placeholder
            update_post_meta($prop_id, 'fave_show_price_placeholder', 0);

            if (isset($_POST['show_price_placeholder'])) {
                $show_placeholder = $_POST['show_price_placeholder'];
                if ($show_placeholder == 'on') {
                    $show_placeholder = 1;
                }

                update_post_meta($prop_id, 'fave_show_price_placeholder', sanitize_text_field($show_placeholder));
            }

            //price placeholder
            if( isset( $_POST['prop_price_placeholder'] ) ) {
                update_post_meta( $prop_id, 'fave_property_price_placeholder', sanitize_text_field( $_POST['prop_price_placeholder']) );
            }

            //price prefix
            if( isset( $_POST['prop_price_prefix'] ) ) {
                update_post_meta( $prop_id, 'fave_property_price_prefix', sanitize_text_field( $_POST['prop_price_prefix']) );
            }

            // Second Price
            if( isset( $_POST['prop_sec_price'] ) ) {
                update_post_meta( $prop_id, 'fave_property_sec_price', sanitize_text_field( $_POST['prop_sec_price'] ) );
            }

            // currency
            if( isset( $_POST['currency'] ) ) {
                update_post_meta( $prop_id, 'fave_currency', sanitize_text_field( $_POST['currency'] ) );
                if(class_exists('Houzez_Currencies')) {
                    $currencies = Houzez_Currencies::get_property_currency_2($prop_id, $_POST['currency']);

                    update_post_meta( $prop_id, 'fave_currency_info', $currencies );
                }
            }


            // Area Size
            if( isset( $_POST['prop_size'] ) ) {
                update_post_meta( $prop_id, 'fave_property_size', sanitize_text_field( $_POST['prop_size'] ) );
            }

            // Area Size Prefix
            if( isset( $_POST['prop_size_prefix'] ) ) {
                update_post_meta( $prop_id, 'fave_property_size_prefix', sanitize_text_field( $_POST['prop_size_prefix'] ) );
            }

            // Land Area Size
            if( isset( $_POST['prop_land_area'] ) ) {
                update_post_meta( $prop_id, 'fave_property_land', sanitize_text_field( $_POST['prop_land_area'] ) );
            }

            // Land Area Size Prefix
            if( isset( $_POST['prop_land_area_prefix'] ) ) {
                update_post_meta( $prop_id, 'fave_property_land_postfix', sanitize_text_field( $_POST['prop_land_area_prefix'] ) );
            }

            // Bedrooms
           // Bedrooms
if (isset($_POST['prop_beds'])) {
    $bedrooms = sanitize_text_field($_POST['prop_beds']);
    
    // Explode the string into an array by comma
    $bedrooms_array = array_map('trim', explode(',', $bedrooms));
    
    // Save the array as a comma-separated string
    update_post_meta($prop_id, 'fave_property_bedrooms', implode(',', $bedrooms_array));
}



            // Rooms
            if( isset( $_POST['prop_rooms'] ) ) {
                update_post_meta( $prop_id, 'fave_property_rooms', sanitize_text_field( $_POST['prop_rooms'] ) );
            }

            // Bathrooms
            if( isset( $_POST['prop_baths'] ) ) {
                update_post_meta( $prop_id, 'fave_property_bathrooms', sanitize_text_field( $_POST['prop_baths'] ) );
            }

            // Garages
            
if (isset($_POST['prop_garage'])) {
    // Sanitize and store the garage values
    $garage_values = sanitize_text_field($_POST['prop_garage']);
    
    // Save the comma-separated garage values to the database
    update_post_meta($prop_id, 'fave_property_garage', $garage_values);
}


            // Garages Size
            if( isset( $_POST['prop_garage_size'] ) ) {
                update_post_meta( $prop_id, 'fave_property_garage_size', sanitize_text_field( $_POST['prop_garage_size'] ) );
            }

            // Virtual Tour
            if( isset( $_POST['virtual_tour'] ) ) {
                update_post_meta( $prop_id, 'fave_virtual_tour', $_POST['virtual_tour'] );
            }

            // Year Built
            if( isset( $_POST['prop_year_built'] ) ) {
                update_post_meta( $prop_id, 'fave_property_year', sanitize_text_field( $_POST['prop_year_built'] ) );
            }

            // Property ID
            $auto_property_id = houzez_option('auto_property_id');
            if( $auto_property_id != 1 ) {
                if (isset($_POST['property_id'])) {
                    update_post_meta($prop_id, 'fave_property_id', sanitize_text_field($_POST['property_id']));
                }
            } else {
                    update_post_meta($prop_id, 'fave_property_id', $prop_id );
            }

            // Property Video Url
            if( isset( $_POST['prop_video_url'] ) ) {
                update_post_meta( $prop_id, 'fave_video_url', sanitize_text_field( $_POST['prop_video_url'] ) );
            }

            // property video image - in case of update
            $property_video_image = "";
            $property_video_image_id = 0;
            if( $submission_action == "update_property" ) {
                $property_video_image_id = get_post_meta( $prop_id, 'fave_video_image', true );
                if ( ! empty ( $property_video_image_id ) ) {
                    $property_video_image_src = wp_get_attachment_image_src( $property_video_image_id, 'houzez-property-detail-gallery' );
                    if( $property_video_image_src ) {
                        $property_video_image = $property_video_image_src[0];
                    }
                }
            }

            // clean up the old meta information related to images when property update
            if( $submission_action == "update_property" ){
                delete_post_meta( $prop_id, 'fave_property_images' );
                delete_post_meta( $prop_id, 'fave_attachments' );
                delete_post_meta( $prop_id, 'fave_agents' );
                delete_post_meta( $prop_id, 'fave_property_agency' );
                delete_post_meta( $prop_id, '_thumbnail_id' );
            }

            // Property Images
            if( isset( $_POST['propperty_image_ids'] ) ) {
                if (!empty($_POST['propperty_image_ids']) && is_array($_POST['propperty_image_ids'])) {
                    $property_image_ids = array();
                    foreach ($_POST['propperty_image_ids'] as $prop_img_id ) {
                        $property_image_ids[] = intval( $prop_img_id );
                        add_post_meta($prop_id, 'fave_property_images', $prop_img_id);

                        // Update the post_parent field for each attachment
                        wp_update_post(array(
                            'ID' => $prop_img_id,
                            'post_parent' => $prop_id
                        ));
                    }

                    // featured image
                    if( isset( $_POST['featured_image_id'] ) ) {
                        $featured_image_id = intval( $_POST['featured_image_id'] );
                        if( in_array( $featured_image_id, $property_image_ids ) ) {
                            update_post_meta( $prop_id, '_thumbnail_id', $featured_image_id );

                            /* if video url is provided but there is no video image then use featured image as video image */
                            if ( empty( $property_video_image ) && !empty( $_POST['prop_video_url'] ) ) {
                                update_post_meta( $prop_id, 'fave_video_image', $featured_image_id );
                            }
                        }
                    } elseif ( ! empty ( $property_image_ids ) ) {
                        update_post_meta( $prop_id, '_thumbnail_id', $property_image_ids[0] );

                        /* if video url is provided but there is no video image then use featured image as video image */
                        if ( empty( $property_video_image ) && !empty( $_POST['prop_video_url'] ) ) {
                            update_post_meta( $prop_id, 'fave_video_image', $property_image_ids[0] );
                        }
                    }
                }
            }

            if( isset( $_POST['propperty_attachment_ids'] ) ) {
                    $property_attach_ids = array();
                    foreach ($_POST['propperty_attachment_ids'] as $prop_atch_id ) {
                        $property_attach_ids[] = intval( $prop_atch_id );
                        add_post_meta($prop_id, 'fave_attachments', $prop_atch_id);
                    }
            }
 

            // Add property type
            if( isset( $_POST['prop_type'] ) && ( $_POST['prop_type'] != '-1' ) ) {
                $type = array_map( 'intval', $_POST['prop_type'] );
                wp_set_object_terms( $prop_id, $type, 'property_type' );
            } else {
                wp_set_object_terms( $prop_id, '', 'property_type' );
            }

            // Add property status
            if( isset( $_POST['prop_status'] ) && ( $_POST['prop_status'] != '-1' ) ) {
                $prop_status = array_map( 'intval', $_POST['prop_status'] );
                wp_set_object_terms( $prop_id, $prop_status, 'property_status' );
            } else {
                wp_set_object_terms( $prop_id, '', 'property_status' );
            }

            // Add property building name
            if( isset( $_POST['prop_building_name'] ) && ( $_POST['prop_building_name'] != '-1' ) ) {
                $prop_building_name = sanitize_text_field( $_POST['prop_building_name'] );
                wp_set_object_terms( $prop_id, $prop_building_name, 'property_building_name' );
            } else {
                wp_set_object_terms( $prop_id, '', 'property_building_name' );
            }

            // Add property status
            if( isset( $_POST['prop_labels'] ) ) {
                $prop_labels = array_map( 'intval', $_POST['prop_labels'] );
                wp_set_object_terms( $prop_id, $prop_labels, 'property_label' );
            } else {
                wp_set_object_terms( $prop_id, '', 'property_label' );
            }

            // Country
            if( isset( $_POST['country'] ) ) {
                $property_country = sanitize_text_field( $_POST['country'] );
                $country_id = wp_set_object_terms( $prop_id, $property_country, 'property_country' );
            } else {
                $default_country = houzez_option('default_country');
                $country_id = wp_set_object_terms( $prop_id, $default_country, 'property_country' );
            }
            
            // Postal Code
            if( isset( $_POST['postal_code'] ) ) {
                update_post_meta( $prop_id, 'fave_property_zip', sanitize_text_field( $_POST['postal_code'] ) );
            }

            
            if( isset( $_POST['locality'] ) ) {
                $property_city = sanitize_text_field( $_POST['locality'] );
                $city_id = wp_set_object_terms( $prop_id, $property_city, 'property_city' );

                $houzez_meta = array();
                $houzez_meta['parent_state'] = isset( $_POST['administrative_area_level_1'] ) ? $_POST['administrative_area_level_1'] : '';
                if( !empty( $city_id) && isset( $_POST['administrative_area_level_1'] ) ) {
                    update_option('_houzez_property_city_' . $city_id[0], $houzez_meta);
                }
            }

            if( isset( $_POST['neighborhood'] ) ) {
                $property_area = sanitize_text_field( $_POST['neighborhood'] );
                $area_id = wp_set_object_terms( $prop_id, $property_area, 'property_area' );

                $houzez_meta = array();
                $houzez_meta['parent_city'] = isset( $_POST['locality'] ) ? $_POST['locality'] : '';
                if( !empty( $area_id) && isset( $_POST['locality'] ) ) {
                    update_option('_houzez_property_area_' . $area_id[0], $houzez_meta);
                }
            }


            // Add property state
            if( isset( $_POST['administrative_area_level_1'] ) ) {
                $property_state = sanitize_text_field( $_POST['administrative_area_level_1'] );
                $state_id = wp_set_object_terms( $prop_id, $property_state, 'property_state' );

                $houzez_meta = array();
                $country_short = isset( $_POST['country'] ) ? $_POST['country'] : '';
                if(!empty($country_short)) {
                   $country_short = strtoupper($country_short); 
                } else {
                    $country_short = '';
                }
                
                $houzez_meta['parent_country'] = $country_short;
                if( !empty( $state_id) ) {
                    update_option('_houzez_property_state_' . $state_id[0], $houzez_meta);
                }
            }
           
            // Add property features
            if( isset( $_POST['prop_features'] ) ) {
                $features_array = array();
                foreach( $_POST['prop_features'] as $feature_id ) {
                    $features_array[] = intval( $feature_id );
                }
                wp_set_object_terms( $prop_id, $features_array, 'property_feature' );
            }

            // additional details
            if( isset( $_POST['additional_features'] ) ) {
                $additional_features = $_POST['additional_features'];
                if( ! empty( $additional_features ) ) {
                    update_post_meta( $prop_id, 'additional_features', $additional_features );
                    update_post_meta( $prop_id, 'fave_additional_features_enable', 'enable' );
                }
            } else {
                update_post_meta( $prop_id, 'additional_features', '' );
            }

            //Floor Plans
            if( isset( $_POST['floorPlans_enable'] ) ) {
                $floorPlans_enable = $_POST['floorPlans_enable'];
                if( ! empty( $floorPlans_enable ) ) {
                    update_post_meta( $prop_id, 'fave_floor_plans_enable', $floorPlans_enable );
                }
            }

            if( isset( $_POST['floor_plans'] ) ) {
                $floor_plans_post = $_POST['floor_plans'];
                if( ! empty( $floor_plans_post ) ) {
                    update_post_meta( $prop_id, 'floor_plans', $floor_plans_post );
                }
            } else {
                update_post_meta( $prop_id, 'floor_plans', '');
            }

            //Multi-units / Sub-properties
            if( isset( $_POST['multiUnits'] ) ) {
                $multiUnits_enable = $_POST['multiUnits'];
                if( ! empty( $multiUnits_enable ) ) {
                    update_post_meta( $prop_id, 'fave_multiunit_plans_enable', $multiUnits_enable );
                }
            }

            if( isset( $_POST['fave_multi_units'] ) ) {
                $fave_multi_units = $_POST['fave_multi_units'];
                if( ! empty( $fave_multi_units ) ) {
                    update_post_meta( $prop_id, 'fave_multi_units', $fave_multi_units );
                }
            } else {
                update_post_meta( $prop_id, 'fave_multi_units', '');
            }

            // Make featured
            if( isset( $_POST['prop_featured'] ) ) {
                $featured = intval( $_POST['prop_featured'] );
                update_post_meta( $prop_id, 'fave_featured', $featured );
            }

            // fave_loggedintoview
            if( isset( $_POST['login-required'] ) ) {
                $featured = intval( $_POST['login-required'] );
                update_post_meta( $prop_id, 'fave_loggedintoview', $featured );
            }

            // Mortgage
            if( $submission_action == 'add_property' ) {
                update_post_meta( $prop_id, 'fave_mortgage_cal', 0 );
                
            }

            // Private Note
            if( isset( $_POST['private_note'] ) ) {
                $private_note = wp_kses_post( $_POST['private_note'] );
                update_post_meta( $prop_id, 'fave_private_note', $private_note );
            }

            // disclaimer 
            if( isset( $_POST['property_disclaimer'] ) ) {
                $property_disclaimer = wp_kses_post( $_POST['property_disclaimer'] );
                update_post_meta( $prop_id, 'fave_property_disclaimer', $property_disclaimer );
            }

            //Energy Class
            if(isset($_POST['energy_class'])) {
                $energy_class = sanitize_text_field($_POST['energy_class']);
                update_post_meta( $prop_id, 'fave_energy_class', $energy_class );
            }
            if(isset($_POST['energy_global_index'])) {
                $energy_global_index = sanitize_text_field($_POST['energy_global_index']);
                update_post_meta( $prop_id, 'fave_energy_global_index', $energy_global_index );
            }
            if(isset($_POST['renewable_energy_global_index'])) {
                $renewable_energy_global_index = sanitize_text_field($_POST['renewable_energy_global_index']);
                update_post_meta( $prop_id, 'fave_renewable_energy_global_index', $renewable_energy_global_index );
            }
            if(isset($_POST['energy_performance'])) {
                $energy_performance = sanitize_text_field($_POST['energy_performance']);
                update_post_meta( $prop_id, 'fave_energy_performance', $energy_performance );
            }
            if(isset($_POST['epc_current_rating'])) {
                $epc_current_rating = sanitize_text_field($_POST['epc_current_rating']);
                update_post_meta( $prop_id, 'fave_epc_current_rating', $epc_current_rating );
            }
            if(isset($_POST['epc_potential_rating'])) {
                $epc_potential_rating = sanitize_text_field($_POST['epc_potential_rating']);
                update_post_meta( $prop_id, 'fave_epc_potential_rating', $epc_potential_rating );
            }


            // Property Payment
            if( isset( $_POST['prop_payment'] ) ) {
                $prop_payment = sanitize_text_field( $_POST['prop_payment'] );
                update_post_meta( $prop_id, 'fave_payment_status', $prop_payment );
            }


            if( isset( $_POST['fave_agent_display_option'] ) ) {

                $prop_agent_display_option = sanitize_text_field( $_POST['fave_agent_display_option'] );

                if( $prop_agent_display_option == 'agent_info' ) {

                    $prop_agent = isset( $_POST['fave_agents'] ) ? $_POST['fave_agents'] : '';
 
                    if(is_array($prop_agent)) {
                        foreach ($prop_agent as $agent) {
                            add_post_meta($prop_id, 'fave_agents', intval($agent) );
                        }
                    }
                    update_post_meta( $prop_id, 'fave_agent_display_option', $prop_agent_display_option );

                    if (houzez_is_agency()) {
                        $user_agency_id = get_user_meta( $userID, 'fave_author_agency_id', true );
                        if( !empty($user_agency_id)) {
                            update_post_meta($prop_id, 'fave_property_agency', $user_agency_id);
                        }
                    }

                } elseif( $prop_agent_display_option == 'agency_info' ) {

                    $user_agency_ids = isset($_POST['fave_property_agency']) ? $_POST['fave_property_agency'] : '';

                    if (houzez_is_agency()) {
                        $user_agency_id = get_user_meta( $userID, 'fave_author_agency_id', true );
                        if( !empty($user_agency_id)) {
                            update_post_meta($prop_id, 'fave_property_agency', $user_agency_id);
                            update_post_meta($prop_id, 'fave_agent_display_option', $prop_agent_display_option);
                        } else {
                            update_post_meta( $prop_id, 'fave_agent_display_option', 'author_info' );
                        }

                    } else {

                        if(is_array($user_agency_ids)) {
                            foreach ($user_agency_ids as $agency) {
                                add_post_meta($prop_id, 'fave_property_agency', intval($agency) );
                            }
                        }
                        update_post_meta($prop_id, 'fave_agent_display_option', $prop_agent_display_option);
                    }
                    
                    
                } else {
                    update_post_meta( $prop_id, 'fave_agent_display_option', $prop_agent_display_option );
                }

            } else {

                if (houzez_is_agency()) {
                    $user_agency_id = get_user_meta( $userID, 'fave_author_agency_id', true );
                    if( !empty($user_agency_id) ) {
                        update_post_meta($prop_id, 'fave_agent_display_option', 'agency_info');
                        update_post_meta($prop_id, 'fave_property_agency', $user_agency_id);
                    } else {
                        update_post_meta( $prop_id, 'fave_agent_display_option', 'author_info' );
                    }

                } elseif(houzez_is_agent()){
                    $user_agent_id = get_user_meta( $userID, 'fave_author_agent_id', true );

                    if ( !empty( $user_agent_id ) ) {

                        update_post_meta($prop_id, 'fave_agent_display_option', 'agent_info');
                        update_post_meta($prop_id, 'fave_agents', $user_agent_id);

                    } else {
                        update_post_meta($prop_id, 'fave_agent_display_option', 'author_info');
                    }

                } else {
                    update_post_meta($prop_id, 'fave_agent_display_option', 'author_info');
                }
            }

            // Address
            if( isset( $_POST['property_map_address'] ) ) {
                update_post_meta( $prop_id, 'fave_property_map_address', sanitize_text_field( $_POST['property_map_address'] ) );
                update_post_meta( $prop_id, 'fave_property_address', sanitize_text_field( $_POST['property_map_address'] ) );
            }

            if( ( isset($_POST['lat']) && !empty($_POST['lat']) ) && (  isset($_POST['lng']) && !empty($_POST['lng'])  ) ) {
                $lat = sanitize_text_field( $_POST['lat'] );
                $lng = sanitize_text_field( $_POST['lng'] );
                $streetView = isset( $_POST['prop_google_street_view'] ) ? sanitize_text_field( $_POST['prop_google_street_view'] ) : '';
                $lat_lng = $lat.','.$lng;

                update_post_meta( $prop_id, 'houzez_geolocation_lat', $lat );
                update_post_meta( $prop_id, 'houzez_geolocation_long', $lng );
                update_post_meta( $prop_id, 'fave_property_location', $lat_lng );
                update_post_meta( $prop_id, 'fave_property_map', '1' );
                update_post_meta( $prop_id, 'fave_property_map_street_view', $streetView );

            }
            

            if( $submission_action == 'add_property' ) {
                do_action( 'houzez_after_property_submit', $prop_id );

                if( houzez_option('add_new_property') == 1 ) {
                    houzez_webhook_post( $_POST, 'houzez_add_new_property' );
                }

            } else if ( $submission_action == 'update_property' ) {
                do_action( 'houzez_after_property_update', $prop_id );

                if( houzez_option('add_new_property') == 1 ) {
                    houzez_webhook_post( $_POST, 'houzez_update_property' );
                }
            }

        return $prop_id;
        }
    }
}





/*-----------------------------------------------------------------------------------*/
// validate Email
/*-----------------------------------------------------------------------------------*/
add_action('wp_ajax_save_as_draft', 'save_property_as_draft');
if( !function_exists('save_property_as_draft') ) {
    function save_property_as_draft() {
        global $current_user;

        wp_get_current_user();
        $userID = $current_user->ID;

        $new_property = array(
            'post_type' => 'property'
        );

        $submission_action = isset($_POST['update_property']) ? $_POST['update_property'] : '';

        // Title
        if( isset( $_POST['prop_title']) ) {
            $new_property['post_title'] = sanitize_text_field( $_POST['prop_title'] );
        }
        // Description
        if( isset( $_POST['description'] ) ) {
            $new_property['post_content'] = wp_kses_post( $_POST['description'] );
        }

        $new_property['post_author'] = $userID;

        $prop_id = 0;
        $new_property['post_status'] = 'draft';

        if( isset($_POST['draft_prop_id']) && !empty( $_POST['draft_prop_id'] ) ) {
            $new_property['ID'] = $_POST['draft_prop_id'];
            $prop_id = wp_update_post( $new_property );
        } else {
            $prop_id = wp_insert_post( $new_property );
        }


        if( $prop_id > 0 ) {
            
            //Custom Fields
            if(class_exists('Houzez_Fields_Builder')) {
                $fields_array = Houzez_Fields_Builder::get_form_fields();
                if(!empty($fields_array)):
                    foreach ( $fields_array as $value ):
                        $field_name = $value->field_id;

                        if( isset( $_POST[$field_name] ) ) {
                            update_post_meta( $prop_id, 'fave_'.$field_name, sanitize_text_field( $_POST[$field_name] ) );
                        }

                    endforeach; endif;
            }
            
            // Add price post meta
            if( isset( $_POST['prop_price'] ) ) {
                update_post_meta( $prop_id, 'fave_property_price', sanitize_text_field( $_POST['prop_price'] ) );

                if( isset( $_POST['prop_label'] ) ) {
                    update_post_meta( $prop_id, 'fave_property_price_postfix', sanitize_text_field( $_POST['prop_label']) );
                }
            }

            // currency
            if( isset( $_POST['currency'] ) ) {
                update_post_meta( $prop_id, 'fave_currency', sanitize_text_field( $_POST['currency'] ) );
                if(class_exists('Houzez_Currencies')) {
                    $currencies = Houzez_Currencies::get_property_currency_2($prop_id, $_POST['currency']);

                    update_post_meta( $prop_id, 'fave_currency_info', $currencies );
                }
            }

            //price prefix
            if( isset( $_POST['prop_price_prefix'] ) ) {
                update_post_meta( $prop_id, 'fave_property_price_prefix', sanitize_text_field( $_POST['prop_price_prefix']) );
            }

            // Second Price
            if( isset( $_POST['prop_sec_price'] ) ) {
                update_post_meta( $prop_id, 'fave_property_sec_price', sanitize_text_field( $_POST['prop_sec_price'] ) );
            }

            // Area Size
            if( isset( $_POST['prop_size'] ) ) {
                update_post_meta( $prop_id, 'fave_property_size', sanitize_text_field( $_POST['prop_size'] ) );
            }

            // Area Size Prefix
            if( isset( $_POST['prop_size_prefix'] ) ) {
                update_post_meta( $prop_id, 'fave_property_size_prefix', sanitize_text_field( $_POST['prop_size_prefix'] ) );
            }
            // Land Area Size
            if( isset( $_POST['prop_land_area'] ) ) {
                update_post_meta( $prop_id, 'fave_property_land', sanitize_text_field( $_POST['prop_land_area'] ) );
            }

            // Land Area Size Prefix
            if( isset( $_POST['prop_land_area_prefix'] ) ) {
                update_post_meta( $prop_id, 'fave_property_land_postfix', sanitize_text_field( $_POST['prop_land_area_prefix'] ) );
            }

            // Bedrooms
            
if (isset($_POST['prop_beds'])) {
    $bedrooms = sanitize_text_field($_POST['prop_beds']);
    
    // Explode the string into an array by comma
    $bedrooms_array = array_map('trim', explode(',', $bedrooms));
    
    // Save the array as a comma-separated string
    update_post_meta($prop_id, 'fave_property_bedrooms', implode(',', $bedrooms_array));
}



            // Bathrooms
            
            if (isset($_POST['prop_baths'])) {
    $bathroom = sanitize_text_field($_POST['prop_baths']);
    
    // Explode the string into an array by comma
    $bathroom_array = array_map('trim', explode(',', $bathroom));
    
    // Save the array as a comma-separated string
    update_post_meta($prop_id, 'fave_property_bathrooms', implode(',', $bathroom_array));
}
            

            // Rooms
            if( isset( $_POST['prop_rooms'] ) ) {
                update_post_meta( $prop_id, 'fave_property_rooms', sanitize_text_field( $_POST['prop_rooms'] ) );
            }

            // Garages
            
if (isset($_POST['prop_garage'])) {
    // Sanitize and store the garage values
    $garage_values = sanitize_text_field($_POST['prop_garage']);
    
    // Save the comma-separated garage values to the database
    update_post_meta($prop_id, 'fave_property_garage', $garage_values);
}


            // Garages Size
            if( isset( $_POST['prop_garage_size'] ) ) {
                update_post_meta( $prop_id, 'fave_property_garage_size', sanitize_text_field( $_POST['prop_garage_size'] ) );
            }

            // Virtual Tour
            if( isset( $_POST['virtual_tour'] ) ) {
                update_post_meta( $prop_id, 'fave_virtual_tour', $_POST['virtual_tour'] );
            }

            // Year Built
            if( isset( $_POST['prop_year_built'] ) ) {
                update_post_meta( $prop_id, 'fave_property_year', sanitize_text_field( $_POST['prop_year_built'] ) );
            }

            // Property ID
            $auto_property_id = houzez_option('auto_property_id');
            if( $auto_property_id != 1 ) {
                if (isset($_POST['property_id'])) {
                    update_post_meta($prop_id, 'fave_property_id', sanitize_text_field($_POST['property_id']));
                }
            } else {
                update_post_meta($prop_id, 'fave_property_id', $prop_id );
            }

            // Property Video Url
            if( isset( $_POST['prop_video_url'] ) ) {
                update_post_meta( $prop_id, 'fave_video_url', sanitize_text_field( $_POST['prop_video_url'] ) );
            }

            // property video image - in case of update
            $property_video_image = "";
            $property_video_image_id = 0;
            if( $submission_action == "update_property" ) {
                $property_video_image_id = get_post_meta( $prop_id, 'fave_video_image', true );
                if ( ! empty ( $property_video_image_id ) ) {
                    $property_video_image_src = wp_get_attachment_image_src( $property_video_image_id, 'houzez-property-detail-gallery' );
                    $property_video_image = $property_video_image_src[0];
                }
            }

            // clean up the old meta information related to images when property update
            if( $submission_action == "update_property" ){
                delete_post_meta( $prop_id, 'fave_property_images' );
                delete_post_meta( $prop_id, 'fave_attachments' );
                delete_post_meta( $prop_id, 'fave_agents' );
                delete_post_meta( $prop_id, 'fave_property_agency' );
                delete_post_meta( $prop_id, '_thumbnail_id' );
            }

            // Property Images
            if( isset( $_POST['propperty_image_ids'] ) ) {
                if (!empty($_POST['propperty_image_ids']) && is_array($_POST['propperty_image_ids'])) {
                    $property_image_ids = array();
                    foreach ($_POST['propperty_image_ids'] as $prop_img_id ) {
                        $property_image_ids[] = intval( $prop_img_id );
                        add_post_meta($prop_id, 'fave_property_images', $prop_img_id);
                    }

                    // featured image
                    if( isset( $_POST['featured_image_id'] ) ) {
                        $featured_image_id = intval( $_POST['featured_image_id'] );
                        if( in_array( $featured_image_id, $property_image_ids ) ) {
                            update_post_meta( $prop_id, '_thumbnail_id', $featured_image_id );

                            /* if video url is provided but there is no video image then use featured image as video image */
                            if ( empty( $property_video_image ) && !empty( $_POST['prop_video_url'] ) ) {
                                update_post_meta( $prop_id, 'fave_video_image', $featured_image_id );
                            }
                        }
                    } elseif ( ! empty ( $property_image_ids ) ) {
                        update_post_meta( $prop_id, '_thumbnail_id', $property_image_ids[0] );

                        /* if video url is provided but there is no video image then use featured image as video image */
                        if ( empty( $property_video_image ) && !empty( $_POST['prop_video_url'] ) ) {
                            update_post_meta( $prop_id, 'fave_video_image', $property_image_ids[0] );
                        }
                    }
                }
            }

            if( isset( $_POST['propperty_attachment_ids'] ) ) {
                    $property_attach_ids = array();
                    foreach ($_POST['propperty_attachment_ids'] as $prop_atch_id ) {
                        $property_attach_ids[] = intval( $prop_atch_id );
                        add_post_meta($prop_id, 'fave_attachments', $prop_atch_id);
                    }
            }


            // Add property type
            if( isset( $_POST['prop_type'] ) && ( $_POST['prop_type'] != '-1' ) ) {
                $type = array_map( 'intval', $_POST['prop_type'] );
                wp_set_object_terms( $prop_id, $type, 'property_type' );
            }

            // Add property status
            if( isset( $_POST['prop_status'] ) && ( $_POST['prop_status'] != '-1' ) ) {
                $prop_status = array_map( 'intval', $_POST['prop_status'] );
                wp_set_object_terms( $prop_id, $prop_status, 'property_status' );
            }

            // Building Name
            if( isset( $_POST['prop_building_name'] ) ) {
                $property_building_name = sanitize_text_field( $_POST['prop_building_name'] );
                $property_building_name_id = wp_set_object_terms( $prop_id, $property_building_name, 'property_building_name' );
            }else {
                wp_set_object_terms( $prop_id, '', 'property_building_name' );
            }

            // Add property label
            if( isset( $_POST['prop_labels'] ) ) {
                $prop_labels = array_map( 'intval', $_POST['prop_labels'] );
                wp_set_object_terms( $prop_id, $prop_labels, 'property_label' );
            }


            // Country
            if( isset( $_POST['country'] ) ) {
                $property_country = sanitize_text_field( $_POST['country'] );
                $country_id = wp_set_object_terms( $prop_id, $property_country, 'property_country' );
            } else {
                $default_country = houzez_option('default_country');
                $country_id = wp_set_object_terms( $prop_id, $default_country, 'property_country' );
            }

            
            // Postal Code
            if( isset( $_POST['postal_code'] ) ) {
                update_post_meta( $prop_id, 'fave_property_zip', sanitize_text_field( $_POST['postal_code'] ) );
            }

            
            if( isset( $_POST['locality'] ) ) {
                $property_city = sanitize_text_field( $_POST['locality'] );
                $city_id = wp_set_object_terms( $prop_id, $property_city, 'property_city' );

                $houzez_meta = array();
                $houzez_meta['parent_state'] = isset( $_POST['administrative_area_level_1'] ) ? $_POST['administrative_area_level_1'] : '';
                if( !empty( $city_id) ) {
                    update_option('_houzez_property_city_' . $city_id[0], $houzez_meta);
                }
            }

            if( isset( $_POST['neighborhood'] ) ) {
                $property_area = sanitize_text_field( $_POST['neighborhood'] );
                $area_id = wp_set_object_terms( $prop_id, $property_area, 'property_area' );

                $houzez_meta = array();
                $houzez_meta['parent_city'] = isset( $_POST['locality'] ) ? $_POST['locality'] : '';
                if( !empty( $area_id) ) {
                    update_option('_houzez_property_area_' . $area_id[0], $houzez_meta);
                }
            }


            // Add property state
            if( isset( $_POST['administrative_area_level_1'] ) ) {
                $property_state = sanitize_text_field( $_POST['administrative_area_level_1'] );
                $state_id = wp_set_object_terms( $prop_id, $property_state, 'property_state' );

                $houzez_meta = array();
                $country_short = isset( $_POST['country'] ) ? $_POST['country'] : '';
                if(!empty($country_short)) {
                   $country_short = strtoupper($country_short); 
                } else {
                    $country_short = '';
                }
                
                $houzez_meta['parent_country'] = $country_short;
                if( !empty( $state_id) ) {
                    update_option('_houzez_property_state_' . $state_id[0], $houzez_meta);
                }
            }

            // Add property features
            if( isset( $_POST['prop_features'] ) ) {
                $features_array = array();
                foreach( $_POST['prop_features'] as $feature_id ) {
                    $features_array[] = intval( $feature_id );
                }
                wp_set_object_terms( $prop_id, $features_array, 'property_feature' );
            }

            // additional details
            if( isset( $_POST['additional_features'] ) ) {
                $additional_features = $_POST['additional_features'];
                if( ! empty( $additional_features ) ) {
                    update_post_meta( $prop_id, 'additional_features', $additional_features );
                    update_post_meta( $prop_id, 'fave_additional_features_enable', 'enable' );
                }
            }

            //Floor Plans
            if( isset( $_POST['floorPlans_enable'] ) ) {
                $floorPlans_enable = $_POST['floorPlans_enable'];
                if( ! empty( $floorPlans_enable ) ) {
                    update_post_meta( $prop_id, 'fave_floor_plans_enable', $floorPlans_enable );
                }
            }

            if( isset( $_POST['floor_plans'] ) ) {
                $floor_plans_post = $_POST['floor_plans'];
                if( ! empty( $floor_plans_post ) ) {
                    update_post_meta( $prop_id, 'floor_plans', $floor_plans_post );
                }
            }

            //Multi-units / Sub-properties
            if( isset( $_POST['multiUnits'] ) ) {
                $multiUnits_enable = $_POST['multiUnits'];
                if( ! empty( $multiUnits_enable ) ) {
                    update_post_meta( $prop_id, 'fave_multiunit_plans_enable', $multiUnits_enable );
                }
            }

            if( isset( $_POST['fave_multi_units'] ) ) {
                $fave_multi_units = $_POST['fave_multi_units'];
                if( ! empty( $fave_multi_units ) ) {
                    update_post_meta( $prop_id, 'fave_multi_units', $fave_multi_units );
                }
            }

            // Make featured
            if( isset( $_POST['prop_featured'] ) ) {
                $featured = intval( $_POST['prop_featured'] );
                update_post_meta( $prop_id, 'fave_featured', $featured );
            }

            // Private Note
            if( isset( $_POST['private_note'] ) ) {
                $private_note = wp_kses_post( $_POST['private_note'] );
                update_post_meta( $prop_id, 'fave_private_note', $private_note );
            }

            // disclaimer 
            if( isset( $_POST['property_disclaimer'] ) ) {
                $property_disclaimer = wp_kses_post( $_POST['property_disclaimer'] );
                update_post_meta( $prop_id, 'fave_property_disclaimer', $property_disclaimer );
            }

            // Property Payment
            if( isset( $_POST['prop_payment'] ) ) {
                $prop_payment = sanitize_text_field( $_POST['prop_payment'] );
                update_post_meta( $prop_id, 'fave_payment_status', $prop_payment );
            }

             //Energy Class
            if(isset($_POST['energy_class'])) {
                $energy_class = sanitize_text_field($_POST['energy_class']);
                update_post_meta( $prop_id, 'fave_energy_class', $energy_class );
            }
            if(isset($_POST['energy_global_index'])) {
                $energy_global_index = sanitize_text_field($_POST['energy_global_index']);
                update_post_meta( $prop_id, 'fave_energy_global_index', $energy_global_index );
            }
            if(isset($_POST['renewable_energy_global_index'])) {
                $renewable_energy_global_index = sanitize_text_field($_POST['renewable_energy_global_index']);
                update_post_meta( $prop_id, 'fave_renewable_energy_global_index', $renewable_energy_global_index );
            }
            if(isset($_POST['energy_performance'])) {
                $energy_performance = sanitize_text_field($_POST['energy_performance']);
                update_post_meta( $prop_id, 'fave_energy_performance', $energy_performance );
            }

            // Property Agent
            if( isset( $_POST['fave_agent_display_option'] ) ) {

                $prop_agent_display_option = sanitize_text_field( $_POST['fave_agent_display_option'] );
                if( $prop_agent_display_option == 'agent_info' ) {

                    $prop_agent = sanitize_text_field( $_POST['fave_agents'] );
                    update_post_meta( $prop_id, 'fave_agent_display_option', $prop_agent_display_option );
                    update_post_meta( $prop_id, 'fave_agents', $prop_agent );

                } else {
                    update_post_meta( $prop_id, 'fave_agent_display_option', $prop_agent_display_option );
                }

            } else {
                update_post_meta( $prop_id, 'fave_agent_display_option', 'author_info' );
            }

            // Address
            if( isset( $_POST['property_map_address'] ) ) {
                update_post_meta( $prop_id, 'fave_property_map_address', sanitize_text_field( $_POST['property_map_address'] ) );
                update_post_meta( $prop_id, 'fave_property_address', sanitize_text_field( $_POST['property_map_address'] ) );
            }

            if( ( isset($_POST['lat']) && !empty($_POST['lat']) ) && (  isset($_POST['lng']) && !empty($_POST['lng'])  ) ) {
                $lat = sanitize_text_field( $_POST['lat'] );
                $lng = sanitize_text_field( $_POST['lng'] );
                $streetView = sanitize_text_field( $_POST['prop_google_street_view'] );
                $lat_lng = $lat.','.$lng;

                update_post_meta( $prop_id, 'houzez_geolocation_lat', $lat );
                update_post_meta( $prop_id, 'houzez_geolocation_long', $lng );
                update_post_meta( $prop_id, 'fave_property_location', $lat_lng );
                update_post_meta( $prop_id, 'fave_property_map', '1' );
                update_post_meta( $prop_id, 'fave_property_map_street_view', $streetView );

            }
            
        }
        echo json_encode( array( 'success' => true, 'property_id' => $prop_id, 'msg' => esc_html__('Successfull', 'houzez') ) );
        wp_die();
    }
}





add_action( 'wp_ajax_nopriv_houzez_get_auto_complete_search', 'houzez_get_auto_complete_search' );
add_action( 'wp_ajax_houzez_get_auto_complete_search', 'houzez_get_auto_complete_search' );

if ( !function_exists( 'houzez_get_auto_complete_search' ) ) {

    function houzez_get_auto_complete_search() {
        $current_language = apply_filters( 'wpml_current_language', null );
        global $wpdb;
        $key = $_POST['key'];
        $key = $wpdb->esc_like($key);
        $keyword_field = houzez_option('keyword_field');
        $houzez_local = houzez_get_localization();
        $response = '';

        if( $keyword_field != 'prop_city_state_county' ) {

            if ( $keyword_field == "prop_title" ) {

                $table = $wpdb->posts;

                $data = $wpdb->get_results( 
                    $wpdb->prepare(
                        "SELECT DISTINCT * FROM {$table} WHERE post_type = %s AND post_status = %s AND (post_title LIKE %s OR post_content LIKE %s)",
                        'property',
                        'publish',
                        '%' . $key . '%',
                        '%' . $key . '%'
                    )
                );


                if ( sizeof( $data ) != 0 ) {

                    $search_url = add_query_arg( 'keyword', $key, houzez_get_search_template_link() );

                    echo '<div class="auto-complete-keyword">';
                    echo '<ul class="list-group">';

                    $new_data = array();

                    foreach ( $data as $post ) {

                        $propID = $post->ID;

                        $post_language = apply_filters( 'wpml_element_language_code', null, array('element_id' => $propID, 'element_type' => 'post'));

                        if ($post_language !== $current_language) {
                            continue;
                        }

                        $new_data [] = $post;
                        
                        // echo $prop_thumb = get_the_post_thumbnail( $propID );
                        
                        if (isset($_POST['prop_beds'])) {
                            $bedrooms = sanitize_text_field($_POST['prop_beds']);
                            
                            // Explode the string into an array by comma
                            $bedrooms_array = array_map('trim', explode(',', $bedrooms));
                            
                            // Save the array as a comma-separated string
                            update_post_meta($prop_id, 'fave_property_bedrooms', implode(',', $bedrooms_array));
                        }

                if (isset($_POST['prop_baths'])) {
                            $bathrooms = sanitize_text_field($_POST['prop_baths']);
                            
                            // Explode the string into an array by comma
                            $bathrooms_array = array_map('trim', explode(',', $bathrooms));
                            
                            // Save the array as a comma-separated string
                            update_post_meta($prop_id, 'fave_property_bathrooms', implode(',', $bedrooms_array));
                        }
                        
                        $prop_size = houzez_get_listing_area_size( $propID );
                        $prop_type = houzez_taxonomy_simple('property_type');
                        $prop_img = get_the_post_thumbnail_url( $propID, array ( 40, 40 ) );

                        if ( empty( $prop_img ) ) {
                            $prop_img = houzez_get_image_placeholder_url('thumbnail');
                        }

                        ?>

                        <li class="list-group-item" data-text="<?php echo $post->post_title; ?>">
                            <div class="d-flex align-items-center">
                                <div class="auto-complete-image-wrap">
                                    <a href="<?php the_permalink( $propID ); ?>">
                                        <img class="img-fluid rounded" src="<?php echo $prop_img; ?>" width="40" height="40" alt="image">
                                    </a>    
                                </div><!-- auto-complete-image-wrap -->
                                <div class="auto-complete-content-wrap ml-3">
                                    <div class="auto-complete-title">
                                        <a href="<?php the_permalink( $propID ); ?>"><?php echo $post->post_title; ?></a>
                                    </div>
                                </div><!-- auto-complete-content-wrap -->
                            </div><!-- d-flex -->
                        </li><!-- list-group-item -->
                        <?php

                    }

                    echo '</ul>';

                    echo '<div class="auto-complete-footer">';
                        echo '<span class="auto-complete-count"><i class="houzez-icon icon-pin mr-1"></i> ' . sizeof( $new_data ) . ' '.$houzez_local['listins_found'].'</span>';
                        echo '<a target="_blank" href="' . $search_url . '" class="search-result-view">'.$houzez_local['view_all_results'].'</a>';
                    echo '</div>';


                    echo '</div>';

                } else {

               ?>
               <ul class="list-group">
                   <li class="list-group-item"> <?php echo $houzez_local['auto_result_not_found']; ?> </li>
               </ul>
               <?php

           }

       } else if ( $keyword_field == "prop_address" ) {

                $posts_table = $wpdb->posts;
                $postmeta_table = $wpdb->postmeta;

                $data = $wpdb->get_results( 
                    $wpdb->prepare(
                            "SELECT DISTINCT post.ID, meta.meta_value FROM {$postmeta_table} AS meta INNER JOIN $posts_table AS post ON meta.post_id=post.ID AND post.post_type='property' and post.post_status='publish' AND meta.meta_value LIKE %s AND ( meta.meta_key='fave_property_map_address' OR meta.meta_key='fave_property_zip' OR meta.meta_key='fave_property_address' OR meta.meta_key='fave_property_id' )",
                            '%' . $key . '%'
                        )
                );

                if ( sizeof( $data ) != 0 ) {

                    echo '<ul class="list-group">';

                    $new_data = array();

                    foreach ( $data as $title ) {

                        $post_language = apply_filters( 'wpml_element_language_code', null, array('element_id' => $title->ID, 'element_type' => 'post'));

                        if ($post_language !== $current_language) {
                            continue;
                        }

                        $new_data [] = $title;
                        ?>
                        
                        <li class="list-group-item" data-text="<?php echo $title->meta_value; ?>">
                            <div class="d-flex align-items-center">
                                <div class="auto-complete-content-wrap flex-fill">
                                    <i class="houzez-icon icon-pin mr-1"></i> <?php echo $title->meta_value; ?>
                                </div><!-- auto-complete-content-wrap -->
                            </div><!-- d-flex -->
                        </li>
                        <?php

                    }

                    echo '</ul>';

                } else {

               ?>
               <ul class="list-group">
                   <li class="list-group-item"> <?php echo $houzez_local['auto_result_not_found']; ?> </li>
               </ul>
               <?php

           }

            }

        } else {
            $terms_table = $wpdb->terms;
            $term_taxonomy = $wpdb->term_taxonomy;

            $data = $wpdb->get_results( 
                $wpdb->prepare(
                    "SELECT DISTINCT * FROM {$terms_table} as term INNER JOIN $term_taxonomy AS term_taxonomy ON term.term_id = term_taxonomy.term_id AND term.name LIKE %s AND ( term_taxonomy.taxonomy = %s OR term_taxonomy.taxonomy = %s OR term_taxonomy.taxonomy = %s )",
                    '%' . $key . '%',
                    'property_area',
                    'property_city',
                    'property_state'
                )
            );

            if ( sizeof( $data ) != 0 ) {

                echo '<ul class="list-group">';

                $new_data = array();

                foreach ( $data as $term ) {
        
                    $term_language = apply_filters( 'wpml_element_language_code', null, array('element_id' => $term->term_id, 'element_type' => 'category'));

                    if ($term_language !== $current_language) {
                        continue;
                    }

                    $new_data [] = $term;
                }

                // Sort the $new_data array based on the taxonomy
                usort($new_data, function($a, $b) {
                    $order = ['property_state' => 1, 'property_city' => 2, 'property_area' => 3];
                    return $order[$a->taxonomy] - $order[$b->taxonomy];
                });

                // Display the sorted terms
                foreach ($new_data as $term) {
                    

                    $taxonomy_img_id = get_term_meta( $term->term_id, 'fave_taxonomy_img', true );

                    $term_type = explode( 'property_', $term->taxonomy );
                    $term_type = $term_type[1];
                    $prop_count = $term->count;

                    if ( empty( $taxonomy_img_id ) ) {
                       $term_img = '<img src="http://placehold.it/40x40" width="40" height="40">';
                   } else {
                        $term_img = wp_get_attachment_image( $taxonomy_img_id, array( 40, 40 ), array( "class" => "img-fluid rounded" ) );
                   }

                   if( $term_type == 'state' ) {
                        $term_type = $houzez_local['auto_state'];
                   } else if( $term_type == 'city' ) {
                        $term_type = $houzez_local['auto_city'];
                   } else if( $term_type == 'area' ) {
                        $term_type = $houzez_local['auto_area'];
                   }

                    ?>
                    <li class="list-group-item" data-text="<?php echo $term->name; ?>">
                        <div class="d-flex align-items-center">
                            <div class="auto-complete-image-wrap">
                                <a href="<?php echo get_term_link( $term ); ?>">
                                    <?php echo $term_img; ?>
                                </a>    
                            </div><!-- auto-complete-image-wrap -->
                            <div class="auto-complete-content-wrap flex-fill ml-3">
                                <div class="auto-complete-title"><?php echo esc_attr($term->name); ?></div>
                                <ul class="item-amenities">
                                    <li><?php if ( !empty( $term_type ) ) { ?>
                                    <?php echo $term_type; ?>
                                <?php } ?>
                                <?php if ( !empty( $prop_count ) ) : ?>
                                     - <?php echo $prop_count . ' ' . $houzez_local['auto_listings']; ?>
                                <?php endif; ?></li>
                                </ul>
                            </div><!-- auto-complete-content-wrap -->
                            <div class="auto-complete-content-wrap ml-3">
                                <a target="_blank" href="<?php echo get_term_link( $term ); ?>" class="search-result-view"><?php echo $houzez_local['auto_view_lists']; ?></a>
                            </div><!-- auto-complete-content-wrap -->
                        </div><!-- d-flex -->
                    </li>
                    <?php

                }

                echo '</ul>';

            } else {

               ?>
               <ul class="list-group">
                   <li class="list-group-item"> <?php echo $houzez_local['auto_result_not_found']; ?> </li>
               </ul>
               <?php

           }

        }

        wp_die();

    }

}

if ( !function_exists( 'houzez_loadmore_properties' ) ) {
    function houzez_loadmore_properties() {
        global $houzez_local;

        $houzez_local = houzez_get_localization();
        $fake_loop_offset = 0;

        $tax_query = array();
        $card_version = sanitize_text_field($_POST['card_version']);
        $property_type = houzez_traverse_comma_string($_POST['type']);
        $property_status = houzez_traverse_comma_string($_POST['status']);
        $property_building_name = houzez_traverse_comma_string($_POST['property_building_name']);
        $property_state = houzez_traverse_comma_string($_POST['state']);
        $property_city = houzez_traverse_comma_string($_POST['city']);
        $property_country = houzez_traverse_comma_string($_POST['country']);
        $property_area = houzez_traverse_comma_string($_POST['area']);
        $property_label = houzez_traverse_comma_string($_POST['label']);
        $houzez_user_role = $_POST['user_role'];
        $featured_prop = $_POST['featured_prop'];
        $posts_limit = $_POST['prop_limit'];
        $sort_by = $_POST['sort_by'];
        $offset = $_POST['offset'];
        $paged = $_POST['paged'];

        $wp_query_args = array(
            'ignore_sticky_posts' => 1
        );

        $tax_query = array();
        $meta_query = array();

        if( !empty($houzez_user_role) ) {
            $role_ids = houzez_author_ids_by_role( $houzez_user_role );
            if (!empty($role_ids)) {
                $wp_query_args['author__in'] = $role_ids;
            }
        }

        $sort_taxonomy = isset($_GET['tax']) ? $_GET['tax'] : '';

        if ( isset( $_GET['tab'] ) && isset( $_GET['tax'] ) ) {
            $tax_query[] = array(
                'taxonomy' => esc_attr($_GET['tax']),
                'field' => 'slug',
                'terms' => esc_attr($_GET['tab'])
            );
        }

        if (!empty($property_type) && $sort_taxonomy != 'property_type') {
            $tax_query[] = array(
                'taxonomy' => 'property_type',
                'field' => 'slug',
                'terms' => is_array($property_type) ? $property_type : houzez_traverse_comma_string($property_type)

            );
        }
        if (!empty($property_status) && $sort_taxonomy != 'property_status') {
            $tax_query[] = array(
                'taxonomy' => 'property_status',
                'field' => 'slug',
                'terms' => is_array($property_status) ? $property_status : houzez_traverse_comma_string($property_status)

            );
        }
        if (!empty($property_building_name) && $sort_taxonomy != 'property_building_name') {
            $tax_query[] = array(
                'taxonomy' => 'property_building_name',
                'field' => 'slug',
                'terms' => is_array($property_building_name) ? $property_building_name : houzez_traverse_comma_string($property_building_name)

            );
        }
        if (!empty($property_state)) {
            $tax_query[] = array(
                'taxonomy' => 'property_state',
                'field' => 'slug',
                'terms' => is_array($property_state) ? $property_state : houzez_traverse_comma_string($property_state)

            );
        }

        if (!empty($property_country)) {
            $tax_query[] = array(
                'taxonomy' => 'property_country',
                'field' => 'slug',
                'terms' => is_array($property_country) ? $property_country : houzez_traverse_comma_string($property_country)
            );
        }
        if (!empty($property_city) && $sort_taxonomy != 'property_city' ) {
            $tax_query[] = array(
                'taxonomy' => 'property_city',
                'field' => 'slug',
                'terms' => is_array($property_city) ? $property_city : houzez_traverse_comma_string($property_city)
            );
        }
        if (!empty($property_area)) {
            $tax_query[] = array(
                'taxonomy' => 'property_area',
                'field' => 'slug',
                'terms' =>is_array($property_area) ? $property_area : houzez_traverse_comma_string($property_area)
            );
        }
        if (!empty($property_label)) {
            $tax_query[] = array(
                'taxonomy' => 'property_label',
                'field' => 'slug',
                'terms' => is_array($property_label) ? $property_label : houzez_traverse_comma_string($property_label)
            );
        }

        if ( !empty($properties_by_agents) && count( $properties_by_agents ) >= 1 ) {
            $meta_query[] = array(
                'meta_key'     => 'fave_agents',
                'meta_value'   => $properties_by_agents,
                'compare' => 'IN',
            );
            $meta_query[] = array(
                'meta_key'     => 'fave_agent_display_option',
                'meta_value'   => 'agent_info',
                'compare' => '=',
            );
        }

        if (!empty($furnishing_options)) {
            $meta_query[] = array(
                'meta_key'     => 'fave_furnishing-options',
                'meta_value'   => $furnishing_options,
                'compare' => 'IN',
            );
        }



        if ( !empty($properties_by_agencies) && count( $properties_by_agencies ) >= 1 ) {
            $meta_query[] = array(
                'meta_key'     => 'fave_property_agency',
                'meta_value'   => $properties_by_agencies,
                'compare' => 'IN',
            );

            $meta_query[] = array(
                'meta_key'     => 'fave_agent_display_option',
                'meta_value'   => 'agency_info',
                'compare' => '=',
            );
        }


        if (!empty($min_price) && !empty($max_price)) {
            $min_price = doubleval(houzez_clean($min_price));
            $max_price = doubleval(houzez_clean($max_price));

            if ($min_price >= 0 && $max_price > $min_price) {
                $meta_query[] = array(
                    'meta_key' => 'fave_property_price',
                    'meta_value' => array($min_price, $max_price),
                    'type' => 'NUMERIC',
                    'compare' => 'BETWEEN',
                );
            }
        } else if (!empty($min_price)) {
            $min_price = doubleval(houzez_clean($min_price));
            if ($min_price >= 0) {
                $meta_query[] = array(
                    'meta_key' => 'fave_property_price',
                    'meta_value' => $min_price,
                    'type' => 'NUMERIC',
                    'compare' => '>=',
                );
            }
        } else if (!empty($max_price)) {
            $max_price = doubleval(houzez_clean($max_price));
            if ($max_price >= 0) {
                $meta_query[] = array(
                    'meta_key' => 'fave_property_price',
                    'meta_value' => $max_price,
                    'type' => 'NUMERIC',
                    'compare' => '<=',
                );
            }
        }


        $property_ids_array = explode(',', $property_ids);

        if (!empty($property_ids)) {
            $wp_query_args['post__in'] = $property_ids_array;
        }

        $tax_count = count( $tax_query );

        if( $tax_count > 1 ) {
            $tax_query['relation'] = 'AND';
        }

        $wp_query_args['tax_query'] = $tax_query;

        $meta_count = count($meta_query);
        if( $meta_count > 1 ) {
            $meta_query['relation'] = 'AND';
        }

        if ($meta_count > 0) {
            $wp_query_args['meta_query'] = $meta_query;
        }

        if ( isset( $_GET['sortby'] ) ) {
            $sort_by = $_GET['sortby'];
        }

        if ( $sort_by == 'a_title' ) {
            $wp_query_args['orderby'] = 'title';
            $wp_query_args['order'] = 'ASC';
        } else if ( $sort_by == 'd_title' ) {
            $wp_query_args['orderby'] = 'title';
            $wp_query_args['order'] = 'DESC';
        } else if ( $sort_by == 'a_price' ) {
            $wp_query_args['orderby'] = 'meta_value_num';
            $wp_query_args['meta_key'] = 'fave_property_price';
            $wp_query_args['order'] = 'ASC';
        } else if ( $sort_by == 'd_price' ) {
            $wp_query_args['orderby'] = 'meta_value_num';
            $wp_query_args['meta_key'] = 'fave_property_price';
            $wp_query_args['order'] = 'DESC';
        } else if ( $sort_by == 'a_date' ) {
            $wp_query_args['orderby'] = 'date';
            $wp_query_args['order'] = 'ASC';
        } else if ( $sort_by == 'd_date' ) {
            $wp_query_args['orderby'] = 'date';
            $wp_query_args['order'] = 'DESC';
        } else if ( $sort_by == 'featured_top' ) {
            $wp_query_args['orderby'] = 'meta_value';
            $wp_query_args['meta_key'] = 'fave_featured';
            $wp_query_args['order'] = 'DESC';
        } else if ( $sort_by == 'random' ) {
            $wp_query_args['orderby'] = 'rand';
            $wp_query_args['order'] = 'DESC';
        } else if ( $sort_by == 'featured_first' ) {
            $wp_query_args['orderby'] = 'meta_value date';
            $wp_query_args['meta_key'] = 'fave_featured';
        } else if ( $sort_by == 'featured_first_random' ) {
            $wp_query_args['meta_key'] = 'fave_featured';
            $wp_query_args['orderby'] = 'meta_value DESC rand';
        }

        if (!empty($featured_prop)) {

            if( $featured_prop == "yes" ) {
                $wp_query_args['meta_key'] = 'fave_featured';
                $wp_query_args['meta_value'] = '1';
            } else {
                $wp_query_args['meta_key'] = 'fave_featured';
                $wp_query_args['meta_value'] = '0';
            }
        }

        $wp_query_args['post_status'] = 'publish';

        if (empty($posts_limit)) {
            $posts_limit = get_option('posts_per_page');
        }
        $wp_query_args['posts_per_page'] = $posts_limit;

        if (!empty($paged)) {
            $wp_query_args['paged'] = $paged;
        } else {
            $wp_query_args['paged'] = 1;
        }

        if (!empty($offset) and $paged > 1) {
            $wp_query_args['offset'] = $offset + ( ($paged - 1) * $posts_limit) ;
        } else {
            $wp_query_args['offset'] = $offset ;
        }

        $fake_loop_offset = $offset;

        $wp_query_args['post_type'] = 'property';
//        echo 'loaaaaaadddin more ';print_r($wp_query_args);
//exit;
        $the_query = new WP_Query($wp_query_args);


        if ($the_query->have_posts()) :
            while ($the_query->have_posts()) : $the_query->the_post();

                get_template_part('template-parts/listing/'.$card_version);

            endwhile;
            wp_reset_postdata();
        else:
            echo 'no_result';
        endif;


        wp_die();
    }
}