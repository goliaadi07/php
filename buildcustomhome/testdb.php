<?php
require_once __DIR__ . '/db.php';
try {
    $row = $pdo->query("SELECT DATABASE() AS db")->fetch();
    $count = $pdo->query("SELECT COUNT(*) as cnt FROM submissions")->fetch();
    echo "<pre>Connected DB: " . htmlspecialchars($row['db']) . "\nRows in submissions: " . (int)$count['cnt'] . "</pre>";
} catch (Exception $e) {
    echo "<pre>DB error: " . htmlspecialchars($e->getMessage()) . "</pre>";
}
