
<?php
// 1.3 Guard central: solo web (las migraciones CLI siguen disponibles)
if (PHP_SAPI !== 'cli') {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/IPSPUPTM/config/auth_guard.php';
    auth_guard([1]);
}
include 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
$r = mysqli_query($conn, 'DESCRIBE afiliados');
while($f = mysqli_fetch_assoc($r)) {
    print_r($f);
}
?>
