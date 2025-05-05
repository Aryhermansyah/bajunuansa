<?php
/**
 * Script untuk memperbaiki masalah kueri SQLite pada database MySQL
 * 
 * Script ini akan mengidentifikasi kueri yang menggunakan sqlite_master dan menggantikannya
 * dengan kueri yang kompatibel dengan MySQL
 */

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Mulai sesi untuk pesan flash
session_start();

// Fungsi untuk memeriksa apakah tabel ada di database
function checkTableExists($tableName) {
    $db = Database::getInstance();
    $dbType = $db->getDbType();
    
    try {
        if ($dbType === 'mysql') {
            // MySQL: Gunakan SHOW TABLES
            $sql = "SHOW TABLES LIKE ?";
            $result = $db->fetchOne($sql, [$tableName]);
            return !empty($result);
        } else {
            // SQLite: Gunakan sqlite_master
            $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name=?";
            $result = $db->fetchOne($sql, [$tableName]);
            return !empty($result);
        }
    } catch (Exception $e) {
        echo "Error saat memeriksa tabel: " . $e->getMessage();
        return false;
    }
}

// Fungsi untuk mendapatkan daftar semua tabel di database
function getAllTables() {
    $db = Database::getInstance();
    $dbType = $db->getDbType();
    $tables = [];
    
    try {
        if ($dbType === 'mysql') {
            // MySQL: Gunakan SHOW TABLES
            $result = $db->fetchAll("SHOW TABLES");
            foreach ($result as $row) {
                $tables[] = current($row);
            }
        } else {
            // SQLite: Gunakan sqlite_master
            $result = $db->fetchAll("SELECT name FROM sqlite_master WHERE type='table'");
            foreach ($result as $row) {
                $tables[] = $row['name'];
            }
        }
    } catch (Exception $e) {
        echo "Error saat mengambil daftar tabel: " . $e->getMessage();
    }
    
    return $tables;
}

// Cek jenis database yang digunakan
$db = Database::getInstance();
$dbType = $db->getDbType();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perbaikan Kueri SQLite ke MySQL</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1, h2, h3 {
            color: #333;
        }
        code {
            background-color: #f5f5f5;
            padding: 2px 4px;
            border-radius: 4px;
            font-family: Consolas, Monaco, 'Andale Mono', monospace;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .success {
            color: green;
            background-color: #d4edda;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .error {
            color: #721c24;
            background-color: #f8d7da;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .warning {
            color: #856404;
            background-color: #fff3cd;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .files-list {
            list-style-type: none;
            padding: 0;
        }
        .files-list li {
            background-color: #f9f9f9;
            padding: 10px;
            margin-bottom: 5px;
            border-left: 3px solid #007bff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Perbaikan Kueri SQLite ke MySQL</h1>
        
        <div class="<?php echo $dbType === 'mysql' ? 'success' : 'warning'; ?>">
            Database yang digunakan: <strong><?php echo strtoupper($dbType); ?></strong>
        </div>
        
        <h2>Status Database</h2>
        
        <?php
        $tables = getAllTables();
        if (empty($tables)) {
            echo '<div class="error">Tidak ada tabel yang ditemukan di database!</div>';
        } else {
            echo '<h3>Tabel yang tersedia:</h3>';
            echo '<ul>';
            foreach ($tables as $table) {
                echo '<li>' . htmlspecialchars($table) . '</li>';
            }
            echo '</ul>';
        }
        ?>
        
        <h2>File yang Perlu Diperhatikan</h2>
        <p>Berikut adalah file yang perlu dimodifikasi untuk menggunakan kueri yang sesuai dengan MySQL:</p>
        
        <ul class="files-list">
            <li>
                <strong>admin/manage_sizes.php</strong> - Baris 13
                <pre><code>SELECT name FROM sqlite_master WHERE type='table' AND name='custom_sizes'</code></pre>
                Ganti dengan:
                <pre><code>SHOW TABLES LIKE 'custom_sizes'</code></pre>
                Atau gunakan fungsi <code>checkTableExists('custom_sizes')</code>
            </li>
            <li>
                <strong>admin/create_tables.php</strong> - Baris 10 & 58
                <pre><code>SELECT name FROM sqlite_master WHERE type='table' AND name='custom_sizes'</code></pre>
                <pre><code>SELECT name FROM sqlite_master WHERE type='table'</code></pre>
                Ganti dengan kueri MySQL yang sesuai seperti di atas.
            </li>
            <li>
                <strong>admin/add_product.php</strong> - Baris 31
                <pre><code>SELECT name FROM sqlite_master WHERE type='table' AND name='custom_sizes'</code></pre>
                Ganti dengan kueri MySQL yang sesuai atau gunakan fungsi helper.
            </li>
        </ul>
        
        <h2>Solusi</h2>
        <p>Untuk memperbaiki masalah ini, Anda perlu:</p>
        <ol>
            <li>Copy script <code>fix_sqlite_queries.php</code> ke server</li>
            <li>Modifikasi file-file di atas menggunakan fungsi helper dari script ini</li>
            <li>Atau gunakan kueri yang sesuai untuk MySQL dan SQLite berdasarkan tipe database</li>
        </ol>
        
        <h3>Contoh Kode Perbaikan:</h3>
        <pre><code>&lt;?php
// Fungsi untuk memeriksa apakah tabel ada di database
function checkTableExists($tableName) {
    $db = Database::getInstance();
    $dbType = $db->getDbType();
    
    try {
        if ($dbType === 'mysql') {
            // MySQL: Gunakan SHOW TABLES
            $sql = "SHOW TABLES LIKE ?";
            $result = $db->fetchOne($sql, [$tableName]);
            return !empty($result);
        } else {
            // SQLite: Gunakan sqlite_master
            $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name=?";
            $result = $db->fetchOne($sql, [$tableName]);
            return !empty($result);
        }
    } catch (Exception $e) {
        return false;
    }
}
?&gt;</code></pre>
        
        <h3>Penggunaan Dalam Kode:</h3>
        <pre><code>if (checkTableExists('custom_sizes')) {
    // Tabel ada
} else {
    // Tabel tidak ada
}</code></pre>
        
        <p><a href="../admin/view_products.php">Kembali ke Admin Panel</a></p>
    </div>
</body>
</html>
