<?php
require_once __DIR__ . '/utils.php';

$dataDir = __DIR__ . '/../data';
$categoriesFile = $dataDir . '/categories.json';
ensure_data_dir($dataDir);

// Initialize with defaults if missing
if (!file_exists($categoriesFile)) {
    $defaults = [
        ['id' => 1, 'name' => 'Nadi Astrology Basics', 'slug' => 'nadi-astrology-basics', 'postCount' => 0],
        ['id' => 2, 'name' => 'Kandam Predictions', 'slug' => 'kandam-predictions', 'postCount' => 0],
        ['id' => 3, 'name' => 'Astrological Remedies', 'slug' => 'astrological-remedies', 'postCount' => 0]
    ];
    safe_write_json($categoriesFile, $defaults);
}

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        $categories = safe_read_json($categoriesFile);
        json_response(['ok' => true, 'data' => $categories]);
        break;

    case 'create':
        allow_methods(['POST']);
        $input = read_input_json();
        if (empty($input)) $input = $_POST;
        $categories = safe_read_json($categoriesFile);
        $cat = [
            'id' => round(microtime(true) * 1000),
            'name' => trim($input['name'] ?? ''),
            'slug' => trim($input['slug'] ?? ''),
            'postCount' => 0
        ];
        array_unshift($categories, $cat);
        safe_write_json($categoriesFile, $categories);
        json_response(['ok' => true, 'data' => $cat]);
        break;

    case 'update':
        allow_methods(['POST']);
        $id = $_GET['id'] ?? null;
        if (!$id) json_response(['ok' => false, 'error' => 'Missing id'], 400);
        $input = read_input_json();
        if (empty($input)) $input = $_POST;
        $categories = safe_read_json($categoriesFile);
        $updated = null;
        foreach ($categories as $idx => $c) {
            if ((string)$c['id'] === (string)$id) {
                $categories[$idx] = array_merge($c, [
                    'name' => trim($input['name'] ?? $c['name'] ?? ''),
                    'slug' => trim($input['slug'] ?? $c['slug'] ?? ''),
                ]);
                $updated = $categories[$idx];
                break;
            }
        }
        if (!$updated) json_response(['ok' => false, 'error' => 'Not found'], 404);
        safe_write_json($categoriesFile, $categories);
        json_response(['ok' => true, 'data' => $updated]);
        break;

    case 'delete':
        allow_methods(['POST']);
        $id = $_GET['id'] ?? null;
        if (!$id) json_response(['ok' => false, 'error' => 'Missing id'], 400);
        $categories = safe_read_json($categoriesFile);
        $before = count($categories);
        $categories = array_values(array_filter($categories, function ($c) use ($id) {
            return (string)$c['id'] !== (string)$id;
        }));
        if (count($categories) === $before) json_response(['ok' => false, 'error' => 'Not found'], 404);
        safe_write_json($categoriesFile, $categories);
        json_response(['ok' => true]);
        break;

    default:
        json_response(['ok' => false, 'error' => 'Unknown action'], 400);
}

?>