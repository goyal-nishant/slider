<?php

/*
 * Plugin Name:       My Basics Plugin
 * Plugin URI:        https://example.com/plugins/the-basics/
 * Description:       Handle the basics with this plugin.
 * Version:           1
 * Requires at least: 1
 * Requires PHP:      7.2
 * Author:            John Smith
 * Author URI:        https://author.example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://example.com/my-plugin/
 * Text Domain:       my-basics-plugin
 * Domain Path:       /languages
 */



 /**
 * Register the "book" custom post type
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


require_once(plugin_dir_path(__FILE__) . 'includes/activation.php');
require_once(plugin_dir_path(__FILE__) . 'includes/deactivation.php');

// Activate the plugin.
register_activation_hook( __FILE__, 'customPlugin_activate' );
 
//Deactivation hook.
register_deactivation_hook(__FILE__, 'customPlugin_deactivate');


function customPlugin_settings_page() {
    ?>
    <div class="wrap">
        <h2>Custom Plugin Settings</h2>
        <form method="post" action="options.php">
            <?php settings_fields('my_plugin_settings_group'); ?>
            <?php do_settings_sections('my_plugin_settings_group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Custom Menu Option</th>
                    <td><input type="text" name="custom_plugin_menu_option" value="<?php echo esc_attr(get_option('custom_plugin_menu_option')); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function customPlugin_settings_init() {
    add_settings_section('customPlugin_settings_section', 'Custom Plugin Settings', '', 'my_plugin_settings_group');
    add_settings_field('custom_plugin_menu_option', 'Custom Menu Option', 'customPlugin_render_menu_option_field', 'my_plugin_settings_group', 'customPlugin_settings_section');
}

function customPlugin_render_menu_option_field() {
    $value = get_option('custom_plugin_menu_option');
    echo '<input type="text" name="custom_plugin_menu_option" value="' . esc_attr($value) . '" />';
}

add_action('admin_menu', 'customPlugin_add_settings_page');
function customPlugin_add_settings_page() {
    add_options_page('Custom Plugin Settings', 'Custom Plugin', 'manage_options', 'custom-plugin-settings', 'customPlugin_settings_page');
}
add_action('admin_init', 'customPlugin_settings_init');



// add user list 
add_action('admin_menu', 'custom_plugin_menu');

// Function to create a new admin menu
function custom_plugin_menu() {
    add_menu_page(
        'User List',         // Page title
        'User List',         // Menu title
        'manage_options',    // Capability
        'user-list',         // Menu slug
        'display_user_list'  // Function to display the page content
    );
}

// Function to fetch users
function fetch_users() {
    $args = array(
        'role__in' => ['administrator', 'editor', 'author', 'contributor', 'subscriber'],
        'orderby' => 'ID',
        'order' => 'ASC'
    );

    $users = get_users($args);
    return $users;
}

// Function to display user list
function display_user_list() {
    // Check if the form is submitted
    if (isset($_POST['submit_new_user'])) {
        // Handle form submission
        handle_new_user_submission();
    }

    // Fetch WordPress users
    $wordpress_users = fetch_users();

    // Fetch data from the contact_list_with_password table
    global $wpdb;
    $table_name = $wpdb->prefix . 'contact_list_with_password';
    $contact_list = $wpdb->get_results("SELECT * FROM $table_name");

    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">User List</h1>
        <!-- Add New User Button -->
        <button id="showFormBtn" class="page-title-action">Add New User</button>

        <!-- New User Form -->
        <div id="newUserForm" style="display:none;" class="wrap">
            <h2>Add New User</h2>
            <form method="post" action="" class="validate">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="name">Name</label>
                            </th>
                            <td>
                                <input type="text" id="name" name="name" class="regular-text" required>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="email">Email</label>
                            </th>
                            <td>
                                <input type="email" id="email" name="email" class="regular-text" required>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="password">Password</label>
                            </th>
                            <td>
                                <input type="password" id="password" name="password" class="regular-text" required>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <p class="submit">
                    <input type="submit" name="submit_new_user" id="submit_new_user" class="button button-primary" value="Add User">
                </p>
            </form>
        </div>

        <!-- User List Table -->
        <table class="wp-list-table widefat fixed striped users">
            <thead>
                <tr>
                    <th scope="col" id="username" class="manage-column column-username">Username</th>
                    <th scope="col" id="email" class="manage-column column-email">Email</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Display WordPress users
                foreach ($wordpress_users as $user) : ?>
                    <tr>
                        <td class="username"><?php echo esc_html($user->user_login); ?></td>
                        <td class="email"><?php echo esc_html($user->user_email); ?></td>
                    </tr>
                <?php endforeach; 

                // Display users from contact_list_with_password table
                foreach ($contact_list as $contact) : ?>
                    <tr>
                        <td class="username"><?php echo esc_html($contact->name); ?></td>
                        <td class="email"><?php echo esc_html($contact->email); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        document.getElementById('showFormBtn').addEventListener('click', function() {
            document.getElementById('newUserForm').style.display = 'block';
        });
    </script>
    <?php
}

function handle_new_user_submission() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'contact_list_with_password';

    // Get form data
    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $password = $_POST['password']; // In a real-world scenario, make sure to properly hash the password

    // Insert data into the database
    $wpdb->insert(
        $table_name,
        array(
            'name' => $name,
            'email' => $email,
            'password' => $password,
        )
    );

    // Redirect after submission (optional)
    wp_redirect(admin_url('admin.php?page=user-list'));
    exit;
}


// Create contact list with password table on activation
function customPlugin_activate() {
    global $wpdb;
    $table_name_with_password = $wpdb->prefix . 'contact_list_with_password';
    $charset_collate = $wpdb->get_charset_collate();

    // Check if the table already exists
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name_with_password'") != $table_name_with_password) {
        $sql = "CREATE TABLE $table_name_with_password (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name tinytext NOT NULL,
            email varchar(100) NOT NULL,
            password varchar(255) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

?>
