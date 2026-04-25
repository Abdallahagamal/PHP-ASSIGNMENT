<?php
$conn = new mysqli("sql113.infinityfree.com","if0_41747456","ryH6NrJNuBGHCR","if0_41747456_email_db");
$q=$conn->query('DESCRIBE users');
while($r=$q->fetch_assoc()) {
    echo $r['Field'] . " (" . $r['Type'] . ")\n";
}
