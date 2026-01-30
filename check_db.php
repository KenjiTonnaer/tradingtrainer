<?php
$db = new PDO('sqlite:database/database.sqlite');
$tables = $db->query('SELECT name FROM sqlite_master WHERE type="table"');
echo "Tables:\n";
foreach ($tables as $table) {
    echo "- " . $table['name'] . "\n";
}

echo "\nUsers:\n";
$users = $db->query('SELECT id, email, name FROM users LIMIT 10');
foreach ($users as $user) {
    echo $user['email'] . " (" . $user['name'] . ")\n";
}
