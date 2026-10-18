<?php

try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3307', 'root', '');
    $pdo->exec('CREATE DATABASE IF NOT EXISTS zyro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    echo 'MYSQL_OK';
} catch (Throwable $e) {
    echo 'MYSQL_FAIL: '.$e->getMessage();
}
