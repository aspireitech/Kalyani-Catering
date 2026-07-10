<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$q = trim($_GET['q'] ?? '');
if (mb_strlen($q) < 2) {
    json_response(['items' => []]);
}

$stmt = Database::get()->prepare(
    "SELECT id, name, slug, course_type, diet_type, unit_price, image_path
     FROM menu_items
     WHERE is_active = 1 AND (name LIKE ? OR description LIKE ? OR cuisine LIKE ?)
     ORDER BY sort_order LIMIT 8"
);
$like = '%' . $q . '%';
$stmt->execute([$like, $like, $like]);

$items = array_map(function ($row) {
    return [
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'slug' => $row['slug'],
        'course_label' => course_label($row['course_type']),
        'diet_type' => $row['diet_type'],
        'price' => $row['unit_price'] ? number_format((float)$row['unit_price'], 2) : null,
        'image' => $row['image_path'],
    ];
}, $stmt->fetchAll());

json_response(['items' => $items]);
