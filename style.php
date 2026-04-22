<?php
header('Content-Type: text/css; charset=UTF-8');
?>
* { font-family: 'DM Sans', sans-serif; }
.mono { font-family: 'DM Mono', monospace; }

.row-hover:hover { background-color: #f8fafc; }
.row-hover:hover .row-actions { opacity: 1; }
.row-actions { opacity: 0; transition: opacity 0.15s; }

.sidebar-link { transition: background 0.12s, color 0.12s; }
.sidebar-link:hover { background: #f1f5f9; }
.sidebar-link.active { background: #eff6ff; color: #2563eb; }
.sidebar-link.active svg { color: #2563eb; }

.check-row { accent-color: #2563eb; }

tr.selected { background: #eff6ff !important; }
