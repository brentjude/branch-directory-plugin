<?php
/**
 * Register Branch Custom Post Type
 */
function bm_register_branch_cpt() {
    $labels = [
        'name'               => 'Branches',
        'singular_name'      => 'Branch',
        'add_new'            => 'Add Branch',
        'add_new_item'       => 'Add New Branch',
        'edit_item'          => 'Edit Branch',
        'new_item'           => 'New Branch',
        'all_items'          => 'All Branches',
        'view_item'          => 'View Branch',
        'search_items'       => 'Search Branches',
        'not_found'          => 'No branches found',
        'not_found_in_trash' => 'No branches found in Trash',
        'menu_name'          => 'Branches',
    ];

    $args = [
        'labels'      => $labels,
        'public'      => true,
        'menu_icon'   => 'dashicons-location-alt',
        'supports'    => ['title', 'thumbnail'],
        'has_archive' => true,
        'show_in_rest'=> true,
    ];

    register_post_type('branch', $args);
}
add_action('init', 'bm_register_branch_cpt');

/**
 * Register Region Taxonomy (Parent)
 */
function bm_register_region_taxonomy() {
    $labels = [
        'name'              => 'Regions',
        'singular_name'     => 'Region',
        'search_items'      => 'Search Regions',
        'all_items'         => 'All Regions',
        'parent_item'       => null,
        'parent_item_colon' => null,
        'edit_item'         => 'Edit Region',
        'update_item'       => 'Update Region',
        'add_new_item'      => 'Add New Region',
        'new_item_name'     => 'New Region Name',
        'menu_name'         => 'Regions',
    ];

    $args = [
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => ['slug' => 'region'],
        'show_in_rest'      => true,
    ];

    register_taxonomy('branch_region', ['branch'], $args);
}
add_action('init', 'bm_register_region_taxonomy');

/**
 * Register Province Taxonomy (Child of Region - using hierarchical parent feature)
 */
function bm_register_province_taxonomy() {
    $labels = [
        'name'              => 'Provinces',
        'singular_name'     => 'Province',
        'search_items'      => 'Search Provinces',
        'all_items'         => 'All Provinces',
        'parent_item'       => 'Parent Region',
        'parent_item_colon' => 'Parent Region:',
        'edit_item'         => 'Edit Province',
        'update_item'       => 'Update Province',
        'add_new_item'      => 'Add New Province',
        'new_item_name'     => 'New Province Name',
        'menu_name'         => 'Provinces',
    ];

    $args = [
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => ['slug' => 'province'],
        'show_in_rest'      => true,
        'meta_box_cb'       => 'bm_province_meta_box_callback',
    ];

    register_taxonomy('branch_province', ['branch'], $args);
}
add_action('init', 'bm_register_province_taxonomy');

/**
 * Custom Meta Box for Province Selection with Region Filter
 */
function bm_province_meta_box_callback($post) {
    $regions = get_terms(['taxonomy' => 'branch_region', 'hide_empty' => false]);
    $provinces = get_terms(['taxonomy' => 'branch_province', 'hide_empty' => false]);
    $selected_provinces = wp_get_post_terms($post->ID, 'branch_province', ['fields' => 'ids']);
    ?>
    <div id="taxonomy-branch_province" class="categorydiv">
        <div id="branch-province-filter" style="margin-bottom: 10px;">
            <label><strong>Filter by Region:</strong></label>
            <select id="region-province-filter" style="width: 100%;">
                <option value="">All Regions</option>
                <?php foreach($regions as $region): ?>
                    <option value="<?php echo esc_attr($region->term_id); ?>">
                        <?php echo esc_html($region->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <ul id="branch_province-checklist" class="categorychecklist form-no-clear">
            <?php foreach($provinces as $province): 
                $parent_region = get_term_meta($province->term_id, 'parent_region', true);
                $checked = in_array($province->term_id, $selected_provinces) ? 'checked' : '';
            ?>
                <li class="province-item" data-region="<?php echo esc_attr($parent_region); ?>">
                    <label>
                        <input type="checkbox" name="tax_input[branch_province][]" 
                               value="<?php echo esc_attr($province->term_id); ?>" 
                               <?php echo $checked; ?>>
                        <?php echo esc_html($province->name); ?>
                        <?php if($parent_region): 
                            $region = get_term($parent_region, 'branch_region');
                            if($region && !is_wp_error($region)):
                        ?>
                            <em>(<?php echo esc_html($region->name); ?>)</em>
                        <?php endif; endif; ?>
                    </label>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#region-province-filter').on('change', function() {
            var selectedRegion = $(this).val();
            
            if (!selectedRegion) {
                $('.province-item').show();
            } else {
                $('.province-item').each(function() {
                    var itemRegion = $(this).attr('data-region');
                    if (itemRegion === selectedRegion) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
        });
    });
    </script>
    
    <style>
    .province-item em {
        color: #666;
        font-size: 0.9em;
    }
    </style>
    <?php
}

/**
 * Add Parent Region Field to Province Edit Page
 */
add_action('branch_province_edit_form_fields', 'bm_add_province_region_field', 10, 2);
add_action('branch_province_add_form_fields', 'bm_add_province_region_field_new');

function bm_add_province_region_field($term) {
    $region_id = get_term_meta($term->term_id, 'parent_region', true);
    $regions = get_terms(['taxonomy' => 'branch_region', 'hide_empty' => false]);
    ?>
    <tr class="form-field">
        <th scope="row">
            <label for="parent_region">Parent Region</label>
        </th>
        <td>
            <select name="parent_region" id="parent_region" class="postform">
                <option value="">-- Select Region --</option>
                <?php foreach($regions as $region): ?>
                    <option value="<?php echo esc_attr($region->term_id); ?>" 
                            <?php selected($region_id, $region->term_id); ?>>
                        <?php echo esc_html($region->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="description">Select the parent region for this province.</p>
        </td>
    </tr>
    <?php
}

function bm_add_province_region_field_new() {
    $regions = get_terms(['taxonomy' => 'branch_region', 'hide_empty' => false]);
    ?>
    <div class="form-field">
        <label for="parent_region">Parent Region</label>
        <select name="parent_region" id="parent_region" class="postform">
            <option value="">-- Select Region --</option>
            <?php foreach($regions as $region): ?>
                <option value="<?php echo esc_attr($region->term_id); ?>">
                    <?php echo esc_html($region->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">Select the parent region for this province.</p>
    </div>
    <?php
}

/**
 * Save Province-Region Relationship
 */
function bm_save_province_region($term_id) {
    if (isset($_POST['parent_region'])) {
        update_term_meta($term_id, 'parent_region', sanitize_text_field($_POST['parent_region']));
    }
}
add_action('created_branch_province', 'bm_save_province_region');
add_action('edited_branch_province', 'bm_save_province_region');

/**
 * Add Custom Meta Boxes (Contact, Address, Lat, Lng)
 */
function bm_add_branch_meta_boxes() {
    add_meta_box('branch_details', 'Branch Details', 'bm_render_branch_meta_box', 'branch', 'normal', 'default');
}
add_action('add_meta_boxes', 'bm_add_branch_meta_boxes');

function bm_render_branch_meta_box($post) {
    $contact   = get_post_meta($post->ID, '_branch_contact', true);
    $address   = get_post_meta($post->ID, '_branch_address', true);
    $latitude  = get_post_meta($post->ID, '_branch_lat', true);
    $longitude = get_post_meta($post->ID, '_branch_lng', true);
    ?>
    <p>
        <label><strong>Contact Number:</strong></label><br>
        <input type="text" name="branch_contact" value="<?php echo esc_attr($contact); ?>" style="width:100%;">
    </p>
    <p>
        <label><strong>Address (displayed in grid & popup):</strong></label><br>
        <input type="text" name="branch_address" value="<?php echo esc_attr($address); ?>" style="width:100%;">
    </p>
    <p>
        <label><strong>Latitude (used for map marker):</strong></label><br>
        <input type="text" name="branch_lat" value="<?php echo esc_attr($latitude); ?>" style="width:100%;" placeholder="14.5995">
    </p>
    <p>
        <label><strong>Longitude (used for map marker):</strong></label><br>
        <input type="text" name="branch_lng" value="<?php echo esc_attr($longitude); ?>" style="width:100%;" placeholder="120.9842">
    </p>
    <?php
}

function bm_save_branch_meta($post_id) {
    if (array_key_exists('branch_contact', $_POST)) {
        update_post_meta($post_id, '_branch_contact', sanitize_text_field($_POST['branch_contact']));
    }
    if (array_key_exists('branch_address', $_POST)) {
        update_post_meta($post_id, '_branch_address', sanitize_text_field($_POST['branch_address']));
    }
    if (array_key_exists('branch_lat', $_POST)) {
        update_post_meta($post_id, '_branch_lat', sanitize_text_field($_POST['branch_lat']));
    }
    if (array_key_exists('branch_lng', $_POST)) {
        update_post_meta($post_id, '_branch_lng', sanitize_text_field($_POST['branch_lng']));
    }
}
add_action('save_post', 'bm_save_branch_meta');