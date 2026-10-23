<?php
require __DIR__ . '/db.php';
require __DIR__ . '/layout.php';

$pdo    = db();
$values = ['name' => '', 'email' => ''];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $errors['form'] = 'Your session expired. Please submit the form again.';
        $values = ['name' => trim((string)($_POST['name'] ?? '')), 'email' => trim((string)($_POST['email'] ?? ''))];
    } else {
        $v      = validate_user($_POST);
        $values = ['name' => $v['name'], 'email' => $v['email']];
        $errors = $v['errors'];

        if (!$errors && email_taken($pdo, $v['email'])) {
            $errors['email'] = 'That email is already registered.';
        }

        if (!$errors) {
            try {
                $pdo->prepare('INSERT INTO users (name, email) VALUES (?, ?)')
                    ->execute([$v['name'], $v['email']]);
                flash('success', 'User “' . $v['name'] . '” was added.');
                redirect('index.php');
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {          // unique-key race
                    $errors['email'] = 'That email is already registered.';
                } else {
                    error_log('create user failed: ' . $e->getMessage());
                    $errors['form'] = 'Something went wrong while saving. Please try again.';
                }
            }
        }
    }
}

page_header('Add User');
?>
    <div class="card">
        <h1>Add New User</h1>
        <?php render_user_form($values, $errors, 'create.php', 'Add User'); ?>
    </div>
<?php page_footer();
