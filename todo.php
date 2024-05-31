<?php
add_action('admin_menu', 'addTodoUser');

function addTodoUser()
{
    add_menu_page(
        'Add Todo',
        'Todo',
        'manage_options',
        'to-do',
        'displayTodo'
    );
}

function displayTodo()
{
    global $wpdb;
    $table_todo = $wpdb->prefix . 'todolist';
    $table_users = $wpdb->prefix . 'users';
    $table_contact_list = $wpdb->prefix . 'contact_list_with_password';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (!empty($_POST['addTask'])) {
            $task = sanitize_text_field($_POST['addTask']);
            $assigned_to = (int)$_POST['assigned_to'];
            $status = isset($_POST['status']) ? $_POST['status'] : 'pending'; // Default status

            $wpdb->insert($table_todo, array(
                'task' => $task,
                'assigned_to' => $assigned_to,
                'status' => $status // Add status field
            ));
        } elseif (isset($_POST['task_id'])) {
            $task_id = (int)$_POST['task_id'];
            if (isset($_POST['status'])) {
                $status = $_POST['status'];
                $wpdb->update(
                    $table_todo,
                    array('status' => $status),
                    array('id' => $task_id)
                );
            } elseif (isset($_POST['delete'])) {
                $wpdb->delete($table_todo, array('id' => $task_id));
            }
        }
    }

?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Add Todo List</h1>
        <div class="create-task"> <!-- Changed class name to match JavaScript -->
            <button id="createTaskBtn">Create Task</button> <!-- Added ID to the button -->
        </div>
        <br>
        <form id="displayForm" method="post" style="display: none;">
            <label for="addTask">
                <h3>Task:</h3>
            </label>
            <input type="text" id="addTask" name="addTask">
            <label for="assigned_to">
                <h3>Assign To:</h3>
            </label>
            <select id="assigned_to" name="assigned_to">
                <?php
                $users = array();

                $admin_role = 'administrator'; // Role name for administrator

                $users_from_wp_users = $wpdb->get_results("SELECT ID, user_login AS username FROM {$wpdb->users} WHERE ID NOT IN (SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = '{$wpdb->prefix}capabilities' AND meta_value LIKE '%{$admin_role}%')", ARRAY_A);

                if ($users_from_wp_users) {
                    $users = array_merge($users, $users_from_wp_users);
                }


                $users_from_contact_list = $wpdb->get_results("SELECT id AS ID, name AS username FROM {$wpdb->prefix}contact_list_with_password", ARRAY_A);
                if ($users_from_contact_list) {
                    $users = array_merge($users, $users_from_contact_list);
                }

                if ($users) {
                    foreach ($users as $user) {
                        echo "<option value='{$user['ID']}'>{$user['username']}</option>";
                    }
                }
                ?>
            </select>
            <input type="submit" name="submit" value="Add Task" class="button-primary">
        </form>
    </div>

<?php
    // Fetch tasks from the database
    $tasks = $wpdb->get_results("SELECT * FROM $table_todo", ARRAY_A);
    if ($tasks) {
        echo "<h2>Tasks</h2>";
        echo "<table class='wp-list-table widefat fixed striped'>";
        echo "<thead>";
        echo "<tr>";
        echo "<th scope='col'>Task</th>";
        echo "<th scope='col'>Assigned To</th>";
        echo "<th scope='col'>Status</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";
        foreach ($tasks as $task) {
            // Fetch user's name based on assigned_to ID
            $assigned_to = $task['assigned_to'];
            $assigned_user = '';
            if ($assigned_to <= $wpdb->get_var("SELECT MAX(ID) FROM {$wpdb->users}")) {
                $assigned_user = $wpdb->get_row("SELECT user_login FROM $table_users WHERE ID = $assigned_to", ARRAY_A);
            } else {
                $assigned_user = $wpdb->get_row("SELECT name AS user_login FROM $table_contact_list WHERE id = $assigned_to", ARRAY_A);
            }
            $assigned_username = $assigned_user ? $assigned_user['user_login'] : 'Unknown User';
            echo "<tr>";
            echo "<td>{$task['task']}</td>";
            echo "<td>{$assigned_username}</td>";
            echo "<td>
                    <form method='post'>
                        <input type='hidden' name='task_id' value='{$task['id']}'>
                        <select name='status' onchange='this.form.submit()'>";
            echo "<option value='pending' " . ($task['status'] == 'pending' ? 'selected' : '') . ">Pending</option>";
            echo "<option value='complete' " . ($task['status'] == 'complete' ? 'selected' : '') . ">Complete</option>";
            echo "</select>
                  </form>
                  </td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
    } else {
        echo "<p>No tasks found.</p>";
    }
}
?>



<script>
        document.addEventListener("DOMContentLoaded", function() {
            var createTaskBtn = document.getElementById("createTaskBtn");
            var displayForm = document.getElementById("displayForm");

            createTaskBtn.addEventListener("click", function() {
                if (displayForm.style.display === "none") {
                    displayForm.style.display = "block";
                } else {
                    displayForm.style.display = "none";
                }
            });
        });
    </script>