
<?php
// includes/audit.php

require_once __DIR__ . '/functions.php';

/**
 * Get the authenticated user's ID.
 */
function audit_user_id(): ?int
{
    if (empty($_SESSION['user']['id'])) {
        return null;
    }

    return (int) $_SESSION['user']['id'];
}

/**
 * Get the current client IP.
 * Do not trust forwarded headers unless your proxy is configured.
 */
function audit_ip(): string
{
    return substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45);
}

/**
 * Hash the current PHP session ID.
 * Never store the raw session ID in audit tables.
 */
function audit_session_hash(): ?string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return null;
    }

    return hash('sha256', session_id());
}

/**
 * Start a login history record after successful OTP verification.
 */
function audit_login_success(): void
{
    global $pdo;

    $userId = audit_user_id();

    if (!$userId) {
        return;
    }

    $hash = audit_session_hash();

    $stmt = $pdo->prepare("
        INSERT INTO user_login_history
        (
            user_id,
            session_id_hash,
            login_at,
            last_activity_at,
            ip_address,
            user_agent,
            login_status
        )
        VALUES (?, ?, NOW(), NOW(), ?, ?, 'Success')
    ");

    $stmt->execute([
        $userId,
        $hash,
        audit_ip(),
        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500)
    ]);

    $_SESSION['audit_login_id'] = (int) $pdo->lastInsertId();

    audit_log(
        'LOGIN',
        'Authentication',
        'Login successful',
        null,
        null,
        null,
        ['method' => 'Password + Email OTP']
    );
}

/**
 * Record a general activity.
 */
function audit_log(
    string $eventType,
    ?string $module = null,
    ?string $action = null,
    ?string $recordType = null,
    $recordId = null,
    ?string $description = null,
    array $metadata = [],
    ?array $oldValues = null,
    ?array $newValues = null
): void {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_activity_logs
            (
                user_id,
                login_history_id,
                event_type,
                module_name,
                action_name,
                record_type,
                record_id,
                description,
                old_values,
                new_values,
                metadata,
                ip_address,
                request_uri
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            audit_user_id(),
            $_SESSION['audit_login_id'] ?? null,
            $eventType,
            $module,
            $action,
            $recordType,
            $recordId !== null ? (string)$recordId : null,
            $description,
            $oldValues !== null ? json_encode($oldValues) : null,
            $newValues !== null ? json_encode($newValues) : null,
            !empty($metadata) ? json_encode($metadata) : null,
            audit_ip(),
            substr($_SERVER['REQUEST_URI'] ?? '', 0, 500)
        ]);

    } catch (Throwable $e) {
        // Audit failure must not expose database details to the user.
        error_log('CRM audit logging failed: ' . $e->getMessage());
    }
}

/**
 * Record security events such as copy/paste attempts.
 * This records the event, never clipboard contents.
 */
function audit_security_event(
    string $eventType,
    ?string $details = null
): void {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_security_events
            (
                user_id,
                login_history_id,
                event_type,
                event_details,
                page_url,
                ip_address
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            audit_user_id(),
            $_SESSION['audit_login_id'] ?? null,
            $eventType,
            $details,
            substr($_SERVER['REQUEST_URI'] ?? '', 0, 500),
            audit_ip()
        ]);

    } catch (Throwable $e) {
        error_log('CRM security audit failed: ' . $e->getMessage());
    }
}

/**
 * Update last activity time.
 */
function audit_touch(): void
{
    global $pdo;

    $loginId = $_SESSION['audit_login_id'] ?? null;

    if (!$loginId) {
        return;
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE user_login_history
            SET last_activity_at = NOW()
            WHERE id = ? AND logout_at IS NULL
        ");

        $stmt->execute([(int)$loginId]);

    } catch (Throwable $e) {
        error_log('CRM session audit failed: ' . $e->getMessage());
    }
}

/**
 * Record logout and clear audit session reference.
 */
function audit_logout(string $reason = 'User logout'): void
{
    global $pdo;

    $loginId = $_SESSION['audit_login_id'] ?? null;

    if ($loginId) {
        try {
            $stmt = $pdo->prepare("
                UPDATE user_login_history
                SET logout_at = NOW(),
                    last_activity_at = NOW(),
                    logout_reason = ?
                WHERE id = ? AND logout_at IS NULL
            ");

            $stmt->execute([
                $reason,
                (int)$loginId
            ]);

            audit_log(
                'LOGOUT',
                'Authentication',
                'Logout',
                null,
                null,
                $reason
            );

        } catch (Throwable $e) {
            error_log('CRM logout audit failed: ' . $e->getMessage());
        }
    }

    unset($_SESSION['audit_login_id']);
}
