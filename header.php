<?php
// Sidebar partial converted from header.html
?>
<aside class="w-64 flex-shrink-0 border-r border-slate-200 flex flex-col h-full bg-white">

    <!-- Sidebar brand -->
    <div class="flex items-center px-5 py-4 border-b border-slate-100">
        <img src="logo.png" alt="EmailManager logo" class="h-8 w-auto object-contain" loading="eager" decoding="async">
    </div>


    <!-- Main navigation links -->
    <nav class="flex-1 px-3 space-y-0.5">
        <a href="#" class="sidebar-link active flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-blue-600" onclick="setActive(this)">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                <polyline points="22,6 12,13 2,6"/>
            </svg>
            Inbox
            <span class="ml-auto bg-blue-600 text-white text-[10px] font-semibold mono px-1.5 py-0.5 rounded-full">12</span>
        </a>

    </nav>

    <!-- Current user profile footer -->
    <div class="flex items-center gap-3 px-4 py-3 border-t border-slate-100">
        <div class="w-8 h-8 rounded-full bg-slate-700 flex items-center justify-center text-white text-xs font-semibold flex-shrink-0">JD</div>
        <div class="flex-1 min-w-0">
            <div class="text-sm font-medium text-slate-800">John Doe</div>
        </div>
        
    </div>

</aside>
