<?php
require __DIR__ . '/db.php';
require __DIR__ . '/layout.php';

$pdo = db();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false || $id === null) {
    redirect('index.php');
}

$user = find_user($pdo, $id);
if (!$user) {
    flash('error', 'That user no longer exists.');
    redirect('index.php');
}

$values = ['name' => $user['name'], 'email' => $user['email']];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $errors['form'] = 'Your session expired. Please submit the form again.';
    } else {
        $v      = validate_user($_POST);
        $values = ['name' => $v['name'], 'email' => $v['email']];
        $errors = $v['errors'];

        if (!$errors && email_taken($pdo, $v['email'], $id)) {
            $errors['email'] = 'Another user already has that email.';
        }

        if (!$errors) {
            try {
                $pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?')
                    ->execute([$v['name'], $v['email'], $id]);
                flash('success', 'User “' . $v['name'] . '” was updated.');
                redirect('index.php');
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $errors['email'] = 'Another user already has that email.';
                } else {
                    error_log('update user failed: ' . $e->getMessage());
                    $errors['form'] = 'Something went wrong while saving. Please try again.';
                }
            }
        }
    }
}

page_header('Edit User');
?>
    <div class="card">
        <h1>Modify User Record</h1>
        <?php render_user_form($values, $errors, 'update.php?id=' . $id, 'Save Changes'); ?>
    </div>
<?php page_footer();
