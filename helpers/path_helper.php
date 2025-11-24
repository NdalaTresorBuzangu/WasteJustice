<?php
/**
 * Path Helper Functions
 * Provides functions to generate correct paths in MVC structure
 */

if (!defined('BASE_DIR')) {
    define('BASE_DIR', dirname(dirname(__DIR__)));
}

// Base paths (from root)
function base_path($path = '') {
    return BASE_DIR . ($path ? '/' . ltrim($path, '/') : '');
}

// URL paths (for links)
function base_url($path = '') {
    $base = defined('BASE_URL') ? BASE_URL : '/WasteJustice';
    return $base . ($path ? '/' . ltrim($path, '/') : '');
}

// View paths
function view_path($view) {
    return base_path('views/' . $view);
}

function view_url($view) {
    return base_url('views/' . $view);
}

// Asset paths
function asset_path($asset) {
    return base_path('assets/' . $asset);
}

function asset_url($asset) {
    return base_url('assets/' . $asset);
}

// Action paths
function action_path($action) {
    return base_path('actions/' . $action);
}

function action_url($action) {
    return base_url('actions/' . $action);
}

// Controller paths
function controller_path($controller) {
    return base_path('controllers/' . $controller);
}

// Model paths
function model_path($model) {
    return base_path('classes/' . $model);
}

// Config paths
function config_path($config = 'config.php') {
    return base_path('config/' . $config);
}

// Redirect helper
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Include view
function include_view($view, $data = []) {
    extract($data);
    include view_path($view);
}

