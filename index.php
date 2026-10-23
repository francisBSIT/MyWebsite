<?php
require __DIR__ . '/db.php';
require __DIR__ . '/layout.php';

$pdo = db();

$perPage = 10;
$q       = trim((string)($_GET['q'] ?? $_POST['q'] ?? ''));

function page_url(int $page, string $q): string
{
    $qs = ['page' => max(1, $page)];
    if ($q !== '') {
        $qs['q'] = $q;
    }
    return 'index.php?' . http_build_query($qs);
}

// --- DELETE (POST + CSRF; a plain link must never be able to delete data) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $back = page_url((int)($_POST['page'] ?? 1), $q);

    if (!csrf_check($_POST['csrf'] ?? null)) {
        flash('error', 'Your session expired. Please try again.');
        redirect($back);
    }

    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false || $id === null) {
        flash('error', 'Invalid user.');
        redirect($back);
    }

    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        flash('success', 'User deleted.');
        // Reset AUTO_INCREMENT to 1 if all rows have been deleted
        if ((int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) {
            $pdo->exec('ALTER TABLE users AUTO_INCREMENT = 1');
        }
    } else {
        flash('error', 'That user no longer exists.');
    }
    redirect($back);
}

// --- READ (search + pagination) ---
$where  = '';
$params = [];
if ($q !== '') {
    $like   = '%' . addcslashes($q, '%_\\') . '%';
    $where  = 'WHERE name LIKE ? OR email LIKE ?';
    $params = [$like, $like];
}

$st = $pdo->prepare("SELECT COUNT(*) FROM users $where");
$st->execute($params);
$total = (int)$st->fetchColumn();

$pages  = max(1, (int)ceil($total / $perPage));
$page   = min(max(1, (int)($_GET['page'] ?? 1)), $pages);
$offset = ($page - 1) * $perPage;

// $perPage and $offset are integers computed above, so they are safe to inline.
$st = $pdo->prepare("SELECT id, name, email FROM users $where ORDER BY id ASC LIMIT $perPage OFFSET $offset");
$st->execute($params);
$users = $st->fetchAll();

page_header('Dashboard');
?>
    <div class="toolbar">
        <h1>User Management</h1>
        <a href="create.php" class="btn btn-primary">+ Add New User</a>
    </div>

    <form method="get" action="index.php" class="search" role="search">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search by name or email…" aria-label="Search users">
        <button type="submit" class="btn btn-secondary">Search</button>
        <?php if ($q !== ''): ?><a href="index.php" class="btn btn-secondary">Clear</a><?php endif; ?>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>#</th><th>Name</th><th>Email</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php if ($users): ?>
                <?php foreach ($users as $i => $user): ?>
                    <tr>
                        <td data-label="#"><?= $offset + $i + 1 ?></td>
                        <td data-label="Name"><?= e($user['name']) ?></td>
                        <td data-label="Email"><?= e($user['email']) ?></td>
                        <td data-label="Actions" class="actions">
                            <a href="update.php?id=<?= (int)$user['id'] ?>" class="btn btn-edit">Edit</a>
                            <form method="post" action="index.php" class="inline-form"
                                  onsubmit="return confirm('Delete this user? This cannot be undone.');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
                                <input type="hidden" name="page" value="<?= $page ?>">
                                <input type="hidden" name="q" value="<?= e($q) ?>">
                                <button type="submit" class="btn btn-delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr class="empty-row"><td colspan="4">
                    <?= $q !== '' ? 'No users match “' . e($q) . '”.' : 'No records found. Add your first user to get started.' ?>
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total > 0): ?>
    <div class="table-foot">
        <p class="muted">Showing <?= $offset + 1 ?>–<?= $offset + count($users) ?> of <?= $total ?></p>
        <?php if ($pages > 1): ?>
        <nav class="pagination" aria-label="Pagination">
            <?php if ($page > 1): ?><a href="<?= e(page_url($page - 1, $q)) ?>">‹ Prev</a><?php endif; ?>
            <?php for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++): ?>
                <a href="<?= e(page_url($p, $q)) ?>" <?= $p === $page ? 'class="current" aria-current="page"' : '' ?>><?= $p ?></a>
            <?php endfor; ?>
            <?php if ($page < $pages): ?><a href="<?= e(page_url($page + 1, $q)) ?>">Next ›</a><?php endif; ?>
        </nav>
        <?php endif; ?>
    </div>
    <?php endif; ?>
<?php page_footer();
