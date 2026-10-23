<?php
require 'db.php'; //

// --- DELETE LOGIC ---
if (isset($_GET['delete_id'])) { //
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?"); //[cite: 3]
    $stmt->execute([$_GET['delete_id']]); //[cite: 3]

    // Reset AUTO_INCREMENT to 1 if all rows have been deleted
    $checkStmt = $pdo->query("SELECT COUNT(*) FROM users");
    if ($checkStmt->fetchColumn() == 0) {
        $pdo->exec("ALTER TABLE users AUTO_INCREMENT = 1");
    }

    header("Location: index.php"); //[cite: 3]
    exit; //[cite: 3]
}

// --- READ LOGIC ---
$stmt = $pdo->query("SELECT * FROM users ORDER BY id ASC"); // Displayed in chronological order
$users = $stmt->fetchAll(); //[cite: 3]
?>

<!DOCTYPE html>
<html>
<head>
    <title>PHP CRUD Dashboard</title>
    <link rel="stylesheet" href="style.css"> <!--[cite: 3, 4] -->
</head>
<body>
    <div class="container">
        <h2>User Management Dashboard</h2>
        <a href="create.php" class="btn-primary">Add New User</a> <!--[cite: 1, 3] -->
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($users) > 0): ?> <!--[cite: 3] -->
                    <?php $rowNum = 1; ?>
                    <?php foreach ($users as $user): ?> <!--[cite: 3] -->
                        <tr>
                            <td><?= $rowNum++ ?></td>
                            <td><?= htmlspecialchars($user['name']) ?></td> <!--[cite: 3] -->
                            <td><?= htmlspecialchars($user['email']) ?></td> <!--[cite: 3] -->
                            <td>
                                <a href="update.php?id=<?= $user['id'] ?>" class="btn-edit">Edit</a> <!--[cite: 3, 5] -->
                                <a href="index.php?delete_id=<?= $user['id'] ?>" class="btn-delete" onclick="return confirm('Are you sure?');">Delete</a> <!--[cite: 3] -->
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center;">No records found.</td></tr> <!--[cite: 3] -->
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>