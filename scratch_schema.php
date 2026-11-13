<?php
// 1.3 Guard central: solo web (las migraciones CLI siguen disponibles)
if (PHP_SAPI !== 'cli') {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
    auth_guard([1]);
}
$conn = mysqli_connect('localhost', 'root', '', 'ipsp');
if (!$conn) die('Error: ' . mysqli_connect_error());
$res = mysqli_query($conn, 'DESCRIBE contrato_plan');
while($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . ' - ' . $row['Type'] . PHP_EOL;
}
?>
