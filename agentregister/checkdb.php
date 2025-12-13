<?php
require_once __DIR__ . '/db.php';
header('Content-Type: text/plain; charset=utf-8');

echo "Connected OK\n\n";

// show current DB
try {
    $cur = $pdo->query("SELECT DATABASE()")->fetchColumn();
    echo "CURRENT DATABASE: " . ($cur ?: '[none]') . "\n";
} catch (Exception $e) {
    echo "ERROR reading current DB: " . $e->getMessage() . "\n";
}

// show last inserted id in table (highest id)
try {
    $r = $pdo->query("SELECT MAX(id) AS mx FROM user_requests")->fetch();
    echo "MAX(id) in user_requests: " . ($r['mx'] ?? 'NULL') . "\n\n";
} catch (Exception $e) {
    echo "ERROR SELECT MAX(id): " . $e->getMessage() . "\n\n";
}

// // print last 5 rows summary
// try {
//     $stmt = $pdo->query("SELECT id, first_name, last_name, email, last_completed_step, created_at FROM form_submissions ORDER BY id DESC LIMIT 5");
//     $rows = $stmt->fetchAll();
//     if (!$rows) { echo "No rows found in form_submissions\n"; }
//     foreach ($rows as $row) {
//         echo "id={$row['id']} | {$row['first_name']} {$row['last_name']} | {$row['email']} | step={$row['last_completed_step']} | created={$row['created_at']}\n";
//     }
// } catch (Exception $e) {
//     echo "ERROR selecting rows: ".$e->getMessage()."\n";
// }
