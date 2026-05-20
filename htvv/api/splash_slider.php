<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$db = new SQLite3('./.ansdb.db');
$db->exec("CREATE TABLE IF NOT EXISTS splash_slider (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    title TEXT DEFAULT '',
    image_url TEXT NOT NULL,
    sort_order INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
)");

$res = $db->query('SELECT id, title, image_url, sort_order, is_active FROM splash_slider WHERE is_active = 1 ORDER BY sort_order ASC, id ASC');
$items = [];
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $items[] = [
        'id' => (string)($row['id'] ?? 0),
        'title' => $row['title'] ?? '',
        'image_url' => $row['image_url'] ?? '',
        'sort_order' => (int)($row['sort_order'] ?? 0),
        'is_active' => (string)($row['is_active'] ?? 1)
    ];
}

echo json_encode([
    'success' => true,
    'total' => count($items),
    'splash_slider' => $items,
    'images' => $items
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
