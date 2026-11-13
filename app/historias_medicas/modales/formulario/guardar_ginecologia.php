<?php
require 'C:/xampp/htdocs/IPSPUPTM/config/database.php';
include 'C:/xampp/htdocs/IPSPUPTM/app/configuracion/bitacora/bitacora.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit();
}

// --- Captura de datos obligatorios ---
$ci_medico = intval($_POST['ci_medico'] ?? 0);
$tipo_paciente = $_POST['tipo_paciente'] ?? null;
$motivo = $_POST['motivo_consulta'] ?? null;

// Determine ci_paciente based on patient type
$ci_paciente_raw = ($_POST['tipo_paciente'] === 'interno')
    ? ($_POST['ci_paciente'] ?? null)
    : ($_POST['cedula_ext'] ?? null);

if (!$ci_paciente_raw || !$tipo_paciente || !$ci_medico || !$motivo) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios (paciente, médico o motivo).']);
    exit();
}

try {
    $ci_paciente = intval(preg_replace('/[^0-9]/', '', $ci_paciente_raw));

    // Auto-detectar tipo real de paciente interno
    if ($tipo_paciente === 'interno') {
        $chk = $conn->prepare("SELECT cedula FROM afiliados WHERE cedula = ?");
        $chk->bind_param("i", $ci_paciente);
        $chk->execute();
        $tipo_paciente = ($chk->get_result()->num_rows > 0) ? 'afiliado' : 'beneficiario';
        $chk->close();
    }

    // --- Todos los campos de la tabla historias_medicas_gine ---
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
    $edad = intval($_POST['edad'] ?? 0);
    $direccion = htmlspecialchars($_POST['direccion'] ?? '');
    $motivo_consulta = htmlspecialchars($_POST['motivo_consulta'] ?? '');
    $enfermedad_actual = htmlspecialchars($_POST['enfermedad_actual'] ?? '');
    $ant_fam = htmlspecialchars($_POST['antecedentes_familiares'] ?? '');
    $ant_per = htmlspecialchars($_POST['antecedentes_personales'] ?? '');
    $gs = htmlspecialchars($_POST['gs'] ?? '');
    $fuma = htmlspecialchars($_POST['fuma'] ?? '');
    $ant_gineco = htmlspecialchars($_POST['ant_gineco_obstetrico'] ?? '');
    $cm = htmlspecialchars($_POST['cm'] ?? '');
    $prs = htmlspecialchars($_POST['prs'] ?? '');
    $cs = htmlspecialchars($_POST['cs'] ?? '');
    $mac = htmlspecialchars($_POST['mac'] ?? '');
    $fuc = htmlspecialchars($_POST['fuc'] ?? '');
    $fum = htmlspecialchars($_POST['fum'] ?? '');
    $gestaciones = htmlspecialchars($_POST['gestaciones'] ?? '');
    $rc = htmlspecialchars($_POST['rc'] ?? '');
    $anio = intval($_POST['año'] ?? date('Y'));
    $otros = htmlspecialchars($_POST['otros'] ?? '');
    $ex_fisico_ta = htmlspecialchars($_POST['ex_fisico_ta'] ?? '');
    $fc = htmlspecialchars($_POST['fc'] ?? '');
    $peso = htmlspecialchars($_POST['peso'] ?? '');
    $talla = htmlspecialchars($_POST['talla'] ?? '');
    $cabeza = htmlspecialchars($_POST['cabeza'] ?? '');
    $orl = htmlspecialchars($_POST['orl'] ?? '');
    $cv = htmlspecialchars($_POST['cv'] ?? '');
    $tiroides = htmlspecialchars($_POST['tiroides'] ?? '');
    $mamas = htmlspecialchars($_POST['mamas'] ?? '');
    $abdomen = htmlspecialchars($_POST['abdomen'] ?? '');
    $ginecologico = htmlspecialchars($_POST['ginecologico'] ?? '');
    $ultrasonido = htmlspecialchars($_POST['ultrasonido'] ?? '');
    $diagnostico = htmlspecialchars($_POST['diagnostico'] ?? '');
    $conducta = htmlspecialchars($_POST['conducta'] ?? '');

    $sql = "INSERT INTO historias_medicas_gine (
                ci_paciente, tipo_paciente, ci_medico, fecha, fecha_nacimiento, edad, direccion,
                motivo_consulta, enfermedad_actual, antecedentes_familiares, antecedentes_personales,
                gs, fuma, ant_gineco_obstetrico, `c.m`, prs, cs, mac, fuc, fum, gestaciones, rc,
                `año`, otros, `ex.fisico.t.a`, `f.c`, peso, talla, cabeza, `o.r.l`, `c.v`,
                tiroides, mamas, abdomen, ginecologico, ultrasonido, diagnostico, conducta
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?
            )";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Error preparando consulta: " . $conn->error);
    }

    $stmt->bind_param(
        "isissis" . // 7: ci_paciente(i), tipo(s), ci_medico(i), fecha(s), fecha_nac(s), edad(i), direccion(s)
        "ssss" . // 4: motivo, enfermedad, ant_fam, ant_per
        "sssssssssss" . // 11: gs, fuma, ant_gineco, cm, prs, cs, mac, fuc, fum, gestaciones, rc
        "isssssssss" . // 10: anio(i), otros, ex_fisico_ta, fc, peso, talla, cabeza, orl, cv, tiroides
        "ssssss", // 6: mamas, abdomen, ginecologico, ultrasonido, diagnostico, conducta
        $ci_paciente, $tipo_paciente, $ci_medico, $fecha, $fecha_nacimiento, $edad, $direccion,
        $motivo_consulta, $enfermedad_actual, $ant_fam, $ant_per,
        $gs, $fuma, $ant_gineco, $cm, $prs, $cs, $mac, $fuc, $fum, $gestaciones, $rc,
        $anio, $otros, $ex_fisico_ta, $fc, $peso, $talla, $cabeza, $orl, $cv, $tiroides,
        $mamas, $abdomen, $ginecologico, $ultrasonido, $diagnostico, $conducta
    );

    if ($stmt->execute()) {
        $usuario = $_SESSION['username'] ?? 'Sistema';
        registrarenBitacora($conn, $usuario, "Historia Ginecología", "Paciente CI: $ci_paciente ($tipo_paciente)");
        echo json_encode(['success' => true, 'message' => 'Historia de Ginecología guardada correctamente.']);
    }
    else {
        throw new Exception("Error en BD: " . $stmt->error);
    }

}
catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
