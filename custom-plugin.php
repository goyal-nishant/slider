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

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once(ABSPATH . 'vendor/autoload.php');

$mail = new PHPMailer(true);

/**
 * Register the "book" custom post type
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once(plugin_dir_path(__FILE__) . 'includes/activation.php');
require_once(plugin_dir_path(__FILE__) . 'includes/deactivation.php');

// Activate the plugin.
register_activation_hook(__FILE__, 'customPlugin_activate');

//Deactivation hook.
register_deactivation_hook(__FILE__, 'customPlugin_deactivate');

// Check if the taxonomy exists before registering it
if (!taxonomy_exists('course')) {
    // Register custom taxonomy
    function register_course_taxonomy()
    {
        $labels = array(
            'name'              => _x('Courses', 'taxonomy general name'),
            'singular_name'     => _x('Course', 'taxonomy singular name'),
            'search_items'      => __('Search Courses'),
            'all_items'         => __('All Courses'),
            'parent_item'       => __('Parent Course'),
            'parent_item_colon' => __('Parent Course:'),
            'edit_item'         => __('Edit Course'),
            'update_item'       => __('Update Course'),
            'add_new_item'      => __('Add New Course'),
            'new_item_name'     => __('New Course Name'),
            'menu_name'         => __('Course'),
        );
        $args   = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'course'],
        );
        register_taxonomy('course', ['book'], $args);
    }
    add_action('init', 'register_course_taxonomy');
}

// Add meta boxes
function add_custom_meta_box()
{
    $post_types = ['post', 'book'];
    add_meta_box(
        'custom_meta_box_id',
        'My Custom Meta Box',
        'render_custom_meta_box_callback',
        $post_types,
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'add_custom_meta_box');

function render_custom_meta_box_callback($post)
{
    echo '<label for="custom_field">My Custom Field </label>';
    echo '<input type="text" id="custom_field" name="custom_field" value="" placeholder = "Enter your meta data" />';
}

function save_custom_meta_data($post_id)
{
    if (array_key_exists('custom_field', $_POST)) {
        update_post_meta(
            $post_id,
            'custom_field',
            sanitize_text_field($_POST['custom_field'])
        );
    }
}
add_action('save_post', 'save_custom_meta_data');

// regsiter_setting into database
function customPlugin_settings_init()
{
    register_setting('my_plugin_settings_group', 'custom_plugin_menu_option');
    register_setting('my_plugin_settings_group', 'custom_plugin_another_option');

    add_settings_section('customPlugin_settings_section', 'Custom Settings', '', 'my_plugin_settings_group');

    add_settings_field('custom_plugin_menu_option', 'Custom Menu Option', 'customPlugin_render_menu_option_field', 'my_plugin_settings_group', 'customPlugin_settings_section');
    add_settings_field('custom_plugin_another_option', 'Another Option', 'customPlugin_render_another_option_field', 'my_plugin_settings_group', 'customPlugin_settings_section');
}
function customPlugin_render_menu_option_field()
{
    $value = get_option('custom_plugin_menu_option');
    echo '<input type="text" name="custom_plugin_menu_option" value="" placeholder = "' . esc_attr($value) . '" />';
}
function customPlugin_render_another_option_field()
{
    $value = get_option('custom_plugin_another_option');
    echo '<input type="text" name="custom_plugin_another_option" value="" placeholder="' . esc_attr($value) . '" />';
}
add_action('admin_menu', 'customPlugin_add_settings_page');
function customPlugin_add_settings_page()
{
    add_options_page('Custom Plugin Settings', 'Custom Plugin', 'manage_options', 'custom-plugin-settings', 'customPlugin_settings_page');
}
function customPlugin_settings_page()
{
?>
    <div class="wrap">
        <h2>Custom Plugin Settings</h2>
        <form method="post" action="options.php">
            <?php settings_fields('my_plugin_settings_group'); ?>
            <?php do_settings_sections('my_plugin_settings_group'); ?>
            <?php submit_button(); ?>
        </form>
    </div>
<?php
}
add_action('admin_init', 'customPlugin_settings_init');

// add user list
add_action('admin_menu', 'custom_plugin_menu');
function custom_plugin_menu()
{
    add_menu_page(
        'User List',
        'User List',
        'manage_options',
        'user-list',
        'display_user_list'
    );
}

function fetch_users()
{
    $args = array(
        'role__in' => ['administrator', 'editor', 'author', 'contributor', 'subscriber'],
        'orderby' => 'ID',
        'order' => 'ASC'
    );

    $users = get_users($args);
    return $users;
}

function display_user_list()
{
    if (isset($_POST['submit_new_user'])) {
        handle_new_user_submission();
    }

    $wordpress_users = fetch_users();

    global $wpdb;
    $table_name = $wpdb->prefix . 'contact_list_with_password';
    $contact_list = $wpdb->get_results("SELECT * FROM $table_name");
?>
    <div class="wrap">
        <h1 class="wp-heading-inline">User List</h1>
        <button id="showFormBtn" class="page-title-action">Add New User</button>
        <button id="selectUser" class="page-title-action">Select user</button>

        <!-- send mail -->
        <div id="sendMailForm" style="display:none;" class="wrap">
            <h2>Send Email to Selected Users</h2>
            <form id="sendMailForm" method="post" action="">
                <input type="hidden" name="selected_users" id="selectedUsers" value="">
                <label for="emailSubject">Subject:</label><br>
                <input type="text" id="emailSubject" name="emailSubject" required><br>
                <label for="emailBody">Body:</label><br>
                <textarea id="emailBody" name="emailBody" rows="4" cols="50" required></textarea><br>
                <input type="submit" value="Send Email" id="sendEmailBtn" name="sendEmailBtn">
            </form>
        </div>


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

        <table class="wp-list-table widefat fixed striped users">
            <thead>
                <tr>
                    <th style="display:none;" id="selectedUser">Select User</th>
                    <th scope="col" id="username" class="manage-column column-username">Username</th>
                    <th scope="col" id="email" class="manage-column column-email">Email</th>
                </tr>
            </thead>

            <tbody>
                <?php
                foreach ($wordpress_users as $user) : ?>
                    <tr>
                        <td style="display:none;">
                            <input type="checkbox" class="selectUserCheckbox" name="selected_user[]" value="<?php echo esc_attr($user->ID); ?>">
                        </td>
                        <td class="username"><?php echo esc_html($user->user_login); ?></td>
                        <td class="email"><?php echo esc_html($user->user_email); ?></td>
                    </tr>

                <?php endforeach;

                foreach ($contact_list as $contact) : ?>
                    <tr>
                        <td style="display:none;">
                            <input type="checkbox" class="selectUserCheckbox" name="selected_user[]" value="<?php echo esc_attr($contact->id); ?>">
                        </td>
                        <td class="username"><?php echo esc_html($contact->name); ?></td>
                        <td class="email"><?php echo esc_html($contact->email); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        var newUserFormDisplayed = false;
    var userColumnDisplayed = false;
    var selectUserColumnHeader = document.getElementById('selectedUser');

    document.getElementById('selectUser').addEventListener('click', function() {
        var checkboxes = document.querySelectorAll('.selectUserCheckbox');
        checkboxes.forEach(function(checkbox) {
            checkbox.parentElement.style.display = userColumnDisplayed ? 'none' : 'block';
        });
        document.getElementById('sendMailForm').style.display = userColumnDisplayed ? 'none' : 'block';

        selectUserColumnHeader.style.display = userColumnDisplayed ? 'none' : 'table-cell';
        userColumnDisplayed = !userColumnDisplayed;

        if (newUserFormDisplayed) {
            document.getElementById('newUserForm').style.display = 'none';
            newUserFormDisplayed = false;
        }
    });

    document.getElementById('showFormBtn').addEventListener('click', function() {
        document.getElementById('newUserForm').style.display = newUserFormDisplayed ? 'none' : 'block';
        newUserFormDisplayed = !newUserFormDisplayed;

        document.getElementById('sendMailForm').style.display = 'none';
        userColumnDisplayed = false;
    });
        document.getElementById('sendEmailBtn').addEventListener('click', function(event) {
            var selectedUsers = [];
            var checkboxes = document.querySelectorAll('.selectUserCheckbox:checked');
            checkboxes.forEach(function(checkbox) {
                selectedUsers.push(checkbox.value);
            });
            document.getElementById('selectedUsers').value = JSON.stringify(selectedUsers);
        });
    </script>
<?php
}

function handle_new_user_submission()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'contact_list_with_password';

    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $password = $_POST['password'];

    $existing_user_custom_table = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE email = %s", $email));
    $existing_user_wp_users = get_user_by('email', $email);

    if (!$existing_user_custom_table && !$existing_user_wp_users) {
        $wpdb->insert(
            $table_name,
            array(
                'name' => $name,
                'email' => $email,
                'password' => $password,
            )
        );

        wp_redirect(admin_url('admin.php?page=user-list'));
        exit;
    } else {
        echo '<div class="error"><p>User with this email already exists.</p></div>';
    }
}

function customPlugin_activate()
{
    global $wpdb;
    $table_name_with_password = $wpdb->prefix . 'contact_list_with_password';
    $charset_collate = $wpdb->get_charset_collate();

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name_with_password'") != $table_name_with_password) {
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

if (isset($_POST['sendEmailBtn'])) {
    send_email_to_selected_users();
}

function send_email_to_selected_users()
{
    if (!function_exists('get_user_by')) {
        require_once ABSPATH . 'wp-includes/pluggable.php';
    }

    $selected_users_json = $_POST['selected_users'];
    $selected_users = json_decode($selected_users_json);

    $subject = $_POST['emailSubject'];
    $body = $_POST['emailBody'];

    $mail = new PHPMailer(true);    
    try {
        $mail->SMTPDebug = SMTP::DEBUG_OFF; 
        $mail->isSMTP();
        $mail->Host       = 'sandbox.smtp.mailtrap.io'; 
        $mail->SMTPAuth   = true; 
        $mail->Username   = '05ee71512c7caa'; 
        $mail->Password   = 'cbf4265255585e'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        $mail->Port       = 2525; 

        $mail->setFrom('from@example.com', 'Mailer');
        foreach ($selected_users as $user_id) {
            $user = get_user_by('ID', $user_id);
            $mail->addAddress($user->user_email, $user->display_name);
        }

        $mail->isHTML(true); 
        $mail->Subject = $subject;
        $mail->Body    = "Subject: " . $subject . "<br>" . "Body: " .$body  ;

        $mail->send();

        //echo 'Message has been sent';
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>
