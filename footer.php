<?php
// Footer partial converted from footer.html
?>
<div id="bulkBar" class="hidden items-center gap-3 px-8 py-3 border-t border-slate-200 bg-white flex-shrink-0">

<span id="bulkCount" class="text-sm text-slate-600 font-medium">0 selected</span>

<div class="flex items-center gap-2 ml-4">
     
        <button id="deleteBtn" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-red-100 text-xs font-medium text-red-500 hover:bg-red-50 transition-colors">
            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <polyline points="3 6 5 6 21 6"/>
                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                <path d="M10 11v6M14 11v6"/>
                <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
            </svg>
            Delete
        </button>
    </div>
    <!-- Clear current row selection -->
    <button onclick="clearSelection()" class="ml-auto text-xs text-slate-400 hover:text-slate-600 transition-colors">
        Clear selection
    </button>
</div>