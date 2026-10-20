<?php
require_once 'config/database.php';

echo "🔍 Verificando tablas...<br><br>";

$tables = ['users', 'teachers'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "✅ Tabla '$table' existe<br>";
    } else {
        echo "❌ Tabla '$table' NO existe<br>";
    }
}
?>