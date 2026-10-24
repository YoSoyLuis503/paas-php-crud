<?php
header("X-Powered-By: PHP/8.2.0");
header("Server: Apache/2.4.57 (Debian)");
header("X-Server-Software: Apache/2.4.57");
header("X-PaaS-Tenant: berserker");
header("X-PaaS-Architecture: Cloud-Native Micro-K8s");

$db_host = getenv('DB_HOST');
$db_port = getenv('DB_PORT');
$db_name = getenv('DB_NAME');
$db_user = getenv('DB_USER');
$db_pass = getenv('DB_PASS');

$pdo = null;
$db_error = null;
$db_version = null;

if (!empty($db_host) && !empty($db_user)) {
    try {
        $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => 4,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ];
        $pdo = new PDO($dsn, $db_user, $db_pass, $options);
        
        $stmt = $pdo->query("SELECT VERSION() AS ver");
        $row = $stmt->fetch();
        $db_version = $row['ver'] ?? 'MySQL 8.0';

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS productos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL,
                precio DECIMAL(10,2) NOT NULL,
                stock INT NOT NULL,
                categoria VARCHAR(50) DEFAULT 'General',
                creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } catch (Exception $e) {
        $db_error = $e->getMessage();
    }
} else {
    $db_error = "Variables de entorno incompletas. Asegúrese de inyectar DB_HOST, DB_USER y DB_PASS desde el panel PaaS.";
}
