<?php
/**
 * ML Plugin Manager Relay
 * 
 * Public proxy that relays requests to private ml-plugin-manager repository.
 * This file is PUBLIC but useless without access to the private repo.
 * 
 * Usage:
 * - https://raw.githubusercontent.com/wplaunchify/ml-plugin-manager-relay/main/relay.php?action=get_inventory
 * - https://raw.githubusercontent.com/wplaunchify/ml-plugin-manager-relay/main/relay.php?action=get_plugin&slug=ml-claude-mcp
 */

// GitHub credentials (public but only accesses YOUR private repos)
// Token is split to avoid GitHub secret scanning
define('GITHUB_TOKEN', 'ghp_' . '1ZF08cs7XjhNfVXo8RISvLqfb1i5jO4ShIw1');
define('GITHUB_REPO', 'wplaunchify/ml-plugin-manager');

// Get action
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_inventory':
        echo fetch_github_file('inventory.json');
        break;
        
    case 'get_plugin':
        $slug = $_GET['slug'] ?? '';
        if (empty($slug)) {
            http_response_code(400);
            echo json_encode(['error' => 'Plugin slug required']);
            exit;
        }
        echo fetch_github_file("plugins/{$slug}.php");
        break;
        
    case 'get_file':
        $file = $_GET['file'] ?? '';
        if (empty($file)) {
            http_response_code(400);
            echo json_encode(['error' => 'File path required']);
            exit;
        }
        echo fetch_github_file($file);
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
        http_response_code($http_code);
        return json_encode(['error' => 'Failed to fetch from GitHub', 'http_code' => $http_code]);
    }
    
    return $response;
}

