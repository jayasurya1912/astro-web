<?php
// Basic utility functions for JSON-based storage

function ensure_data_dir($dir)
{
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

function safe_read_json($path)
{
    if (!file_exists($path)) {
        return [];
    }
    $content = file_get_contents($path);
    if ($content === false || $content === '') {
        return [];
    }
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

function safe_write_json($path, $data)
{
    $tmp = $path . '.tmp';
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents($tmp, $json);
    rename($tmp, $path);
}

function json_response($payload, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function read_input_json()
{
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function method()
{
    return $_SERVER['REQUEST_METHOD'] ?? 'GET';
}

function allow_methods($allowed)
{
    $m = method();
    if (!in_array($m, $allowed)) {
        json_response(['error' => 'Method Not Allowed', 'allowed' => $allowed], 405);
    }
}

?>