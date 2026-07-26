<?php
/**
 * mailbox_helpers.php — shared queries for Mailbox (Step 9).
 */

function mailbox_resolve_user_id(mysqli $conn, string $email, string $role): int {
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    if ($userId > 0) {
        return $userId;
    }

    $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? AND role = ? LIMIT 1');
    if ($stmt === false) {
        return 0;
    }
    $stmt->bind_param('ss', $email, $role);
    $stmt->execute();
    $stmt->bind_result($foundId);
    if ($stmt->fetch()) {
        $userId = (int) $foundId;
        $_SESSION['user_id'] = $userId;
    }
    $stmt->close();
    return $userId;
}

function mailbox_can_access_ticket(mysqli $conn, int $ticketId, string $role, int $userId): bool {
    if ($ticketId <= 0 || $userId <= 0) {
        return false;
    }
    if ($role === 'admin') {
        $stmt = $conn->prepare('SELECT id FROM tickets WHERE id = ? LIMIT 1');
        if ($stmt === false) {
            return false;
        }
        $stmt->bind_param('i', $ticketId);
        $stmt->execute();
        $stmt->store_result();
        $ok = $stmt->num_rows > 0;
        $stmt->close();
        return $ok;
    }
    if ($role === 'user') {
        $stmt = $conn->prepare('SELECT id FROM tickets WHERE id = ? AND user_id = ? LIMIT 1');
        if ($stmt === false) {
            return false;
        }
        $stmt->bind_param('ii', $ticketId, $userId);
        $stmt->execute();
        $stmt->store_result();
        $ok = $stmt->num_rows > 0;
        $stmt->close();
        return $ok;
    }
    if ($role === 'techn') {
        $stmt = $conn->prepare('SELECT id FROM tickets WHERE id = ? AND assigned_to = ? LIMIT 1');
        if ($stmt === false) {
            return false;
        }
        $stmt->bind_param('ii', $ticketId, $userId);
        $stmt->execute();
        $stmt->store_result();
        $ok = $stmt->num_rows > 0;
        $stmt->close();
        return $ok;
    }
    return false;
}

function mailbox_fetch_conversations(mysqli $conn, string $role, int $userId): array {
    $rows = [];

    if ($role === 'user') {
        $sql = "SELECT t.id, t.subject, t.status,
                       (SELECT m.message FROM messages m WHERE m.ticket_id = t.id ORDER BY m.sent_at DESC LIMIT 1) AS last_message,
                       (SELECT m.sent_at FROM messages m WHERE m.ticket_id = t.id ORDER BY m.sent_at DESC LIMIT 1) AS last_sent_at,
                       tech.first_name AS party_first, tech.last_name AS party_last
                FROM tickets t
                LEFT JOIN users tech ON t.assigned_to = tech.id
                WHERE t.user_id = ?
                  AND (t.assigned_to IS NOT NULL
                       OR EXISTS (SELECT 1 FROM messages m WHERE m.ticket_id = t.id))
                ORDER BY COALESCE(last_sent_at, t.updated_at) DESC";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return [];
        }
        $stmt->bind_param('i', $userId);
    } elseif ($role === 'techn') {
        $sql = "SELECT t.id, t.subject, t.status,
                       (SELECT m.message FROM messages m WHERE m.ticket_id = t.id ORDER BY m.sent_at DESC LIMIT 1) AS last_message,
                       (SELECT m.sent_at FROM messages m WHERE m.ticket_id = t.id ORDER BY m.sent_at DESC LIMIT 1) AS last_sent_at,
                       u.first_name AS party_first, u.last_name AS party_last
                FROM tickets t
                INNER JOIN users u ON t.user_id = u.id
                WHERE t.assigned_to = ?
                ORDER BY COALESCE(last_sent_at, t.updated_at) DESC";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return [];
        }
        $stmt->bind_param('i', $userId);
    } else {
        $sql = "SELECT t.id, t.subject, t.status,
                       (SELECT m.message FROM messages m WHERE m.ticket_id = t.id ORDER BY m.sent_at DESC LIMIT 1) AS last_message,
                       (SELECT m.sent_at FROM messages m WHERE m.ticket_id = t.id ORDER BY m.sent_at DESC LIMIT 1) AS last_sent_at,
                       u.first_name AS party_first, u.last_name AS party_last,
                       tech.first_name AS tech_first, tech.last_name AS tech_last
                FROM tickets t
                INNER JOIN users u ON t.user_id = u.id
                LEFT JOIN users tech ON t.assigned_to = tech.id
                WHERE t.assigned_to IS NOT NULL
                   OR EXISTS (SELECT 1 FROM messages m WHERE m.ticket_id = t.id)
                ORDER BY COALESCE(last_sent_at, t.updated_at) DESC";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return [];
        }
    }

    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    return $rows;
}

function mailbox_fetch_messages(mysqli $conn, int $ticketId): array {
    $stmt = $conn->prepare(
        "SELECT m.id, m.message, m.sent_at, m.sender_id,
                u.first_name, u.last_name, u.role
         FROM messages m
         INNER JOIN users u ON m.sender_id = u.id
         WHERE m.ticket_id = ?
         ORDER BY m.sent_at ASC"
    );
    if ($stmt === false) {
        return [];
    }
    $stmt->bind_param('i', $ticketId);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    return $rows;
}

function mailbox_fetch_messages_since(mysqli $conn, int $ticketId, int $afterId): array {
    if ($afterId <= 0) {
        return mailbox_fetch_messages($conn, $ticketId);
    }

    $stmt = $conn->prepare(
        "SELECT m.id, m.message, m.sent_at, m.sender_id,
                u.first_name, u.last_name, u.role
         FROM messages m
         INNER JOIN users u ON m.sender_id = u.id
         WHERE m.ticket_id = ? AND m.id > ?
         ORDER BY m.sent_at ASC"
    );
    if ($stmt === false) {
        return [];
    }
    $stmt->bind_param('ii', $ticketId, $afterId);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    return $rows;
}

function mailbox_fetch_message_by_id(mysqli $conn, int $messageId): ?array {
    $stmt = $conn->prepare(
        "SELECT m.id, m.message, m.sent_at, m.sender_id,
                u.first_name, u.last_name, u.role
         FROM messages m
         INNER JOIN users u ON m.sender_id = u.id
         WHERE m.id = ?
         LIMIT 1"
    );
    if ($stmt === false) {
        return null;
    }
    $stmt->bind_param('i', $messageId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function mailbox_conversation_party_label(array $conv, string $role): string {
    if ($role === 'admin') {
        $owner = trim(($conv['party_first'] ?? '') . ' ' . ($conv['party_last'] ?? ''));
        $tech  = trim(($conv['tech_first'] ?? '') . ' ' . ($conv['tech_last'] ?? ''));
        return $owner . ($tech !== '' ? ' · ' . $tech : '');
    }

    $partyLabel = trim(($conv['party_first'] ?? '') . ' ' . ($conv['party_last'] ?? ''));
    if ($partyLabel === '') {
        return $role === 'user' ? 'Technician' : 'User';
    }
    return $partyLabel;
}

function mailbox_message_to_api(array $msg, int $currentUserId, string $role): array {
    $isSent = (int) $msg['sender_id'] === $currentUserId;
    $senderName = trim(($msg['first_name'] ?? '') . ' ' . ($msg['last_name'] ?? ''));

    return [
        'id'           => (int) $msg['id'],
        'message'      => $msg['message'],
        'time_label'   => mailbox_format_time($msg['sent_at']),
        'is_sent'      => $isSent,
        'sender_name'  => $senderName,
        'show_sender'  => $role === 'admin' && !$isSent,
    ];
}

function mailbox_messages_to_api(array $messages, int $currentUserId, string $role): array {
    $out = [];
    foreach ($messages as $msg) {
        $out[] = mailbox_message_to_api($msg, $currentUserId, $role);
    }
    return $out;
}

function mailbox_conversations_to_api(mysqli $conn, string $role, int $userId): array {
    $conversations = mailbox_fetch_conversations($conn, $role, $userId);
    $out = [];

    foreach ($conversations as $conv) {
        $cid = (int) $conv['id'];
        $out[] = [
            'id'           => $cid,
            'subject'      => $conv['subject'],
            'ticket_label' => '#' . str_pad((string) $cid, 4, '0', STR_PAD_LEFT),
            'party_label'  => mailbox_conversation_party_label($conv, $role),
            'preview'      => mailbox_preview((string) ($conv['last_message'] ?? '')),
            'time_label'   => mailbox_format_time($conv['last_sent_at'] ?? null),
        ];
    }

    return $out;
}

function mailbox_fetch_ticket_header(mysqli $conn, int $ticketId): ?array {
    $stmt = $conn->prepare(
        "SELECT t.id, t.subject, t.status,
                u.first_name AS owner_first, u.last_name AS owner_last,
                tech.first_name AS tech_first, tech.last_name AS tech_last
         FROM tickets t
         INNER JOIN users u ON t.user_id = u.id
         LEFT JOIN users tech ON t.assigned_to = tech.id
         WHERE t.id = ?
         LIMIT 1"
    );
    if ($stmt === false) {
        return null;
    }
    $stmt->bind_param('i', $ticketId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function mailbox_format_time(?string $datetime): string {
    if ($datetime === null || $datetime === '') {
        return '';
    }
    $ts = strtotime($datetime);
    if ($ts === false) {
        return '';
    }
    return date('M j, g:i A', $ts);
}

function mailbox_preview(string $text, int $max = 60): string {
    $text = trim($text);
    if ($text === '') {
        return 'No messages yet';
    }
    return mb_strlen($text) > $max ? mb_substr($text, 0, $max) . '…' : $text;
}
