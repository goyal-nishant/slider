<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once(ABSPATH . 'vendor/autoload.php');

$mail = new PHPMailer(true);


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
        $mail->Body    =     "Body: " . $body;

        $mail->send();

        //echo 'Message has been sent';
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>


$subject = $_POST['emailSubject'];
$body = $_POST['emailBody'];

foreach ($selected_users as $user_id) {
    $user = get_user_by('ID', $user_id);
    $to = $user->user_email;
    $headers = array('Content-Type: text/html; charset=UTF-8');
    $result = wp_mail($to, $subject, $body, $headers);
    if (!$result) {
        echo "Message could not be sent to $to.";
    }
}


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
    $mail->Body    = "Body: " . $body;

    $mail->send();

    //echo 'Message has been sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
