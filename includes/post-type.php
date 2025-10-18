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
