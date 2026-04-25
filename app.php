<?php
// Serve this file as JavaScript while keeping it in PHP for easy includes.
header('Content-Type: application/javascript; charset=UTF-8');
?>
let selectedRows = new Set();

const messageData = Array.isArray(window.emailData) ? window.emailData : [];
const messageMap = new Map(messageData.map(item => [String(item.messageId), item]));
const threadMap = new Map();

messageData.forEach(msg => {
    if (!threadMap.has(msg.threadId)) {
        threadMap.set(msg.threadId, []);
    }
    threadMap.get(msg.threadId).push(msg);
});

let activeMessageId = null;
let activeThreadId = null;

function escapeHtml(text) {
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function updateFilteredView() {
    const rows = Array.from(document.querySelectorAll('#emailBody tr'));
    const visibleRows = rows.filter(row => row.style.display !== 'none');
    const empty = document.getElementById('emptyState');
    const convCount = document.getElementById('conv-count');

    if (visibleRows.length === 0) {
        empty.classList.remove('hidden');
        empty.classList.add('flex');
    } else {
        empty.classList.add('hidden');
        empty.classList.remove('flex');
    }

    convCount.textContent = `${visibleRows.length} message${visibleRows.length !== 1 ? 's' : ''}`;
}

function filterTable() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');

    const search = searchInput ? searchInput.value.toLowerCase() : '';
    const status = statusFilter ? statusFilter.value.toLowerCase() : '';

    const rows = document.querySelectorAll('#emailBody tr');

    rows.forEach(row => {
        const rowFrom = row.dataset.from || '';
        const rowSubject = row.dataset.subject || '';
        const rowBody = row.dataset.body || '';

        const matchSearch = !search || rowFrom.includes(search) || rowSubject.includes(search) || rowBody.includes(search);

        row.style.display = matchSearch ? '' : 'none';
    });

    updateFilteredView();
}

function toggleRow(messageId, checkbox) {
    if (checkbox.checked) selectedRows.add(messageId);
    else selectedRows.delete(messageId);

    const row = document.querySelector(`tr[data-id="${messageId}"]`);
    if (row) row.classList.toggle('selected', checkbox.checked);
    updateBulkBar();
}

function toggleAll(masterCb) {
    const visibleCheckboxes = Array.from(document.querySelectorAll('#emailBody tr'))
        .filter(row => row.style.display !== 'none')
        .map(row => row.querySelector('input[type=checkbox]'))
        .filter(Boolean);

    visibleCheckboxes.forEach(cb => {
        cb.checked = masterCb.checked;
        const messageId = cb.closest('tr').dataset.id;
        if (masterCb.checked) selectedRows.add(messageId);
        else selectedRows.delete(messageId);
        cb.closest('tr').classList.toggle('selected', masterCb.checked);
    });

    updateBulkBar();
}

function clearSelection() {
    selectedRows.clear();

    document.querySelectorAll('#emailBody input[type=checkbox]').forEach(cb => {
        cb.checked = false;
        cb.closest('tr')?.classList.remove('selected');
    });

    const master = document.querySelector('thead input[type=checkbox]');
    if (master) master.checked = false;

    updateBulkBar();
}

function updateBulkBar() {
    const bulkBar = document.getElementById('bulkBar');
    const bulkCount = document.getElementById('bulkCount');
    if (!bulkBar || !bulkCount) return;

    const count = selectedRows.size;
    bulkCount.textContent = `${count} selected`;
    bulkBar.classList.toggle('hidden', count === 0);
    bulkBar.classList.toggle('flex', count > 0);
}

function handleRowClick(e, messageId) {
    if (e.target.type === 'checkbox') return;
    openMessageModal(messageId);
}

function renderThreadMessages(message) {
    const threadEl = document.getElementById('messageThread');
    if (!threadEl || !message.threadId) return;

    const threadContent = Array.isArray(message.thread) ? message.thread : [];

    if (threadContent.length === 0) {
        threadEl.innerHTML = `
            <div class="rounded-xl border border-dashed border-slate-300 bg-white px-5 py-6 text-center text-sm text-slate-500">
                No messages in this conversation yet.
            </div>
        `;
        return;
    }

    const isSentRoot = (message.type || 'received') === 'sent';
    const externalName = isSentRoot ? (message.to || 'Recipient') : (message.from || 'Sender');

    threadEl.innerHTML = threadContent.map((msg, index) => {
        const fromName = msg.from || 'Unknown';
        const isMine = fromName.toLowerCase() === 'you'
            || fromName.toLowerCase() === 'me'
            || fromName.toLowerCase() !== externalName.toLowerCase();

        const rowAlignClass = isMine ? 'justify-end' : 'justify-start';
        const bubbleStyle = isMine
            ? 'border-blue-200 bg-blue-50 text-slate-800'
            : 'border-slate-200 bg-white text-slate-700';
        const avatarStyle = isMine
            ? 'bg-slate-900 text-white'
            : 'bg-white border border-slate-200 text-slate-600';
        const senderLabel = isMine ? 'You' : fromName;
        const initials = senderLabel
            .split(' ')
            .filter(Boolean)
            .slice(0, 2)
            .map(part => part[0])
            .join('')
            .toUpperCase() || '?';

        return `
            <div class="flex ${rowAlignClass}">
                <article class="max-w-[85%] rounded-2xl border p-4 shadow-sm ${bubbleStyle}">
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full text-[10px] font-semibold ${avatarStyle}">${escapeHtml(initials)}</span>
                            <p class="text-sm font-semibold">${escapeHtml(senderLabel)}</p>
                        </div>
                        <span class="text-xs text-slate-400">${escapeHtml(msg.time || (index + 1))}</span>
                    </div>
                    <p class="whitespace-pre-wrap text-sm leading-6">${escapeHtml(msg.body || '')}</p>
                </article>
            </div>
        `;
    }).join('');

    threadEl.scrollTop = threadEl.scrollHeight;
}

function openMessageModal(messageId) {
    const message = messageMap.get(String(messageId));
    const dialog = document.getElementById('messageDialog');
    const titleEl = document.getElementById('messageDialogTitle');
    const metaEl = document.getElementById('messageDialogMeta');
    const subMetaEl = document.getElementById('messageDialogSubMeta');

    if (!message || !dialog || !titleEl || !metaEl) return;

    activeMessageId = String(messageId);
    activeThreadId = message.threadId;
    
    titleEl.textContent = message.subject || 'Message';
    
    // Show "From" for received messages, "To" for sent messages
    const isSent = (message.type || 'received') === 'sent';
    const contactName = isSent ? (message.to || 'Unknown') : (message.from || 'Unknown');
    const contactEmail = isSent ? (message.toEmail || '') : (message.fromEmail || '');
    const label = isSent ? 'To' : 'From';
    metaEl.textContent = `${label} ${contactName} • ${contactEmail}`;
    if (subMetaEl) {
        const count = Array.isArray(message.thread) ? message.thread.length : 0;
        subMetaEl.textContent = `${count} message${count !== 1 ? 's' : ''} in this thread`;
    }
    
    renderThreadMessages(message);

    if (typeof dialog.showModal === 'function' && !dialog.open) {
        dialog.showModal();
    }
}

function closeConversationModal() {
    const dialog = document.getElementById('messageDialog');
    if (dialog && dialog.open) {
        dialog.close();
    }
}

function openComposeModal() {
    const dialog = document.getElementById('composeDialog');
    if (!dialog) return;

    if (typeof dialog.showModal === 'function' && !dialog.open) {
        dialog.showModal();
    }

    const toInput = document.getElementById('composeTo');
    if (toInput) toInput.focus();
}

function closeComposeModal() {
    const dialog = document.getElementById('composeDialog');
    if (dialog && dialog.open) {
        dialog.close();
    }
}

function buildInitials(name) {
    return String(name || '')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map(part => part[0])
        .join('')
        .toUpperCase() || 'ME';
}

function buildTimeLabel() {
    return 'Just now';
}

function renderPreview(body) {
    const text = String(body || '');
    return text.length > 60 ? `${text.slice(0, 60)}...` : text;
}

function sendReply(event) {
    event.preventDefault();

    const input = document.getElementById('replyInput');
    if (!input || !activeMessageId || !activeThreadId) return;

    const messageBody = input.value.trim();
    if (!messageBody) return;

    const message = messageMap.get(activeMessageId);
    if (!message) return;

    if (!Array.isArray(message.thread)) {
        message.thread = [];
    }

    message.thread.push({
        from: 'You',
        body: messageBody,
        time: 'Just now',
    });

    renderThreadMessages(message);
    input.value = '';
    input.focus();

    // Send to server
    fetch("DB_Ops.php?action=update", {
        method: "POST",
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            chat_id: activeThreadId,
            message: messageBody
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.message !== "Reply added") {
            alert("Error: " + data.message);
            return;
        }
        const row = document.querySelector(`tr[data-thread-id="${activeThreadId}"]`);
        if (row) {
            const preview = row.querySelector('td:nth-child(3) div:last-child');
            if (preview) {
                preview.innerHTML = `<span class="text-slate-400">You: </span>${messageBody.substring(0,50)}`;
        }   }
    })
    .catch(console.error);
}

// Marks the clicked sidebar link as active.
function setActive(el) {
    document.querySelectorAll('.sidebar-link').forEach(link => link.classList.remove('active'));
    el.classList.add('active');
}



//////////////////////////////////
function renderEmails(emails) {
    const tbody = document.getElementById('emailBody');
    tbody.innerHTML = '';

    const avatarColors = [
        'bg-violet-100 text-violet-700',
        'bg-blue-100 text-blue-700',
        'bg-rose-100 text-rose-700',
        'bg-teal-100 text-teal-700',
        'bg-amber-100 text-amber-700',
        'bg-emerald-100 text-emerald-700',
    ];

    const chatsMap = {};

    emails.forEach(email => {
        if (!chatsMap[email.chat_id]) {
            chatsMap[email.chat_id] = email;
        }
    });

    const chats = Object.values(chatsMap);

    chats.forEach((email, index) => {
        const avatarClass = avatarColors[index % avatarColors.length];
        const initials = buildInitials(email.sender_name);

        const isSent = (email.sender_email === window.userEmail);
        const contactName = isSent ? (email.recipient_name || 'Recipient') : email.sender_name;
        const contactEmail = isSent ? (email.recipient_email || '') : email.sender_email;
        const previewPrefix = isSent ? 'You' : email.sender_name;

        const rowHtml = `
        <tr class="row-hover border-b border-slate-100 cursor-pointer hover:bg-slate-50"
            data-id="${email.id}"
            data-thread-id="${email.chat_id}"
            data-subject="${escapeHtml((email.subject || '').toLowerCase())}"
            data-from="${escapeHtml((contactName || '').toLowerCase())}"
            data-body="${escapeHtml((email.message || '').toLowerCase())}"
            onclick="handleRowClick(event, '${email.id}')">

            <td class="px-4 py-4 w-10" onclick="event.stopPropagation()">
                <input type="checkbox" class="check-row rounded" onchange="toggleRow('${email.id}', this)">
            </td>

            <td class="px-4 py-4 w-40">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold ${avatarClass}">
                        ${escapeHtml(initials)}
                    </div>
                    <div class="min-w-0">
                        <span class="font-medium text-slate-800 text-sm truncate">${escapeHtml(contactName)}</span>
                        <div class="text-xs text-slate-400 truncate">${escapeHtml(contactEmail)}</div>
                    </div>
                </div>
            </td>

            <td class="px-4 py-4 flex-1">
                <div class="font-medium text-sm truncate mb-1">${escapeHtml(email.subject)}</div>
                <div class="text-sm text-slate-600 truncate">
                    <span class="text-slate-400">${escapeHtml(previewPrefix)}: </span>
                    ${escapeHtml(renderPreview(email.message))}
                </div>
            </td>

            <td class="px-4 py-4 w-24">
                <span class="text-xs text-slate-400">${escapeHtml(email.sent_at)}</span>
            </td>
        </tr>
        `;

        tbody.insertAdjacentHTML('beforeend', rowHtml);
    });

    selectedRows.clear();
    updateFilteredView();
    updateBulkBar();
}
//////////////////////////////////



document.getElementById("composeForm").addEventListener("submit", function(e){
    e.preventDefault();

    let toEmail = document.getElementById('composeEmail').value.trim();
    let subject = document.getElementById('composeSubject').value.trim();
    let body = document.getElementById('composeBody').value.trim();

    if (!toEmail || !subject || !body) return;

    fetch("DB_Ops.php?action=add", {
        method: "POST",
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            composeEmail: toEmail,
            composeSubject: subject,
            composeBody: body
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.message !== "Email added") {
            alert(data.message);
            return;
        }
        // Reload the table to reflect the new email with correct names and colors
        fetch('DB_Ops.php?action=read')
        .then(res => res.json())
        .then(emails => {
            renderEmails(emails);
        })
        .catch(err => {
            console.error(err);
            alert("Error reloading messages");
        });

        document.getElementById("composeForm").reset();
        closeComposeModal();
    })
    .catch(console.error);
});


document.addEventListener('DOMContentLoaded', () => {
    updateFilteredView();
    updateBulkBar();

    // Delete selected messages
const deleteBtn = document.getElementById('deleteBtn');

if (deleteBtn) {
    deleteBtn.addEventListener('click', () => {

        if (selectedRows.size === 0) {
            alert('No messages selected');
            return;
        }

        const ids = Array.from(selectedRows).join(',');

        fetch(`DB_Ops.php?action=delete&id=${ids}`)
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            // Reload the table to reflect deletions
            fetch('DB_Ops.php?action=read')
            .then(res => res.json())
            .then(emails => {
                renderEmails(emails);
            })
            .catch(err => {
                console.error(err);
                alert("Error reloading messages");
            });
        })
        .catch(err => {
            console.error(err);
            alert("Error deleting messages");
        });
    });
}
    const dialog = document.getElementById('messageDialog');
    if (dialog) {
        dialog.addEventListener('click', event => {
            if (event.target === dialog) closeConversationModal();
        });
    }

    const composeDialog = document.getElementById('composeDialog');
    if (composeDialog) {
        composeDialog.addEventListener('click', event => {
            if (event.target === composeDialog) closeComposeModal();
        });
    }
});