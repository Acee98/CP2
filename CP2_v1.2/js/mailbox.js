/**
 * mailbox.js — live chat: AJAX send + polling for new messages.
 */
(function () {
    const POLL_MS = 3000;

    function escapeHtml(text) {
        const el = document.createElement('div');
        el.textContent = text;
        return el.innerHTML;
    }

    function formatMessageBody(text) {
        return escapeHtml(text).replace(/\n/g, '<br>');
    }

    function scrollToBottom(messagesEl, force) {
        if (!messagesEl) {
            return;
        }
        const nearBottom = messagesEl.scrollHeight - messagesEl.scrollTop - messagesEl.clientHeight < 80;
        if (force || nearBottom) {
            messagesEl.scrollTop = messagesEl.scrollHeight;
        }
    }

    function getMaxMessageId(messagesEl) {
        let max = 0;
        messagesEl.querySelectorAll('[data-message-id]').forEach((node) => {
            const id = parseInt(node.dataset.messageId, 10);
            if (id > max) {
                max = id;
            }
        });
        return max;
    }

    function buildMessageNode(msg) {
        const wrap = document.createElement('div');
        wrap.className = 'mailbox-msg ' + (msg.is_sent ? 'sent' : 'received');
        wrap.dataset.messageId = String(msg.id);

        if (msg.show_sender && msg.sender_name) {
            const sender = document.createElement('span');
            sender.className = 'mailbox-msg-sender';
            sender.textContent = msg.sender_name;
            wrap.appendChild(sender);
        }

        const bubble = document.createElement('div');
        bubble.className = 'mailbox-msg-bubble';
        bubble.innerHTML = formatMessageBody(msg.message);
        wrap.appendChild(bubble);

        const time = document.createElement('span');
        time.className = 'mailbox-msg-time';
        time.textContent = msg.time_label;
        wrap.appendChild(time);

        return wrap;
    }

    function appendMessages(messagesEl, messages) {
        if (!messagesEl || !messages || !messages.length) {
            return false;
        }

        const empty = messagesEl.querySelector('.mailbox-chat-no-msgs');
        if (empty) {
            empty.remove();
        }

        const existingIds = new Set();
        messagesEl.querySelectorAll('[data-message-id]').forEach((node) => {
            existingIds.add(parseInt(node.dataset.messageId, 10));
        });

        let added = false;
        messages.forEach((msg) => {
            if (existingIds.has(msg.id)) {
                return;
            }
            messagesEl.appendChild(buildMessageNode(msg));
            added = true;
        });

        return added;
    }

    function updateThreadList(listEl, conversations, activeTicketId) {
        if (!listEl || !conversations || !conversations.length) {
            return;
        }

        const empty = listEl.querySelector('.mailbox-threads-empty');
        if (empty) {
            empty.remove();
        }

        conversations.forEach((conv) => {
            let link = listEl.querySelector(`a.mailbox-thread-item[href*="ticket_id=${conv.id}"]`);
            if (!link) {
                link = document.createElement('a');
                link.className = 'mailbox-thread-item';
                link.href = `?tab=messages&ticket_id=${conv.id}`;
                link.innerHTML =
                    '<div class="mailbox-thread-meta">' +
                    '<span class="mailbox-thread-subject"></span>' +
                    '<span class="mailbox-thread-time"></span>' +
                    '</div>' +
                    '<span class="mailbox-thread-party"></span>' +
                    '<span class="mailbox-thread-preview"></span>';
                listEl.appendChild(link);
            }

            link.classList.toggle('active', conv.id === activeTicketId);
            link.querySelector('.mailbox-thread-subject').textContent =
                `${conv.ticket_label} · ${conv.subject}`;

            const timeEl = link.querySelector('.mailbox-thread-time');
            if (conv.time_label) {
                timeEl.textContent = conv.time_label;
                timeEl.style.display = '';
            } else {
                timeEl.textContent = '';
                timeEl.style.display = 'none';
            }

            link.querySelector('.mailbox-thread-party').textContent = conv.party_label;
            link.querySelector('.mailbox-thread-preview').textContent = conv.preview;
        });

        conversations.forEach((conv) => {
            const link = listEl.querySelector(`a.mailbox-thread-item[href*="ticket_id=${conv.id}"]`);
            if (link) {
                listEl.appendChild(link);
            }
        });
    }

    function isMessagesTabActive() {
        return document.body.getAttribute('data-page') === 'messages';
    }

    document.addEventListener('DOMContentLoaded', () => {
        const root = document.getElementById('mailbox-live-root');
        if (!root) {
            return;
        }

        const apiUrl = root.dataset.apiUrl;
        const activeTicketId = parseInt(root.dataset.activeTicketId, 10) || 0;
        const messagesEl = document.getElementById('mailbox-chat-messages');
        const threadsList = root.querySelector('.mailbox-threads-list');
        const form = document.getElementById('mailbox-send-form');

        if (messagesEl) {
            scrollToBottom(messagesEl, true);
        }

        let pollTimer = null;
        let pollInFlight = false;

        async function poll() {
            if (pollInFlight || document.hidden || !isMessagesTabActive()) {
                return;
            }
            pollInFlight = true;

            try {
                const params = new URLSearchParams({ action: 'poll' });
                if (activeTicketId > 0) {
                    params.set('ticket_id', String(activeTicketId));
                    if (messagesEl) {
                        params.set('after_id', String(getMaxMessageId(messagesEl)));
                    }
                }

                const res = await fetch(`${apiUrl}?${params.toString()}`, {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                });

                if (!res.ok) {
                    return;
                }

                const data = await res.json();
                if (!data.ok) {
                    return;
                }

                if (data.conversations) {
                    updateThreadList(threadsList, data.conversations, activeTicketId);
                }

                if (messagesEl && data.messages && data.messages.length) {
                    const added = appendMessages(messagesEl, data.messages);
                    if (added) {
                        scrollToBottom(messagesEl, false);
                    }
                }
            } catch (err) {
                // Ignore transient network errors during polling.
            } finally {
                pollInFlight = false;
            }
        }

        function startPolling() {
            if (pollTimer) {
                return;
            }
            pollTimer = setInterval(poll, POLL_MS);
        }

        function stopPolling() {
            if (!pollTimer) {
                return;
            }
            clearInterval(pollTimer);
            pollTimer = null;
        }

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                stopPolling();
                return;
            }
            poll();
            startPolling();
        });

        if (form && activeTicketId > 0) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                const input = form.querySelector('.mailbox-chat-input');
                const sendBtn = form.querySelector('.mailbox-chat-send');
                const message = (input.value || '').trim();
                if (!message) {
                    return;
                }

                sendBtn.disabled = true;

                try {
                    const body = new FormData();
                    body.append('action', 'send');
                    body.append('ticket_id', String(activeTicketId));
                    body.append('message', message);
                    body.append('send_message', '1');

                    const res = await fetch(apiUrl, {
                        method: 'POST',
                        body,
                        credentials: 'same-origin',
                        headers: { Accept: 'application/json' },
                    });

                    const data = await res.json();
                    if (!data.ok) {
                        alert(data.error || 'Could not send message.');
                        return;
                    }

                    input.value = '';

                    if (messagesEl && data.message) {
                        appendMessages(messagesEl, [data.message]);
                        scrollToBottom(messagesEl, true);
                    }

                    if (data.conversations) {
                        updateThreadList(threadsList, data.conversations, activeTicketId);
                    }
                } catch (err) {
                    alert('Could not send message. Please try again.');
                } finally {
                    sendBtn.disabled = false;
                    input.focus();
                }
            });
        }

        startPolling();
        setTimeout(poll, 500);
    });
})();
