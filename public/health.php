<?php
/**
 * 轻量级健康检查端点
 * 不加载 ThinkPHP 框架，直接测试 PHP 运行 + 数据库连通性
 * 用于 Docker HEALTHCHECK 和 Railway 部署健康探测
 */

// 超时保护：3 秒内必须返回
set_time_limit(3);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

$checks = [];
$healthy = true;

// 1. PHP 基本检查
$checks['php'] = 'ok';

// 2. 数据库连通性检查
$host = getenv('MYSQL_HOST') ?: '127.0.0.1';
$port = getenv('MYSQL_PORT') ?: '3306';
$db   = getenv('MYSQL_DATABASE') ?: '';
$user = getenv('MYSQL_USER') ?: '';
$pass = getenv('MYSQL_PASSWORD') ?: '';

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_TIMEOUT       => 2,
        PDO::ATTR_ERRMODE       => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
    ]);
    $pdo->query('SELECT 1');
    $checks['database'] = 'ok';
} catch (PDOException $e) {
    $checks['database'] = 'error';
    $checks['db_message'] = $e->getMessage();
    $healthy = false;
}

// 3. 运行时目录可写检查
$runtimeDir = __DIR__ . '/../runtime';
if (is_writable($runtimeDir)) {
    $checks['runtime'] = 'ok';
} else {
    $checks['runtime'] = 'error';
    $healthy = false;
}

// 输出结果
if ($healthy) {
    http_response_code(200);
    echo json_encode(['status' => 'ok', 'checks' => $checks]);
} else {
    http_response_code(503);
    echo json_encode(['status' => 'error', 'checks' => $checks]);
}
