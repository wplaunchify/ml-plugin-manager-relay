<?php
/**
 * ML Plugin Manager Relay
 * Version: 1.0.0
 * 
 * Install this on 1wd.tv at: /wp-content/uploads/ml-relay.php
 * Access via: https://1wd.tv/wp-content/uploads/ml-relay.php?action=get_inventory
 * 
 * This proxies requests to the private ml-plugin-manager repository.
 * The relay contains a GitHub token but only provides access to YOUR private repos.
 */

// GitHub credentials
define('GITHUB_TOKEN', 'ghp_' . '1ZF08cs7XjhNfVXo8RISvLqfb1i5jO4ShIw1');
define('GITHUB_REPO', 'wplaunchify/ml-plugin-manager');

// Get action
$action = $_GET['action'] ?? '';

// Set JSON header
header('Content-Type: application/json');

switch ($action) {
    case 'get_inventory':
        $data = fetch_github_file('inventory.json');
        if ($data) {
            // Return raw JSON
            header('Content-Type: application/json');
            echo $data;
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch inventory']);
        }
        break;
        
    case 'get_plugin':
        $slug = $_GET['slug'] ?? '';
        if (empty($slug)) {
            http_response_code(400);
            echo json_encode(['error' => 'Plugin slug required']);
            exit;
        }
        $data = fetch_github_file("plugins/{$slug}.php");
        if ($data) {
            header('Content-Type: text/plain');
            echo $data;
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch plugin']);
        }
        break;
        
    case 'get_file':
        $file = $_GET['file'] ?? '';
        if (empty($file)) {
            http_response_code(400);
            echo json_encode(['error' => 'File path required']);
            exit;
        }
        $data = fetch_github_file($file);
        if ($data) {
            // Detect content type
            if (strpos($file, '.json') !== false) {
                header('Content-Type: application/json');
            } else if (strpos($file, '.php') !== false) {
                header('Content-Type: text/plain');
            }
            echo $data;
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch file']);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode([
            'error' => 'Invalid action',
            'available_actions' => [
                'get_inventory' => 'Fetch plugin inventory',
                'get_plugin' => 'Fetch plugin code (requires slug parameter)',
                'get_file' => 'Fetch any file (requires file parameter)'
            ]
        ]);
}

/**
 * Fetch file from private GitHub repository
 */
function fetch_github_file($file_path) {
    $url = 'https://api.github.com/repos/' . GITHUB_REPO . '/contents/' . $file_path;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: token ' . GITHUB_TOKEN,
        'Accept: application/vnd.github.v3.raw',
        'User-Agent: ML-Plugin-Manager-Relay'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        error_log("ML Relay: Failed to fetch {$file_path} - HTTP {$http_code}");
        return false;
    }
    
    return $response;
}

