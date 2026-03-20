<?php
require 'config/database.php';
$pdo->exec("DELETE FROM exam_attempts WHERE user_id = (SELECT id FROM users WHERE username = 'student1')");
echo "Done\n";
?>
