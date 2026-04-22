<?php
// Load dummy inbox data from JSON to keep data separate from UI markup.
$dataFile = __DIR__ . '/data/emails.json';
$emailsJson = @file_get_contents($dataFile);
$emails = json_decode($emailsJson ?: '[]', true);

if (!is_array($emails)) {
    $emails = [];
}
// DON'T REMOVE
// Status-to-badge style mapping for consistent label colors.
$statusStyles = [
    'New' => 'bg-blue-50 text-blue-600 border border-blue-200',
    'Open' => 'bg-amber-50 text-amber-600 border border-amber-200',
    'Replied' => 'bg-green-50 text-green-600 border border-green-200',
    'Won' => 'bg-teal-50 text-teal-600 border border-teal-200',
    'Pending' => 'bg-slate-100 text-slate-500 border border-slate-200',
];
//DON'T REMOVE
// Rotating avatar color presets to visually differentiate contacts.
$avatarColors = [
    'bg-violet-100 text-violet-700',
    'bg-blue-100 text-blue-700',
    'bg-rose-100 text-rose-700',
    'bg-teal-100 text-teal-700',
    'bg-amber-100 text-amber-700',
    'bg-emerald-100 text-emerald-700',
];
//////////////////////////////////////////////////////////////////////////////////////


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EmailManager</title>

    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindplus/elements@1" type="module"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="style.php">
</head>
<body class="bg-white text-slate-800 h-screen overflow-hidden">

<div class="flex h-screen">

    <!-- Left sidebar navigation -->
    <?php include __DIR__ . '/header.php'; ?>

    <!-- Main inbox content area -->
    <main class="flex-1 flex flex-col overflow-hidden bg-white">

        <!-- Page title and primary action -->
        <div class="flex items-center justify-between px-8 pt-6 pb-2 border-b border-slate-100 flex-shrink-0">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Messages</h1>
                <p class="text-sm text-slate-400 mt-0.5" id="conv-count"><?php echo count($emails); ?> messages</p>
            </div>
            <div class="flex items-center"></div>
        </div>

        <!-- Search and filter controls -->
        <div class="flex items-center gap-3 px-8 py-3 border-b border-slate-100 flex-shrink-0">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                </svg>
                <input type="text" id="searchInput" placeholder="Search conversations..."
                    class="rounded-lg border border-slate-200 pl-8 pr-4 py-2 text-sm text-slate-600 placeholder-slate-400 outline-none focus:border-blue-300 w-64 transition-colors"
                    oninput="filterTable()">
            </div>
            <select id="statusFilter" onchange="filterTable()"
                class="rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-600 outline-none focus:border-blue-300 bg-white cursor-pointer">
                <option value="">All Status</option>
                <option value="new">New</option>
                <option value="open">Open</option>
                <option value="replied">Replied</option>
                <option value="won">Won</option>
                <option value="pending">Pending</option>
            </select>
        
        </div>

        <!-- Scrollable conversation table -->
        <div class="flex-1 overflow-y-auto">
            <table class="w-full text-sm border-collapse" id="emailTable">
            <!-- Static table header -->
                <thead>
                    <tr class="border-b border-slate-100 bg-white sticky top-0 z-10">
                        <th class="w-10 px-4 py-3 text-left">
                            <input type="checkbox" class="check-row rounded" onchange="toggleAll(this)">
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider w-40">From</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider flex-1 w-0">Subject & Message</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider w-20">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider w-24">Time</th>
                    </tr>
                </thead>
                <tbody id="emailBody">
                    <!-- Render each individual message row from the PHP data array. -->
                    <?php foreach ($emails as $index => $message): ?>
                        <?php
                        // Fallbacks protect UI if a status key is missing.
                        $statusClass = $statusStyles[$message['status']] ?? 'bg-slate-100 text-slate-500 border border-slate-200';
                        $avatarClass = $avatarColors[$index % count($avatarColors)];
                        // Determine contact info based on message type (sent vs received)
                        $isSent = ($message['type'] ?? 'received') === 'sent';
                        $contactName = $isSent ? ($message['to'] ?? '') : ($message['from'] ?? '');
                        $contactEmail = $isSent ? ($message['toEmail'] ?? '') : ($message['fromEmail'] ?? '');
                        $contactInitials = $isSent ? ($message['toInitials'] ?? '') : ($message['fromInitials'] ?? '');
                        $contactLabel = $isSent ? 'To' : 'From';
                        ?>
                        <tr class="row-hover border-b border-slate-100 cursor-pointer transition-colors hover:bg-slate-50"
                            data-id="<?php echo htmlspecialchars($message['messageId'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-thread-id="<?php echo (int)$message['threadId']; ?>"
                            data-status="<?php echo strtolower(htmlspecialchars($message['status'], ENT_QUOTES, 'UTF-8')); ?>"
                            data-subject="<?php echo strtolower(htmlspecialchars($message['subject'], ENT_QUOTES, 'UTF-8')); ?>"
                            data-from="<?php echo strtolower(htmlspecialchars($contactName, ENT_QUOTES, 'UTF-8')); ?>"
                            data-body="<?php echo strtolower(htmlspecialchars($message['body'], ENT_QUOTES, 'UTF-8')); ?>"
                            onclick="handleRowClick(event, '<?php echo htmlspecialchars($message['messageId'], ENT_QUOTES, 'UTF-8'); ?>')">

                            <!-- Row select checkbox -->
                            <td class="px-4 py-4 w-10" onclick="event.stopPropagation()">
                                <input type="checkbox" class="check-row rounded" onchange="toggleRow('<?php echo htmlspecialchars($message['messageId'], ENT_QUOTES, 'UTF-8'); ?>', this)">
                            </td>

                            <!-- Sender/Recipient with avatar and label -->
                            <td class="px-4 py-4 w-40">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold flex-shrink-0 <?php echo $avatarClass; ?>">
                                        <?php echo htmlspecialchars($contactInitials, ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1">
                                            <span class="text-xs font-semibold text-slate-500 uppercase"><?php echo $contactLabel; ?></span>
                                            <span class="font-medium text-slate-800 text-sm truncate"><?php echo htmlspecialchars($contactName, ENT_QUOTES, 'UTF-8'); ?></span>
                                        </div>
                                        <div class="text-xs text-slate-400 truncate"><?php echo htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8'); ?></div>
                                    </div>
                                </div>
                            </td>

                            <!-- Subject and message body preview combined -->
                            <td class="px-4 py-4 flex-1">
                                <div class="font-medium text-slate-800 text-sm truncate mb-1"><?php echo htmlspecialchars($message['subject'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="text-xs text-slate-500 truncate"><?php echo htmlspecialchars((strlen($message['body']) > 60) ? (substr($message['body'], 0, 60) . '...') : $message['body'], ENT_QUOTES, 'UTF-8'); ?></div>
                            </td>

                            <!-- Message status badge -->
                            <td class="px-4 py-4 w-20">
                                <span class="inline-block rounded-md px-2.5 py-1 text-xs font-medium <?php echo $statusClass; ?>"><?php echo htmlspecialchars($message['status'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>

                            <!-- Time received -->
                            <td class="px-4 py-4 w-24">
                                <span class="text-xs text-slate-400"><?php echo htmlspecialchars($message['time'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Empty state shown when filters hide all rows -->
            <div id="emptyState" class="hidden flex-col items-center justify-center py-24 text-slate-400">
                <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" class="mb-3 opacity-40">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                    <polyline points="22,6 12,13 2,6"/>
                </svg>
                <p class="text-sm">No conversations match your filters.</p>
            </div>
        </div>

        <?php include __DIR__ . '/footer.php'; ?>

    </main>
</div>
//compose
<button type="button" onclick="openComposeModal()" class="fixed bottom-7 right-7 z-30 inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-2xl transition hover:-translate-y-0.5 hover:bg-slate-700" aria-label="Compose mail">
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
        <path d="M12 5v14"/>
        <path d="M5 12h14"/>
    </svg>
    Compose
</button>

//compose modal
<dialog id="composeDialog" aria-labelledby="composeDialogTitle" class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-slate-900/45 backdrop:backdrop-blur-sm">
    <div class="flex min-h-full items-end justify-end p-4 sm:items-end sm:justify-end sm:p-7">
        <div class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-4 py-3">
                <h3 id="composeDialogTitle" class="text-sm font-semibold text-slate-800">New Message</h3>
                <button type="button" onclick="closeComposeModal()" class="rounded-md border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-600 hover:bg-slate-100">Close</button>
            </div>

            <form id="composeForm" class="space-y-3 px-4 py-4" onsubmit="sendCompose(event)">
                
                <div>
                    <label for="composeEmail" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">To</label>
                    <input id="composeEmail" type="email" required placeholder="name@example.com" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 outline-none focus:border-slate-400">
                </div>

                <div>
                    <label for="composeSubject" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Subject</label>
                    <input id="composeSubject" type="text" required placeholder="Write subject" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 outline-none focus:border-slate-400">
                </div>

                <div>
                    <label for="composeBody" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Message</label>
                    <textarea id="composeBody" rows="6" required placeholder="Write your message..." class="w-full resize-none rounded-lg border border-slate-200 px-3 py-2 text-sm leading-6 text-slate-700 outline-none focus:border-slate-400"></textarea>
                </div>

                <div class="flex items-center justify-between pt-1">
                    <p class="text-xs text-slate-400">This sends as you and appears in inbox.</p>
                    <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Send</button>
                </div>
            </form>
        </div>
    </div>
</dialog>

<!-- Conversation modal with blurred backdrop -->
<dialog id="messageDialog" aria-labelledby="messageDialogTitle" class="fixed inset-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent backdrop:bg-slate-900/45 backdrop:backdrop-blur-sm">
    <div class="flex min-h-full items-end justify-center p-3 text-center sm:items-center sm:p-6">
        <div class="relative w-full max-w-5xl transform overflow-hidden rounded-2xl border border-slate-200 bg-white text-left shadow-2xl transition-all">
            <div class="border-b border-slate-200 bg-gradient-to-b from-slate-50 to-white px-6 py-5">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Conversation</p>
                        <h3 id="messageDialogTitle" class="mt-1 truncate text-xl font-semibold text-slate-900">Message</h3>
                        <p id="messageDialogMeta" class="mt-1 truncate text-sm text-slate-600"></p>
                        <p id="messageDialogSubMeta" class="mt-1 text-xs text-slate-400"></p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="document.getElementById('replyInput')?.focus()" class="rounded-md border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Reply</button>
                        <button type="button" onclick="closeConversationModal()" class="rounded-md bg-slate-900 px-3 py-1.5 text-sm font-medium text-white hover:bg-slate-700">Close</button>
                    </div>
                </div>
            </div>

            <div id="messageThread" class="max-h-[56vh] space-y-4 overflow-y-auto bg-slate-100/70 px-6 py-5"></div>

            <form id="replyForm" class="border-t border-slate-200 bg-white px-6 py-4" onsubmit="sendReply(event)">
                <label for="replyInput" class="mb-2 block text-sm font-medium text-slate-700">Reply</label>
                <textarea id="replyInput" rows="4" placeholder="Write your reply..." class="w-full resize-y rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-700 outline-none transition focus:border-slate-400 focus:bg-white"></textarea>
                <div class="mt-3 flex items-center justify-between">
                    <p class="text-xs text-slate-400">Your reply appears instantly in this thread.</p>
                    <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Send Reply</button>
                </div>
            </form>
        </div>
    </div>
</dialog>

<script>
window.emailData = <?php echo json_encode($emails, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>

<script src="app.php"></script>
</body>
</html>
