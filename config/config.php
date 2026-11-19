<?php

date_default_timezone_set('Asia/Ho_Chi_Minh');

if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'FashionStore');
}

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)));
}

if (!defined('BASE_URL')) {
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']) ?: '') : '';
    $realRootPath = str_replace('\\', '/', realpath(ROOT_PATH) ?: ROOT_PATH);

    $baseUrl = '';
    if ($docRoot !== '' && stripos($realRootPath, $docRoot) === 0) {
        $relative = substr($realRootPath, strlen($docRoot));
        $baseUrl = '/' . trim(str_replace('\\', '/', $relative), '/');
        if ($baseUrl === '/') {
            $baseUrl = '';
        }
    } else {
        
        $baseUrl = '';
    }

    define('BASE_URL', $baseUrl);
}

if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', ROOT_PATH . '/public/uploads');
}

if (!defined('UPLOAD_URL')) {
    define('UPLOAD_URL', BASE_URL . '/public/uploads');
}
