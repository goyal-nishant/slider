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
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
require_once(plugin_dir_path(__FILE__) . 'includes/activation.php');
require_once(plugin_dir_path(__FILE__) . 'includes/deactivation.php');
require_once plugin_dir_path( __FILE__ ) . 'templates/user-list-page.php';
require_once plugin_dir_path( __FILE__ ) . 'main-functionality.php';
require_once plugin_dir_path(__FILE__) . 'templates/todo.php';
require_once plugin_dir_path(__FILE__) . 'templates\task-assigned-to-users.php';
// Enqueue script
add_action('admin_enqueue_scripts', 'custom_plugin_enqueue_scripts');
function custom_plugin_enqueue_scripts()
{
    wp_enqueue_script('custom-plugin-script', plugin_dir_url(__FILE__) . 'js/user-list.js', array('jquery'), '1.0', true);
}

// Activate the plugin.
register_activation_hook(__FILE__, 'customPlugin_activate');
    
//Deactivation hook.
register_deactivation_hook(__FILE__, 'customPlugin_deactivate');

// Add a new table in database after active
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

        

    $table_todo = $wpdb->prefix . 'todolist';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_todo'") != $table_todo) {
        $sql_todo = "CREATE TABLE $table_todo (
            id INT NOT NULL AUTO_INCREMENT,
            task VARCHAR(255) NOT NULL,
            assigned_to INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_todo);
    }

}

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

?>