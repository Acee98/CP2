<?php
/**
 * Mailbox UI partial — expects:
 *   $mailbox_conversations, $mailbox_active_ticket_id, $mailbox_messages,
 *   $mailbox_active_ticket, $mailbox_current_user_id, $mailbox_role,
 *   $mailbox_success, $mailbox_error
 */
$activeId = (int) ($mailbox_active_ticket_id ?? 0);
?>
<?php if (!empty($mailbox_success)): ?>
    <div class="utilities-notice"><?= htmlspecialchars($mailbox_success) ?></div>
<?php endif; ?>
<?php if (!empty($mailbox_error)): ?>
    <div class="utilities-notice-error"><?= htmlspecialchars($mailbox_error) ?></div>
<?php endif; ?>

<div class="mailbox-container" id="mailbox-live-root"
    data-api-url="../logic/mailbox_api.php"
    data-current-user-id="<?= (int) $mailbox_current_user_id ?>"
    data-role="<?= htmlspecialchars($mailbox_role, ENT_QUOTES, 'UTF-8') ?>"
    data-active-ticket-id="<?= $activeId ?>">

    <aside class="mailbox-threads">
        <div class="mailbox-threads-header">
            <span class="mailbox-threads-title">Conversations</span>
        </div>
        <div class="mailbox-threads-list">
            <?php if (empty($mailbox_conversations)): ?>
                <div class="mailbox-threads-empty">
                    <p>No conversations yet.</p>
                    <p>
                        <?php if ($mailbox_role === 'user'): ?>
                            Once a ticket is assigned to a technician, you can message them here.
                        <?php elseif ($mailbox_role === 'techn'): ?>
                            Assigned tickets appear here so you can chat with the ticket owner.
                        <?php else: ?>
                            Conversations appear when tickets are assigned or have messages.
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <?php foreach ($mailbox_conversations as $conv): ?>
                    <?php
                        $cid = (int) $conv['id'];
                        if ($mailbox_role === 'admin') {
                            $owner = trim(($conv['party_first'] ?? '') . ' ' . ($conv['party_last'] ?? ''));
                            $tech  = trim(($conv['tech_first'] ?? '') . ' ' . ($conv['tech_last'] ?? ''));
                            $partyLabel = $owner . ($tech !== '' ? ' · ' . $tech : '');
                        } else {
                            $partyLabel = trim(($conv['party_first'] ?? '') . ' ' . ($conv['party_last'] ?? ''));
                            if ($partyLabel === '') {
                                $partyLabel = $mailbox_role === 'user' ? 'Technician' : 'User';
                            }
                        }
                        $preview = mailbox_preview((string) ($conv['last_message'] ?? ''));
                        $timeLabel = mailbox_format_time($conv['last_sent_at'] ?? null);
                        $isActive = $cid === $activeId;
                    ?>
                    <a href="?tab=messages&amp;ticket_id=<?= $cid ?>"
                        class="mailbox-thread-item<?= $isActive ? ' active' : '' ?>">
                        <div class="mailbox-thread-meta">
                            <span class="mailbox-thread-subject">#<?= str_pad((string) $cid, 4, '0', STR_PAD_LEFT) ?>
                                · <?= htmlspecialchars($conv['subject']) ?></span>
                            <?php if ($timeLabel !== ''): ?>
                                <span class="mailbox-thread-time"><?= htmlspecialchars($timeLabel) ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="mailbox-thread-party"><?= htmlspecialchars($partyLabel) ?></span>
                        <span class="mailbox-thread-preview"><?= htmlspecialchars($preview) ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </aside>

    <div class="mailbox-chat">
        <?php if ($activeId <= 0 || empty($mailbox_active_ticket)): ?>
            <div class="mailbox-chat-empty">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M4 19h3v2c0 .36.19.69.51.87a1 1 0 0 0 1-.01L13.27 19h6.72c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2M4 5h16v12h-7c-.18 0-.36.05-.51.14L9 19.23V18c0-.55-.45-1-1-1H4z"></path>
                </svg>
                <p class="mailbox-chat-empty-title">No conversation selected</p>
                <p class="mailbox-chat-empty-sub">Select a conversation from the left to view and send messages.</p>
            </div>
        <?php else: ?>
            <?php
                $headerTicket = $mailbox_active_ticket;
                $ownerName = trim(($headerTicket['owner_first'] ?? '') . ' ' . ($headerTicket['owner_last'] ?? ''));
                $techName  = trim(($headerTicket['tech_first'] ?? '') . ' ' . ($headerTicket['tech_last'] ?? ''));
                if ($mailbox_role === 'admin') {
                    $chatSubtitle = $ownerName . ($techName !== '' ? ' · Tech: ' . $techName : '');
                } elseif ($mailbox_role === 'user') {
                    $chatSubtitle = $techName !== '' ? 'Technician: ' . $techName : 'Assigned technician';
                } else {
                    $chatSubtitle = 'User: ' . $ownerName;
                }
            ?>
            <div class="mailbox-chat-header">
                <span class="mailbox-chat-header-subject">#<?= str_pad((string) $activeId, 4, '0', STR_PAD_LEFT) ?>
                    · <?= htmlspecialchars($headerTicket['subject']) ?></span>
                <span class="mailbox-chat-header-sub"><?= htmlspecialchars($chatSubtitle) ?></span>
            </div>
            <div class="mailbox-chat-messages" id="mailbox-chat-messages">
                <?php if (empty($mailbox_messages)): ?>
                    <div class="mailbox-chat-no-msgs">No messages yet. Start the conversation below.</div>
                <?php else: ?>
                    <?php foreach ($mailbox_messages as $msg): ?>
                        <?php
                            $isSent = (int) $msg['sender_id'] === (int) $mailbox_current_user_id;
                            $msgClass = $isSent ? 'sent' : 'received';
                            $senderName = trim(($msg['first_name'] ?? '') . ' ' . ($msg['last_name'] ?? ''));
                        ?>
                        <div class="mailbox-msg <?= $msgClass ?>" data-message-id="<?= (int) $msg['id'] ?>">
                            <?php if ($mailbox_role === 'admin' && !$isSent): ?>
                                <span class="mailbox-msg-sender"><?= htmlspecialchars($senderName) ?></span>
                            <?php endif; ?>
                            <div class="mailbox-msg-bubble"><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
                            <span class="mailbox-msg-time"><?= htmlspecialchars(mailbox_format_time($msg['sent_at'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <form class="mailbox-chat-input-row" id="mailbox-send-form"
                action="../logic/message_mngmnt.php" method="post">
                <input type="hidden" name="ticket_id" value="<?= $activeId ?>">
                <input type="text" name="message" class="mailbox-chat-input" placeholder="Type a message…"
                    maxlength="5000" required autocomplete="off">
                <button type="submit" name="send_message" class="mailbox-chat-send" aria-label="Send message">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M2.01 21 23 12 2.01 3 2 10l15 2-15 2z"></path>
                    </svg>
                </button>
            </form>
        <?php endif; ?>
    </div>

</div>
