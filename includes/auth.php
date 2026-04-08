<?php
/**
 * Session helpers for admin vs hostel_owner dashboards.
 * Include after config/db.php on pages under modules/dashboard/.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Default permission flags for new hostel_owner accounts (admin can override per user). */
function auth_default_owner_permissions(): array
{
    return [
        'view_hostels' => true,
        'edit_hostel' => true,
        'manage_rooms' => true,
        'view_bookings' => true,
        'manage_bookings' => false,
    ];
}

function auth_merge_owner_permissions(?string $json): array
{
    $defaults = auth_default_owner_permissions();
    $decoded = json_decode($json ?? '', true);
    if (!is_array($decoded)) {
        return $defaults;
    }
    return array_merge($defaults, $decoded);
}

function auth_require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ../auth/login.php');
        exit;
    }
}

function auth_require_admin(): void
{
    auth_require_login();
    $t = $_SESSION['user_type'] ?? '';
    if ($t === 'admin') {
        return;
    }
    if ($t === 'hostel_owner') {
        header('Location: owner_hostels.php');
        exit;
    }
    header('Location: no_access.php');
    exit;
}

function auth_require_owner(): void
{
    auth_require_login();
    $t = $_SESSION['user_type'] ?? '';
    if ($t === 'hostel_owner') {
        return;
    }
    if ($t === 'admin') {
        header('Location: index.php');
        exit;
    }
    header('Location: no_access.php');
    exit;
}

/** True if current user is a hostel owner with the given permission (admins always true). */
function auth_owner_can(string $key): bool
{
    if (($_SESSION['user_type'] ?? '') === 'admin') {
        return true;
    }
    $perms = $_SESSION['owner_permissions'] ?? [];
    return !empty($perms[$key]);
}

/** Reload name/email/type/permissions from DB (e.g. after admin changes flags). */
function auth_refresh_session_from_db(mysqli $conn, int $userId): void
{
    $stmt = mysqli_prepare($conn, "
        SELECT name, email, user_type, permissions_json FROM users WHERE id = ? AND deleted_at IS NULL LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $name, $email, $utype, $permJson);
    if (mysqli_stmt_fetch($stmt)) {
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_type'] = $utype ?? 'student';
        $_SESSION['owner_permissions'] = auth_merge_owner_permissions($permJson);
    }
    mysqli_stmt_close($stmt);
}
