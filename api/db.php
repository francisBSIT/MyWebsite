<?php
/**
 * Database connection + shared helpers using environment variables for cloud deployment.
 */
$dbHost    = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
$dbName    = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'crud_db';
$dbUser    = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
$dbPass    = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';
$dbPort    = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
$dbCharset = 'utf8mb4';

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Singleton PDO connection. */
function db(): PDO
{
    global $dbHost, $dbName, $dbUser, $dbPass, $dbPort, $dbCharset;
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset={$dbCharset}";
        try {
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            error_log('DB connection failed: ' . $e->getMessage());
            http_response_code(500);
            exit('Database connection failed. Ensure environment variables are set correctly in Vercel.');
        }
    }
    return $pdo;
}

function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(?string $token): bool
{
    return is_string($token) && hash_equals(csrf_token(), $token);
}

/* ---------- Flash messages ---------- */

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- Users ---------- */

function validate_user(array $in): array
{
    $name  = trim(preg_replace('/\s+/u', ' ', (string)($in['name'] ?? '')));
    $email = strtolower(trim((string)($in['email'] ?? '')));
    $errors = [];

    if ($name === '') {
        $errors['name'] = 'Name is required.';
    } elseif (mb_strlen($name) < 2) {
        $errors['name'] = 'Name must be at least 2 characters.';
    } elseif (mb_strlen($name) > 100) {
        $errors['name'] = 'Name must be 100 characters or fewer.';
    }

    if ($email === '') {
        $errors['email'] = 'Email is required.';
    } elseif (strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    return ['name' => $name, 'email' => $email, 'errors' => $errors];
}

function email_taken(PDO $pdo, string $email, int $exceptId = 0): bool
{
    $st = $pdo->prepare('SELECT 1 FROM users WHERE email = ? AND id <> ? LIMIT 1');
    $st->execute([$email, $exceptId]);
    return (bool)$st->fetchColumn();
}

function find_user(PDO $pdo, int $id): ?array
{
    $st = $pdo->prepare('SELECT id, name, email FROM users WHERE id = ?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}