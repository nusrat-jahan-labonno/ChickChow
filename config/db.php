<?php
// Database connection (PDO).
// - Local (XAMPP): works as it is (localhost / root / no password / database "chickchow").
// - Online (Vercel): set these Environment Variables in the Vercel dashboard
//   DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS  (and DB_SSL = 1 for TiDB Cloud / most cloud MySQL).
// No password is stored in the code, so it is safe to upload this file to GitHub.
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$db   = getenv('DB_NAME') ?: 'chickchow';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS');
if ($pass === false) $pass = '';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
if (getenv('DB_SSL')) {                       // secure connection for cloud databases
    $options[PDO::MYSQL_ATTR_SSL_CA] = __DIR__ . '/cacert.pem';
    if (getenv('DB_SSL_VERIFY') === '0') {
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }
}

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    if (getenv('DB_HOST')) {                  // cloud database: use Bangladesh time
        $pdo->exec("SET time_zone = '+06:00'");
    }
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage() . '<br>Check that MySQL is running and the database has been imported.');
}
