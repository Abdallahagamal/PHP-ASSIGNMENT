<?php
$conn = new mysqli('localhost','root','','email_db');
$q=$conn->query('DESCRIBE users');
while($r=$q->fetch_assoc()) {
    echo $r['Field'] . " (" . $r['Type'] . ")\n";
}
