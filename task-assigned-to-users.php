<?php
// Add menu item visible to non-admin users
add_action('admin_menu', 'add_custom_menu_item');
function add_custom_menu_item() {
    if (!current_user_can('manage_options')) { // Check if current user is not an administrator
        add_menu_page(
            'My Tasks', // Page title
            'My Tasks', // Menu title
            'read', // Capability required
            'my-tasks', // Menu slug
            'display_assigned_tasks' // Callback function to display tasks
        );
    }
}

// Callback function to display tasks assigned to the current user
function display_assigned_tasks() {
    global $wpdb;

    $current_user_id = get_current_user_id();
    $table_todo = $wpdb->prefix . 'todolist';

    // Fetch tasks assigned to the current user
    $tasks = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $table_todo WHERE assigned_to = %d", $current_user_id),
        ARRAY_A
    );

    ?>
    <div class="wrap">
        <h1>My Tasks</h1>
        <?php if ($tasks) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col">Task</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task) : ?>
                        <tr>
                            <td><?php echo esc_html($task['task']); ?></td>
                            <td>
                                <select class="task-status" data-task-id="<?php echo esc_attr($task['id']); ?>">
                                    <option value="pending" <?php selected($task['status'], 'pending'); ?>>Pending</option>
                                    <option value="complete" <?php selected($task['status'], 'complete'); ?>>Complete</option>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>No tasks assigned.</p>
        <?php endif; ?>
    </div>

    <script>
        (function($) {
            $(document).ready(function() {
                // Update status when select box changes
                $('.task-status').on('change', function() {
                    var taskId = $(this).data('task-id');
                    var newStatus = $(this).val();

                    // AJAX request to update status in database
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'update_task_status',
                            taskId: taskId,
                            newStatus: newStatus,
                        },
                        success: function(response) {
                            console.log(response);
                        },
                        error: function(error) {
                            console.error(error);
                        }
                    });
                });
            });
        })(jQuery);
    </script>
<?php
}

// AJAX handler to update task status
add_action('wp_ajax_update_task_status', 'update_task_status_callback');
function update_task_status_callback() {
    global $wpdb;

    $table_todo = $wpdb->prefix . 'todolist';
    $task_id = isset($_POST['taskId']) ? intval($_POST['taskId']) : 0;
    $new_status = isset($_POST['newStatus']) ? sanitize_text_field($_POST['newStatus']) : '';

    // Update task status in the database
    $wpdb->update(
        $table_todo,
        array('status' => $new_status),
        array('id' => $task_id),
        array('%s'),
        array('%d')
    );

    // Return success response
    wp_send_json_success('Task status updated successfully.');
    wp_die();
}
?>
