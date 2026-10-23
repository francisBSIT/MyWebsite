<?php
/** Shared page chrome and the reusable user form. */

function page_header(string $title): void
{
    $flashes = take_flashes();
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · User Manager</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <header class="page-head">
        <a href="index.php" class="brand">👥 User Manager</a>
    </header>
    <?php foreach ($flashes as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?>" role="status"><?= e($f['message']) ?></div>
    <?php endforeach; ?>
<?php
}

function page_footer(): void
{
    ?>
</div>
</body>
</html>
<?php
}

/**
 * Name + email form used by both create.php and update.php.
 * @param array $values ['name'=>..., 'email'=>...]
 * @param array $errors field => message (and optional 'form')
 */
function render_user_form(array $values, array $errors, string $action, string $submitLabel): void
{
    ?>
    <form method="post" action="<?= e($action) ?>" novalidate>
        <?= csrf_field() ?>

        <?php if (!empty($errors['form'])): ?>
            <div class="alert alert-error" role="alert"><?= e($errors['form']) ?></div>
        <?php endif; ?>

        <div class="field">
            <label for="name">Full name</label>
            <input type="text" id="name" name="name" maxlength="100" required autofocus
                   autocomplete="name" placeholder="Jane Doe"
                   value="<?= e($values['name']) ?>"
                   <?= isset($errors['name']) ? 'aria-invalid="true" aria-describedby="name-err"' : '' ?>>
            <?php if (isset($errors['name'])): ?><p class="error-text" id="name-err"><?= e($errors['name']) ?></p><?php endif; ?>
        </div>

        <div class="field">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" maxlength="255" required
                   autocomplete="email" placeholder="jane@example.com"
                   value="<?= e($values['email']) ?>"
                   <?= isset($errors['email']) ? 'aria-invalid="true" aria-describedby="email-err"' : '' ?>>
            <?php if (isset($errors['email'])): ?><p class="error-text" id="email-err"><?= e($errors['email']) ?></p><?php endif; ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= e($submitLabel) ?></button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
<?php
}
