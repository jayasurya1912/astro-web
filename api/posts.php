<?php
require_once __DIR__ . '/utils.php';

// Simple file-based storage for blog posts
$dataDir = __DIR__ . '/../data';
$postsFile = $dataDir . '/posts.json';
ensure_data_dir($dataDir);

// Initialize storage file if missing
if (!file_exists($postsFile)) {
    safe_write_json($postsFile, []);
}

$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        // Optional filter by status (e.g., published)
        $status = $_GET['status'] ?? null;
        $posts = safe_read_json($postsFile);
        if ($status) {
            $posts = array_values(array_filter($posts, function ($p) use ($status) {
                return isset($p['status']) && $p['status'] === $status;
            }));
        }
        // Sort by date desc
        usort($posts, function ($a, $b) {
            return strtotime($b['date'] ?? '0') <=> strtotime($a['date'] ?? '0');
        });
        json_response(['ok' => true, 'data' => $posts]);
        break;

    case 'get':
        $id = $_GET['id'] ?? null;
        if (!$id) json_response(['ok' => false, 'error' => 'Missing id'], 400);
        $posts = safe_read_json($postsFile);
        foreach ($posts as $p) {
            if ((string)$p['id'] === (string)$id) {
                json_response(['ok' => true, 'data' => $p]);
            }
        }
        json_response(['ok' => false, 'error' => 'Not found'], 404);
        break;

    case 'create':
        allow_methods(['POST']);
        $input = read_input_json();
        if (empty($input)) {
            // fallback to form-urlencoded
            $input = $_POST;
        }
        $required = ['title', 'excerpt', 'content', 'category', 'tags', 'status'];
        foreach ($required as $r) {
            if (!isset($input[$r])) $input[$r] = '';
        }
        $posts = safe_read_json($postsFile);
        $post = [
            'id' => round(microtime(true) * 1000),
            'title' => trim($input['title'] ?? ''),
            'excerpt' => trim($input['excerpt'] ?? ''),
            'content' => trim($input['content'] ?? ''),
            'category' => (string)($input['category'] ?? ''),
            'tags' => trim($input['tags'] ?? ''),
            'status' => trim($input['status'] ?? 'draft'),
            'image' => trim($input['image'] ?? ''),
            'date' => $input['date'] ?? date('c')
        ];
        array_unshift($posts, $post);
        safe_write_json($postsFile, $posts);
        json_response(['ok' => true, 'data' => $post]);
        break;

    case 'update':
        allow_methods(['POST']);
        $id = $_GET['id'] ?? null;
        if (!$id) json_response(['ok' => false, 'error' => 'Missing id'], 400);
        $input = read_input_json();
        if (empty($input)) $input = $_POST;
        $posts = safe_read_json($postsFile);
        $updated = null;
        foreach ($posts as $idx => $p) {
            if ((string)$p['id'] === (string)$id) {
                $posts[$idx] = array_merge($p, [
                    'title' => trim($input['title'] ?? $p['title'] ?? ''),
                    'excerpt' => trim($input['excerpt'] ?? $p['excerpt'] ?? ''),
                    'content' => trim($input['content'] ?? $p['content'] ?? ''),
                    'category' => (string)($input['category'] ?? $p['category'] ?? ''),
                    'tags' => trim($input['tags'] ?? $p['tags'] ?? ''),
                    'status' => trim($input['status'] ?? $p['status'] ?? 'draft'),
                    'image' => trim($input['image'] ?? $p['image'] ?? '')
                ]);
                $updated = $posts[$idx];
                break;
            }
        }
        if (!$updated) json_response(['ok' => false, 'error' => 'Not found'], 404);
        safe_write_json($postsFile, $posts);
        json_response(['ok' => true, 'data' => $updated]);
        break;

    case 'delete':
        allow_methods(['POST']);
        $id = $_GET['id'] ?? null;
        if (!$id) json_response(['ok' => false, 'error' => 'Missing id'], 400);
        $posts = safe_read_json($postsFile);
        $before = count($posts);
        $posts = array_values(array_filter($posts, function ($p) use ($id) {
            return (string)$p['id'] !== (string)$id;
        }));
        if (count($posts) === $before) json_response(['ok' => false, 'error' => 'Not found'], 404);
        safe_write_json($postsFile, $posts);
        json_response(['ok' => true]);
        break;

    default:
        json_response(['ok' => false, 'error' => 'Unknown action'], 400);
}

?>