<?php
/**
 * config/plan_helper.php
 *
 * Reglas centrales del plan de salud anual:
 *   - Periodo de inscripción: 1 de octubre a 31 de diciembre del año anterior.
 *   - Contrato anual con pago inicial del 30 % y cuotas mensuales sobre el 70 %.
 *   - Mora: se toleran 2 cuotas vencidas; con la tercera se bloquea la cobertura.
 *   - Cobertura familiar de $800 por año, no acumulable.
 *   - Vigencia por fechas: el contrato vence solo al terminar el año del plan.
 *
 * La cédula sigue siendo el dato de entrada en la interfaz; estas funciones
 * trabajan siempre con id_persona.
 */

if (!function_exists('planHoy')) {

    define('PLAN_PORCENTAJE_INICIAL', 0.30);
    define('PLAN_CUOTAS_ANUALES', 12);
    define('PLAN_MAX_CUOTAS_VENCIDAS', 2);
    define('PLAN_DIA_PAGO_DEFECTO', 5);
    define('PLAN_ZONA_HORARIA', 'America/Caracas');

    /**
     * Fecha de hoy en la zona horaria del sistema (Y-m-d).
     */
    function planHoy(): string
    {
        $ahora = new DateTimeImmutable('now', new DateTimeZone(PLAN_ZONA_HORARIA));
        return $ahora->format('Y-m-d');
    }

    /**
     * Indica si hoy estamos dentro del periodo de inscripción (octubre a diciembre).
     */
    function planPeriodoInscripcionAbierto(?string $fecha = null): bool
    {
        $fecha = $fecha ?: planHoy();
        $mes = (int) substr($fecha, 5, 2);
        return $mes >= 10 && $mes <= 12;
    }

    /**
     * Texto fijo del periodo de inscripción.
     */
    function planPeriodoInscripcionTexto(): string
    {
        return '1 de octubre al 31 de diciembre';
    }

    /**
     * Año del plan al que corresponde inscribirse hoy.
     * De octubre a diciembre se inscribe para el año siguiente.
     */
    function planAnioObjetivo(?string $fecha = null): int
    {
        $fecha = $fecha ?: planHoy();
        $anio = (int) substr($fecha, 0, 4);
        return planPeriodoInscripcionAbierto($fecha) ? $anio + 1 : $anio;
    }

    /**
     * Plan activo para inscribirse. Solo debe existir uno a la vez.
     */
    function planObtenerActivo(mysqli $conn): ?array
    {
        $sql = "SELECT * FROM planes WHERE activo = 1 ORDER BY anio DESC, ID_planes DESC LIMIT 1";
        $resultado = $conn->query($sql);
        if (!$resultado) {
            return null;
        }
        $fila = $resultado->fetch_assoc();
        return $fila ?: null;
    }

    /**
     * Plan por ID, sin importar si está activo.
     */
    function planObtenerPorId(mysqli $conn, int $idPlan): ?array
    {
        $stmt = $conn->prepare("SELECT * FROM planes WHERE ID_planes = ? LIMIT 1");
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $idPlan);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $fila ?: null;
    }

    /**
     * Plan activo de un año específico.
     */
    function planObtenerActivoPorAnio(mysqli $conn, int $anio): ?array
    {
        $stmt = $conn->prepare("SELECT * FROM planes WHERE activo = 1 AND anio = ? LIMIT 1");
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $anio);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $fila ?: null;
    }

    /**
     * Monto del pago inicial (30 % del precio del plan).
     */
    function planMontoInicial(float $montoTotal): float
    {
        return round($montoTotal * PLAN_PORCENTAJE_INICIAL, 2);
    }

    /**
     * Monto restante que se reparte en las cuotas (70 %).
     */
    function planMontoRestante(float $montoTotal): float
    {
        return round($montoTotal - planMontoInicial($montoTotal), 2);
    }

    /**
     * Días que tiene un mes.
     */
    function planDiasDelMes(int $anio, int $mes): int
    {
        return (int) (new DateTimeImmutable(sprintf('%04d-%02d-01', $anio, $mes)))->format('t');
    }

    /**
     * Fecha de vencimiento de una cuota, ajustando el día al último del mes.
     */
    function planFechaVencimiento(int $anio, int $mes, int $diaPago): string
    {
        $dia = max(1, min($diaPago, planDiasDelMes($anio, $mes)));
        return sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
    }

    /**
     * Meses que cubren las cuotas de un contrato.
     *
     * - Contrato anual normal (inicio 1 de enero): enero a diciembre.
     * - Inscripción excepcional a mitad de año: desde el mes siguiente hasta diciembre.
     */
    function planMesesCuotas(int $anioPlan, ?string $fechaInicio = null): array
    {
        $primeroDelAnio = sprintf('%04d-01-01', $anioPlan);

        if ($fechaInicio === null || $fechaInicio <= $primeroDelAnio) {
            return range(1, PLAN_CUOTAS_ANUALES);
        }

        $mesInscripcion = (int) substr($fechaInicio, 5, 2);
        if ($mesInscripcion >= 12) {
            throw new Exception('No quedan meses del año para generar cuotas.');
        }

        return range($mesInscripcion + 1, 12);
    }

    /**
     * Reparte el 70 % restante en cuotas iguales, ajustando la última
     * para que la suma coincida exactamente con el monto del contrato.
     *
     * @return float[]
     */
    function planMontosCuotas(float $montoTotal, int $numCuotas): array
    {
        if ($numCuotas <= 0) {
            throw new Exception('El contrato debe tener al menos una cuota.');
        }

        $restante = planMontoRestante($montoTotal);
        $base = round($restante / $numCuotas, 2);

        $montos = array_fill(0, $numCuotas, $base);
        $diferencia = round($restante - ($base * $numCuotas), 2);
        $montos[$numCuotas - 1] = round($base + $diferencia, 2);

        return $montos;
    }

    /**
     * Resumen de montos que verá el usuario antes de guardar:
     * inicial, cantidad de cuotas, cuota estimada y total.
     */
    function planResumenMontos(float $montoTotal, int $numCuotas): array
    {
        $montos = planMontosCuotas($montoTotal, $numCuotas);
        return [
            'monto_total' => round($montoTotal, 2),
            'monto_inicial' => planMontoInicial($montoTotal),
            'monto_restante' => planMontoRestante($montoTotal),
            'num_cuotas' => $numCuotas,
            'monto_cuota' => $montos[0] ?? 0.0,
            'cuotas' => $montos,
        ];
    }

    /**
     * Titular del contrato familiar al que pertenece una persona.
     * Si es afiliado devuelve su propio id_persona; si es beneficiario,
     * devuelve el id_persona del afiliado titular.
     */
    function planPersonaTitular(mysqli $conn, int $idPersona): ?int
    {
        $stmt = $conn->prepare("SELECT id_persona FROM afiliados WHERE id_persona = ? LIMIT 1");
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $idPersona);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($fila) {
            return (int) $fila['id_persona'];
        }

        $stmt = $conn->prepare(
            "SELECT a.id_persona
             FROM beneficiarios b
             JOIN afiliados a ON a.ID = b.id_afiliado
             WHERE b.id_persona = ?
             LIMIT 1"
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $idPersona);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $fila ? (int) $fila['id_persona'] : null;
    }

    /**
     * Contrato de una persona para un año específico.
     */
    function contratoPorAnio(mysqli $conn, int $idPersona, int $anio): ?array
    {
        $stmt = $conn->prepare(
            "SELECT * FROM contrato_plan
             WHERE id_persona = ? AND anio = ?
             ORDER BY ID_contrato DESC LIMIT 1"
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('ii', $idPersona, $anio);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $fila ?: null;
    }

    /**
     * Contrato cuya vigencia por fechas cubre la fecha indicada.
     */
    function contratoVigente(mysqli $conn, int $idPersona, ?string $fecha = null): ?array
    {
        $fecha = $fecha ?: planHoy();
        $stmt = $conn->prepare(
            "SELECT * FROM contrato_plan
             WHERE id_persona = ?
               AND fecha_inicio <= ?
               AND fecha_fin >= ?
               AND estado_contrato <> 'Inactivo'
             ORDER BY anio DESC, ID_contrato DESC
             LIMIT 1"
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('iss', $idPersona, $fecha, $fecha);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $fila ?: null;
    }

    /**
     * Total pagado del 30 % inicial.
     */
    function contratoInicialPagado(mysqli $conn, int $idContrato): float
    {
        $stmt = $conn->prepare(
            "SELECT COALESCE(SUM(monto_cuota), 0) AS total
             FROM pagos_contrato
             WHERE ID_contrato = ? AND tipo_pago = 'Pago Inicial'"
        );
        if (!$stmt) {
            return 0.0;
        }
        $stmt->bind_param('i', $idContrato);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (float) ($fila['total'] ?? 0);
    }

    /**
     * Cuotas del contrato ordenadas por vencimiento.
     */
    function contratoCuotas(mysqli $conn, int $idContrato): array
    {
        $cuotas = [];
        $stmt = $conn->prepare(
            "SELECT * FROM contrato_cuotas
             WHERE id_contrato = ?
             ORDER BY numero_cuota ASC"
        );
        if (!$stmt) {
            return $cuotas;
        }
        $stmt->bind_param('i', $idContrato);
        $stmt->execute();
        $resultado = $stmt->get_result();
        while ($fila = $resultado->fetch_assoc()) {
            $cuotas[] = $fila;
        }
        $stmt->close();
        return $cuotas;
    }

    /**
     * Total consumido de la póliza del contrato.
     */
    function contratoConsumido(mysqli $conn, int $idContrato): float
    {
        $stmt = $conn->prepare(
            "SELECT COALESCE(SUM(monto_descontado), 0) AS total
             FROM consumo_plan
             WHERE ID_contrato_plan = ?"
        );
        if (!$stmt) {
            return 0.0;
        }
        $stmt->bind_param('i', $idContrato);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (float) ($fila['total'] ?? 0);
    }

    /**
     * Componente de cobertura que aplica a un examen:
     * primero busca una regla específica del examen y, si no existe,
     * usa la regla de su categoría.
     */
    function planComponenteCobertura(mysqli $conn, int $idPlan, ?int $idExamen, ?int $idCategoria): ?array
    {
        if ($idExamen !== null) {
            $stmt = $conn->prepare(
                "SELECT * FROM componentes_planes
                 WHERE ID_planes_componentes = ? AND ID_examen_componentes = ?
                 LIMIT 1"
            );
            if ($stmt) {
                $stmt->bind_param('ii', $idPlan, $idExamen);
                $stmt->execute();
                $fila = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($fila) {
                    return $fila;
                }
            }
        }

        if ($idCategoria !== null) {
            $stmt = $conn->prepare(
                "SELECT * FROM componentes_planes
                 WHERE ID_planes_componentes = ?
                   AND id_categoria_componente = ?
                   AND ID_examen_componentes IS NULL
                 LIMIT 1"
            );
            if ($stmt) {
                $stmt->bind_param('ii', $idPlan, $idCategoria);
                $stmt->execute();
                $fila = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($fila) {
                    return $fila;
                }
            }
        }

        return null;
    }

    /**
     * Consumo acumulado de una categoría dentro de un contrato.
     * Incluye exámenes del catálogo y estudios externos.
     *
     * $origen:
     * - 'interno': servicios prestados en la institución (se controlan por cantidad).
     * - 'externo': consumos fuera de la institución (se controlan por dinero).
     * - 'todos': ambos.
     *
     * @return array{cantidad:int, monto:float}
     */
    function planConsumoCategoria(
        mysqli $conn,
        int $idContrato,
        int $idCategoria,
        string $origen = 'todos'
    ): array {
        $filtroOrigen = '';
        if ($origen === 'interno') {
            $filtroOrigen = ' AND cp.ID_examen_plan IS NOT NULL';
        } elseif ($origen === 'externo') {
            $filtroOrigen = ' AND cp.nombre_estudio_externo IS NOT NULL';
        }

        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS cantidad,
                    COALESCE(SUM(cp.monto_descontado), 0) AS monto
             FROM consumo_plan cp
             LEFT JOIN examenes e ON e.ID_examen = cp.ID_examen_plan
             WHERE cp.ID_contrato_plan = ?
               AND (e.id_categoria = ? OR cp.id_categoria_externa = ?)"
            . $filtroOrigen
        );
        if (!$stmt) {
            return ['cantidad' => 0, 'monto' => 0.0];
        }
        $stmt->bind_param('iii', $idContrato, $idCategoria, $idCategoria);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return [
            'cantidad' => (int) ($fila['cantidad'] ?? 0),
            'monto' => (float) ($fila['monto'] ?? 0),
        ];
    }

    /**
     * Cantidad de veces que se ha consumido un examen específico.
     */
    function planConsumoExamen(mysqli $conn, int $idContrato, int $idExamen): int
    {
        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS cantidad
             FROM consumo_plan
             WHERE ID_contrato_plan = ? AND ID_examen_plan = ?"
        );
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('ii', $idContrato, $idExamen);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int) ($fila['cantidad'] ?? 0);
    }

    /**
     * Valida los cupos de cantidad de una cita interna antes de cobrarla.
     * Los topes en dinero no se revisan aquí: solo aplican a consumos externos.
     *
     * @return array{ok:bool, motivo:string}
     */
    function planValidarCupoCita(mysqli $conn, int $idContrato, int $idPlan, int $idCita): array
    {
        $stmt = $conn->prepare(
            "SELECT ce.id_examen, ex.id_categoria, ex.nombre_examen
             FROM citas_examenes ce
             INNER JOIN examenes ex ON ex.ID_examen = ce.id_examen
             WHERE ce.id_cita = ?
             ORDER BY ce.id_cita_examen ASC"
        );
        if (!$stmt) {
            return ['ok' => false, 'motivo' => 'No se pudieron consultar los exámenes de la cita.'];
        }
        $stmt->bind_param('i', $idCita);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $porExamen = [];
        $porCategoria = [];
        $items = [];

        while ($fila = $resultado->fetch_assoc()) {
            $idExamen = (int) $fila['id_examen'];
            $idCategoria = $fila['id_categoria'] !== null ? (int) $fila['id_categoria'] : null;

            $porExamen[$idExamen] = ($porExamen[$idExamen] ?? 0) + 1;
            if ($idCategoria !== null) {
                $porCategoria[$idCategoria] = ($porCategoria[$idCategoria] ?? 0) + 1;
            }

            $items[$idExamen] = [
                'id_examen' => $idExamen,
                'id_categoria' => $idCategoria,
                'nombre' => (string) $fila['nombre_examen'],
            ];
        }
        $stmt->close();

        foreach ($items as $idExamen => $item) {
            $idCategoria = $item['id_categoria'];
            $componente = planComponenteCobertura($conn, $idPlan, $idExamen, $idCategoria);
            if ($componente === null) {
                return [
                    'ok' => false,
                    'motivo' => 'El examen "' . $item['nombre'] . '" no está incluido en el plan.',
                ];
            }

            if ($componente['cantidad_maxima'] === null) {
                continue;
            }

            $maximo = (int) $componente['cantidad_maxima'];
            if ($componente['ID_examen_componentes'] !== null) {
                $usados = planConsumoExamen($conn, $idContrato, $idExamen);
                $solicitados = $porExamen[$idExamen] ?? 1;
            } else {
                $usados = $idCategoria !== null
                    ? planConsumoCategoria($conn, $idContrato, $idCategoria, 'interno')['cantidad']
                    : 0;
                $solicitados = $idCategoria !== null
                    ? ($porCategoria[$idCategoria] ?? 1)
                    : ($porExamen[$idExamen] ?? 1);
            }

            if ($usados + $solicitados > $maximo) {
                return [
                    'ok' => false,
                    'motivo' => 'El examen "' . $item['nombre']
                        . '" supera el cupo disponible del plan ('
                        . $maximo . ' permitido(s)).',
                ];
            }
        }

        return ['ok' => true, 'motivo' => ''];
    }

    /**
     * Deuda vencida de contratos anteriores al año indicado.
     * Se usa para exigir estar al día antes de completar una nueva inscripción.
     *
     * @return array{cuotas:int, monto:float}
     */
    function contratoDeudaAnterior(mysqli $conn, int $idPersona, int $anio): array
    {
        $hoy = planHoy();
        $stmt = $conn->prepare(
            "SELECT COUNT(*) AS cuotas,
                    COALESCE(SUM(cc.monto - cc.monto_pagado), 0) AS monto
             FROM contrato_cuotas cc
             JOIN contrato_plan cp ON cp.ID_contrato = cc.id_contrato
             WHERE cp.id_persona = ?
               AND cp.anio < ?
               AND cc.fecha_vencimiento < ?
               AND cc.monto_pagado < cc.monto"
        );
        if (!$stmt) {
            return ['cuotas' => 0, 'monto' => 0.0];
        }
        $stmt->bind_param('iis', $idPersona, $anio, $hoy);
        $stmt->execute();
        $fila = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return [
            'cuotas' => (int) ($fila['cuotas'] ?? 0),
            'monto' => (float) ($fila['monto'] ?? 0),
        ];
    }

    /**
     * Fecha en la que se completó el pago inicial del 30 %.
     * Sirve para que la cobertura no sea retroactiva si el inicial se
     * completa después de la fecha de inicio del contrato.
     */
    function contratoFechaInicialCompleta(mysqli $conn, int $idContrato, float $montoInicial): ?string
    {
        if ($montoInicial <= 0) {
            return null;
        }

        $stmt = $conn->prepare(
            "SELECT fecha_pago, monto_cuota
             FROM pagos_contrato
             WHERE ID_contrato = ? AND tipo_pago = 'Pago Inicial'
             ORDER BY fecha_pago ASC, ID_pago ASC"
        );
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('i', $idContrato);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $acumulado = 0.0;
        $fecha = null;
        while ($fila = $resultado->fetch_assoc()) {
            $acumulado += (float) $fila['monto_cuota'];
            if ($acumulado + 0.009 >= $montoInicial) {
                $fecha = substr((string) $fila['fecha_pago'], 0, 10);
                break;
            }
        }

        $stmt->close();
        return $fecha;
    }

    /**
     * Resumen completo de un contrato: montos, cuotas, mora y cobertura.
     */
    function contratoResumen(mysqli $conn, array $contrato, ?string $fecha = null): array
    {
        $fecha = $fecha ?: planHoy();
        $hoy = planHoy();

        $idContrato = (int) $contrato['ID_contrato'];
        $anioContrato = (int) ($contrato['anio'] ?? substr((string) $contrato['fecha_inicio'], 0, 4));
        $plan = planObtenerPorId($conn, (int) $contrato['ID_planes_contrato']);

        $montoTotal = (float) $contrato['monto_total'];
        $montoInicial = planMontoInicial($montoTotal);
        $inicialPagado = contratoInicialPagado($conn, $idContrato);
        $inicialCompleto = $inicialPagado + 0.009 >= $montoInicial && $montoInicial > 0;
        $fechaInicialCompleta = $inicialCompleto
            ? contratoFechaInicialCompleta($conn, $idContrato, $montoInicial)
            : null;

        $cuotas = contratoCuotas($conn, $idContrato);
        $cuotasPagadas = 0;
        $cuotasPendientes = 0;
        $cuotasVencidas = 0;
        $deudaVencida = 0.0;
        $pagadoCuotas = 0.0;
        $proximaCuota = null;

        foreach ($cuotas as $cuota) {
            $saldo = round((float) $cuota['monto'] - (float) $cuota['monto_pagado'], 2);
            $pagadoCuotas += (float) $cuota['monto_pagado'];

            if ($saldo <= 0.009) {
                $cuotasPagadas++;
                continue;
            }

            $cuotasPendientes++;
            $vencida = $cuota['fecha_vencimiento'] < $fecha;
            if ($vencida) {
                $cuotasVencidas++;
                $deudaVencida += $saldo;
            }
            if ($proximaCuota === null) {
                $proximaCuota = $cuota;
            }
        }

        $deudaVencida = round($deudaVencida, 2);
        $pagadoCuotas = round($pagadoCuotas, 2);
        $enMora = $cuotasVencidas > PLAN_MAX_CUOTAS_VENCIDAS;

        // Solvente: el 30 % inicial está completo y no queda ninguna cuota
        // pendiente. El contrato queda totalmente pagado.
        $solvente = $inicialCompleto && $cuotasPendientes === 0 && count($cuotas) > 0;

        $cobertura = (float) ($plan['monto_cobertura'] ?? 0);
        $consumido = contratoConsumido($conn, $idContrato);
        $disponible = max(0, round($cobertura - $consumido, 2));

        // La cobertura empieza en la fecha del contrato o cuando se completa
        // el 30 %, lo que ocurra después. Nunca es retroactiva.
        $coberturaInicio = (string) $contrato['fecha_inicio'];
        if ($fechaInicialCompleta !== null && $fechaInicialCompleta > $coberturaInicio) {
            $coberturaInicio = $fechaInicialCompleta;
        }

        $inactivo = ($contrato['estado_contrato'] ?? '') === 'Inactivo';
        $vigente = !$inactivo
            && $coberturaInicio <= $fecha
            && $fecha <= $contrato['fecha_fin'];

        $coberturaActiva = $vigente && $inicialCompleto && !$enMora;

        $motivo = 'Cobertura activa.';
        if ($inactivo) {
            $motivo = 'El contrato está inactivo.';
        } elseif ($fecha < $coberturaInicio) {
            if (!$inicialCompleto) {
                $motivo = 'Debe completar el pago inicial del 30 %: '
                    . '$' . number_format($inicialPagado, 2) . ' de $' . number_format($montoInicial, 2)
                    . '. La cobertura comienza al completarlo.';
            } elseif ($fechaInicialCompleta !== null && $fechaInicialCompleta > $contrato['fecha_inicio']) {
                $motivo = 'La cobertura comienza el '
                    . date('d/m/Y', strtotime($coberturaInicio))
                    . ' (pago inicial completado el '
                    . date('d/m/Y', strtotime($fechaInicialCompleta)) . ').';
            } else {
                $motivo = 'La cobertura comienza el '
                    . date('d/m/Y', strtotime($coberturaInicio)) . '.';
            }
        } elseif ($fecha > $contrato['fecha_fin']) {
            $motivo = 'El contrato venció el ' . date('d/m/Y', strtotime($contrato['fecha_fin'])) . '.';
        } elseif (!$inicialCompleto) {
            $motivo = 'Debe completar el pago inicial del 30 %: '
                . '$' . number_format($inicialPagado, 2) . ' de $' . number_format($montoInicial, 2) . '.';
        } elseif ($enMora) {
            $motivo = 'Tiene ' . $cuotasVencidas . ' cuotas vencidas; el límite es '
                . PLAN_MAX_CUOTAS_VENCIDAS . '.';
        }

        return [
            'contrato' => $contrato,
            'plan' => $plan,
            'anio' => $anioContrato,
            'monto_total' => round($montoTotal, 2),
            'monto_inicial' => $montoInicial,
            'inicial_pagado' => round($inicialPagado, 2),
            'saldo_inicial' => max(0, round($montoInicial - $inicialPagado, 2)),
            'inicial_completo' => $inicialCompleto,
            'fecha_inicial_completa' => $fechaInicialCompleta,
            'cobertura_inicio' => $coberturaInicio,
            'cuotas' => $cuotas,
            'cuotas_total' => count($cuotas),
            'cuotas_pagadas' => $cuotasPagadas,
            'cuotas_pendientes' => $cuotasPendientes,
            'cuotas_vencidas' => $cuotasVencidas,
            'deuda_vencida' => $deudaVencida,
            'pagado_total' => round($inicialPagado + $pagadoCuotas, 2),
            'solvente' => $solvente,
            'en_mora' => $enMora,
            'proxima_cuota' => $proximaCuota,
            'cobertura' => $cobertura,
            'consumido' => round($consumido, 2),
            'disponible' => $disponible,
            'vigente' => $vigente,
            'cobertura_activa' => $coberturaActiva,
            'motivo' => $motivo,
            'hoy' => $hoy,
            'fecha_consulta' => $fecha,
        ];
    }

    /**
     * Contrato más relevante de una persona para mostrarlo en un listado:
     * primero el vigente o por iniciar; si no hay, el más reciente.
     */
    function planContratoRelevante(mysqli $conn, int $idPersona, ?string $fecha = null): ?array
    {
        $hoy = $fecha ?: planHoy();
        $stmt = $conn->prepare(
            "SELECT * FROM contrato_plan
             WHERE id_persona = ?
             ORDER BY (estado_contrato NOT IN ('Inactivo', 'Vencido') AND fecha_fin >= ?) DESC,
                      anio DESC,
                      fecha_inicio DESC
             LIMIT 1"
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('is', $idPersona, $hoy);
        $stmt->execute();
        $contrato = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $contrato ?: null;
    }

    /**
     * Texto y clase Bootstrap del estado real del plan para listados.
     *
     * @return array{0:string,1:string} [texto, clase]
     */
    function planEstadoVisualContrato(?array $contrato, ?array $resumen): array
    {
        if (!$contrato || !$resumen) {
            return ['Sin contrato', 'bg-secondary'];
        }
        $estadoContrato = (string) ($contrato['estado_contrato'] ?? '');
        if ($estadoContrato === 'Inactivo') {
            return ['Inactivo', 'bg-secondary'];
        }
        if ($estadoContrato === 'Vencido' || ($contrato['fecha_fin'] ?? '') < planHoy()) {
            return ['Vencido', 'bg-dark'];
        }
        if (!$resumen['inicial_completo']) {
            return ['Pago inicial pendiente', 'bg-warning text-dark'];
        }
        if ($resumen['en_mora']) {
            return ['En mora', 'bg-danger'];
        }
        if ($estadoContrato === 'Pendiente') {
            // Inicial completo pero el contrato sigue pendiente: deuda anterior.
            return ['Pendiente', 'bg-warning text-dark'];
        }
        if ($resumen['cobertura_activa']) {
            return ['Cobertura activa', 'bg-success'];
        }

        return ['Por iniciar', 'bg-info text-dark'];
    }

    /**
     * Valida la cobertura de una persona (titular o beneficiario).
     *
     * - $fecha: fecha para la que se necesita la cobertura (fecha de la cita).
     * - $fechaMora: fecha con la que se calcula la mora actual.
     *
     * @return array{ok:bool, motivo:string, contrato:?array, plan:?array, resumen:?array, titular:?int, vigente:bool, cobertura_activa:bool, fecha_consulta:string, fecha_mora:string}
     */
    function contratoValidarCobertura(
        mysqli $conn,
        int $idPersona,
        ?string $fecha = null,
        ?string $fechaMora = null
    ): array {
        $fecha = $fecha ?: planHoy();
        $fechaMora = $fechaMora ?: planHoy();

        $idTitular = planPersonaTitular($conn, $idPersona);
        if ($idTitular === null) {
            return [
                'ok' => false,
                'motivo' => 'La persona no está registrada como afiliado ni como beneficiario.',
                'contrato' => null,
                'plan' => null,
                'resumen' => null,
                'titular' => null,
                'vigente' => false,
                'cobertura_activa' => false,
                'fecha_consulta' => $fecha,
                'fecha_mora' => $fechaMora,
            ];
        }

        $contrato = contratoVigente($conn, $idTitular, $fecha);
        if ($contrato === null) {
            return [
                'ok' => false,
                'motivo' => 'No tiene un contrato vigente para la fecha indicada.',
                'contrato' => null,
                'plan' => null,
                'resumen' => null,
                'titular' => $idTitular,
                'vigente' => false,
                'cobertura_activa' => false,
                'fecha_consulta' => $fecha,
                'fecha_mora' => $fechaMora,
            ];
        }

        // La mora se calcula con la fecha actual, no con la fecha futura de
        // la cita; así no se bloquea una cita futura por cuotas que aún no
        // han vencido.
        $resumen = contratoResumen($conn, $contrato, $fechaMora);

        $inactivo = ($contrato['estado_contrato'] ?? '') === 'Inactivo';
        $coberturaInicio = $resumen['cobertura_inicio'];
        $vigenteFecha = !$inactivo
            && $coberturaInicio <= $fecha
            && $fecha <= $contrato['fecha_fin'];
        $ok = $vigenteFecha && $resumen['inicial_completo'] && !$resumen['en_mora'];

        $motivo = 'Cobertura activa.';
        if ($inactivo) {
            $motivo = 'El contrato está inactivo.';
        } elseif ($fecha < $coberturaInicio) {
            if (!$resumen['inicial_completo']) {
                $motivo = 'Debe completar el pago inicial del 30 %: '
                    . '$' . number_format($resumen['inicial_pagado'], 2)
                    . ' de $' . number_format($resumen['monto_inicial'], 2)
                    . '. La cobertura comienza al completarlo.';
            } elseif (
                $resumen['fecha_inicial_completa'] !== null
                && $resumen['fecha_inicial_completa'] > $contrato['fecha_inicio']
            ) {
                $motivo = 'La cobertura comienza el '
                    . date('d/m/Y', strtotime($coberturaInicio))
                    . ' (pago inicial completado el '
                    . date('d/m/Y', strtotime($resumen['fecha_inicial_completa'])) . ').';
            } else {
                $motivo = 'La cobertura comienza el '
                    . date('d/m/Y', strtotime($coberturaInicio)) . '.';
            }
        } elseif ($fecha > $contrato['fecha_fin']) {
            $motivo = 'El contrato venció el ' . date('d/m/Y', strtotime($contrato['fecha_fin'])) . '.';
        } elseif (!$resumen['inicial_completo']) {
            $motivo = 'Debe completar el pago inicial del 30 %: '
                . '$' . number_format($resumen['inicial_pagado'], 2)
                . ' de $' . number_format($resumen['monto_inicial'], 2) . '.';
        } elseif ($resumen['en_mora']) {
            $motivo = 'Tiene ' . $resumen['cuotas_vencidas'] . ' cuotas vencidas; el límite es '
                . PLAN_MAX_CUOTAS_VENCIDAS . '.';
        }

        return [
            'ok' => $ok,
            'motivo' => $motivo,
            'contrato' => $contrato,
            'plan' => $resumen['plan'],
            'resumen' => $resumen,
            'titular' => $idTitular,
            'vigente' => $vigenteFecha,
            'cobertura_activa' => $ok,
            'fecha_consulta' => $fecha,
            'fecha_mora' => $fechaMora,
        ];
    }

    /**
     * Recalcula y guarda el estado del contrato:
     * Vencido al pasar la fecha fin, Activo si el 30 % está completo y no
     * hay deuda anterior, Pendiente en caso contrario.
     */
    function contratoActualizarEstado(mysqli $conn, array $contrato): string
    {
        $estadoActual = (string) ($contrato['estado_contrato'] ?? '');
        if ($estadoActual === 'Inactivo') {
            return 'Inactivo';
        }

        $hoy = planHoy();
        if ($contrato['fecha_fin'] < $hoy) {
            $nuevoEstado = 'Vencido';
        } else {
            $resumen = contratoResumen($conn, $contrato);
            $deudaAnterior = contratoDeudaAnterior(
                $conn,
                (int) $contrato['id_persona'],
                (int) ($contrato['anio'] ?? 0)
            );
            $nuevoEstado = ($resumen['inicial_completo'] && $deudaAnterior['cuotas'] === 0)
                ? 'Activo'
                : 'Pendiente';
        }

        if ($nuevoEstado !== $estadoActual) {
            $stmt = $conn->prepare(
                "UPDATE contrato_plan SET estado_contrato = ? WHERE ID_contrato = ?"
            );
            if ($stmt) {
                $idContrato = (int) $contrato['ID_contrato'];
                $stmt->bind_param('si', $nuevoEstado, $idContrato);
                $stmt->execute();
                $stmt->close();
            }
        }

        return $nuevoEstado;
    }

    /**
     * Recalcula el estado a partir del ID del contrato.
     */
    function contratoActualizarEstadoPorId(mysqli $conn, int $idContrato): ?string
    {
        $stmt = $conn->prepare("SELECT * FROM contrato_plan WHERE ID_contrato = ? LIMIT 1");
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $idContrato);
        $stmt->execute();
        $contrato = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $contrato ? contratoActualizarEstado($conn, $contrato) : null;
    }

    /**
     * Genera las cuotas de un contrato ya insertado.
     * Debe ejecutarse dentro de la transacción que crea el contrato.
     *
     * @return int Cantidad de cuotas generadas.
     */
    function contratoGenerarCuotas(mysqli $conn, int $idContrato, array $contrato): int
    {
        $anio = (int) ($contrato['anio'] ?? substr((string) $contrato['fecha_inicio'], 0, 4));
        $diaPago = (int) ($contrato['dia_pago_mensual'] ?? PLAN_DIA_PAGO_DEFECTO);
        $diaPago = max(1, min(31, $diaPago));

        $meses = planMesesCuotas($anio, (string) $contrato['fecha_inicio']);
        $montos = planMontosCuotas((float) $contrato['monto_total'], count($meses));

        $stmtExiste = $conn->prepare("SELECT COUNT(*) AS total FROM contrato_cuotas WHERE id_contrato = ?");
        if ($stmtExiste) {
            $stmtExiste->bind_param('i', $idContrato);
            $stmtExiste->execute();
            $total = (int) ($stmtExiste->get_result()->fetch_assoc()['total'] ?? 0);
            $stmtExiste->close();
            if ($total > 0) {
                return 0;
            }
        }

        $stmt = $conn->prepare(
            "INSERT INTO contrato_cuotas
                (id_contrato, numero_cuota, anio, mes, fecha_vencimiento, monto, monto_pagado, estado)
             VALUES (?, ?, ?, ?, ?, ?, 0.00, 'Pendiente')"
        );
        if (!$stmt) {
            throw new Exception('No se pudieron preparar las cuotas del contrato.');
        }

        $numero = 1;
        foreach ($meses as $indice => $mes) {
            $fechaVencimiento = planFechaVencimiento($anio, (int) $mes, $diaPago);
            $monto = (float) $montos[$indice];
            $stmt->bind_param(
                'iiiisd',
                $idContrato,
                $numero,
                $anio,
                $mes,
                $fechaVencimiento,
                $monto
            );
            $stmt->execute();
            $numero++;
        }

        $stmt->close();
        return count($meses);
    }

    /**
     * Crea el contrato anual con sus cuotas.
     *
     * Reglas:
     * - El monto se toma del precio del plan (no es editable).
     * - Dentro de oct-dic se inscribe para el año siguiente o, como
     *   inscripción tardía, para el año en curso (vigente hasta el 31/12).
     * - Fuera del periodo solo un administrador (rol 1) puede inscribir.
     * - Si el afiliado debe 3 o más cuotas de años anteriores, debe ponerse al día.
     *
     * @return array{id_contrato:int, contrato:array, resumen:array, cuotas_generadas:int}
     */
    function contratoCrearAnual(
        mysqli $conn,
        int $idPersona,
        int $idPlan,
        int $diaPago,
        bool $esAdmin = false,
        ?string $fechaInscripcion = null
    ): array {
        $fecha = $fechaInscripcion ?: planHoy();
        $anioInscripcion = (int) substr($fecha, 0, 4);

        $plan = planObtenerPorId($conn, $idPlan);
        if ($plan === null) {
            throw new Exception('El plan seleccionado no existe.');
        }
        if ((int) $plan['activo'] !== 1 || empty($plan['anio'])) {
            throw new Exception('El plan seleccionado no está disponible para inscripciones.');
        }

        $anioPlan = (int) $plan['anio'];
        $periodoAbierto = planPeriodoInscripcionAbierto($fecha);

        if ($periodoAbierto) {
            // Durante oct-dic conviven dos inscripciones válidas:
            // - el plan del año siguiente (inscripción anticipada), y
            // - el plan del año en curso (inscripción tardía, vigente hasta el 31/12).
            $esPlanSiguiente = $anioPlan === $anioInscripcion + 1;
            $esPlanEnCurso = $anioPlan === $anioInscripcion;

            if (!$esPlanSiguiente && !$esPlanEnCurso) {
                throw new Exception(
                    'Durante el periodo de inscripción solo se puede inscribir al plan del año en curso'
                    . ' o al del año siguiente.'
                );
            }
        } else {
            if (!$esAdmin) {
                throw new Exception(
                    'Fuera del periodo de inscripción (' . planPeriodoInscripcionTexto()
                    . ') solo un administrador puede autorizar la inscripción.'
                );
            }
            if ($anioPlan < $anioInscripcion || $anioPlan > $anioInscripcion + 1) {
                throw new Exception('El plan seleccionado no corresponde al año de la inscripción.');
            }
        }

        if (contratoPorAnio($conn, $idPersona, $anioPlan) !== null) {
            throw new Exception('El afiliado ya tiene un contrato para el año ' . $anioPlan . '.');
        }

        $deudaAnterior = contratoDeudaAnterior($conn, $idPersona, $anioPlan);
        if ($deudaAnterior['cuotas'] > PLAN_MAX_CUOTAS_VENCIDAS) {
            throw new Exception(
                'El afiliado tiene ' . $deudaAnterior['cuotas'] . ' cuotas vencidas de años anteriores ('
                . '$' . number_format($deudaAnterior['monto'], 2)
                . '). Debe ponerse al día antes de inscribirse.'
            );
        }

        if ($anioPlan === $anioInscripcion) {
            $fechaInicio = $fecha;
            $fechaFin = sprintf('%04d-12-31', $anioPlan);
        } else {
            $fechaInicio = sprintf('%04d-01-01', $anioPlan);
            $fechaFin = sprintf('%04d-12-31', $anioPlan);
        }

        // Valida que queden meses antes de insertar el contrato.
        planMesesCuotas($anioPlan, $fechaInicio);

        $montoTotal = round((float) $plan['precio'], 2);
        if ($montoTotal <= 0) {
            throw new Exception('El plan no tiene un precio válido.');
        }

        $diaPago = max(1, min(31, $diaPago ?: PLAN_DIA_PAGO_DEFECTO));

        $stmt = $conn->prepare(
            "INSERT INTO contrato_plan
                (ID_planes_contrato, id_persona, anio, fecha_inicio, fecha_fin, monto_total,
                 frecuencia_pago, dia_pago_mensual, estado_contrato)
             VALUES (?, ?, ?, ?, ?, ?, 'Mensual', ?, 'Pendiente')"
        );
        if (!$stmt) {
            throw new Exception('No se pudo preparar el contrato.');
        }

        $stmt->bind_param(
            'iiissdi',
            $idPlan,
            $idPersona,
            $anioPlan,
            $fechaInicio,
            $fechaFin,
            $montoTotal,
            $diaPago
        );
        $stmt->execute();
        $idContrato = (int) $conn->insert_id;
        $stmt->close();

        $stmt = $conn->prepare("SELECT * FROM contrato_plan WHERE ID_contrato = ? LIMIT 1");
        $stmt->bind_param('i', $idContrato);
        $stmt->execute();
        $contrato = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $cuotasGeneradas = contratoGenerarCuotas($conn, $idContrato, $contrato);
        $estado = contratoActualizarEstado($conn, $contrato);
        $contrato['estado_contrato'] = $estado;

        return [
            'id_contrato' => $idContrato,
            'contrato' => $contrato,
            'resumen' => contratoResumen($conn, $contrato),
            'cuotas_generadas' => $cuotasGeneradas,
        ];
    }

    /**
     * Cuotas pendientes de un contrato, de la más antigua a la más reciente.
     */
    function contratoCuotasPendientes(mysqli $conn, int $idContrato): array
    {
        $cuotas = [];
        $stmt = $conn->prepare(
            "SELECT * FROM contrato_cuotas
             WHERE id_contrato = ? AND monto_pagado < monto
             ORDER BY fecha_vencimiento ASC, numero_cuota ASC"
        );
        if (!$stmt) {
            return $cuotas;
        }
        $stmt->bind_param('i', $idContrato);
        $stmt->execute();
        $resultado = $stmt->get_result();
        while ($fila = $resultado->fetch_assoc()) {
            $cuotas[] = $fila;
        }
        $stmt->close();
        return $cuotas;
    }

    /**
     * Tasa BCV oficial. Intenta obtenerla del servicio público y usa la
     * tasa enviada por la interfaz como respaldo si la consulta falla.
     */
    function planObtenerTasaBCV(?float $respaldo = null): float
    {
        $tasa = 0.0;

        if (function_exists('curl_init')) {
            $ch = curl_init('https://ve.dolarapi.com/v1/dolares/oficial');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_TIMEOUT => 6,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT => 'IPSPUPTM/1.0',
                // Endpoint público de consulta; XAMPP no siempre trae el bundle de CA.
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $respuesta = curl_exec($ch);
            $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($respuesta !== false && $codigo === 200) {
                $datos = json_decode($respuesta, true);
                if (is_array($datos)) {
                    $tasa = (float) ($datos['promedio'] ?? $datos['venta'] ?? 0);
                }
            }
        }

        if ($tasa <= 0 && $respaldo !== null && $respaldo > 0) {
            $tasa = $respaldo;
        }

        return round($tasa, 2);
    }

    /**
     * Registra un pago y lo aplica a la cuota más antigua pendiente.
     *
     * - Pago Inicial: acepta abonos parciales hasta completar el 30 %.
     * - Cuota: exige cuotas completas y aplica a las N más antiguas.
     *
     * Debe ejecutarse dentro de una transacción abierta por el llamador.
     *
     * @return array{tipo:string, pagos:int[], monto:float, monto_bs:float, tasa_bcv:float, cuotas:int, estado:string}
     */
    function contratoRegistrarPago(
        mysqli $conn,
        int $idContrato,
        string $tipoPago,
        float $monto,
        string $fechaPago,
        string $metodoPago,
        float $tasaBcv,
        ?int $cantidadCuotas = null
    ): array {
        $monto = round($monto, 2);
        if ($monto <= 0) {
            throw new Exception('El monto debe ser mayor que cero.');
        }
        if ($tasaBcv <= 0) {
            throw new Exception('No se pudo determinar la tasa BCV. Intente de nuevo o verifique la tasa manualmente.');
        }

        $stmt = $conn->prepare("SELECT * FROM contrato_plan WHERE ID_contrato = ? LIMIT 1");
        if (!$stmt) {
            throw new Exception('No se pudo consultar el contrato.');
        }
        $stmt->bind_param('i', $idContrato);
        $stmt->execute();
        $contrato = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$contrato) {
            throw new Exception('El contrato no existe.');
        }
        if (($contrato['estado_contrato'] ?? '') === 'Inactivo') {
            throw new Exception('El contrato está inactivo y no admite pagos.');
        }

        $resumen = contratoResumen($conn, $contrato);
        $montoBs = round($monto * $tasaBcv, 2);

        $insertarPago = static function (float $montoPago, ?int $numeroCuota) use (
            $conn,
            $idContrato,
            $fechaPago,
            $metodoPago,
            $tasaBcv,
            $tipoPago
        ): int {
            $montoBsPago = round($montoPago * $tasaBcv, 2);

            $stmt = $conn->prepare(
                "INSERT INTO pagos_contrato
                    (ID_contrato, monto_cuota, fecha_pago, numero_cuota, metodo_pago,
                     tasa_bcv, monto_bs, tipo_pago)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            if (!$stmt) {
                throw new Exception('No se pudo preparar el registro del pago.');
            }
            $stmt->bind_param(
                'idsisdds',
                $idContrato,
                $montoPago,
                $fechaPago,
                $numeroCuota,
                $metodoPago,
                $tasaBcv,
                $montoBsPago,
                $tipoPago
            );
            $stmt->execute();
            $idPago = (int) $conn->insert_id;
            $stmt->close();
            return $idPago;
        };

        if ($tipoPago === 'Pago Inicial') {
            if ($resumen['inicial_completo']) {
                throw new Exception('El pago inicial del 30 % ya está completo.');
            }
            if ($monto > $resumen['saldo_inicial'] + 0.009) {
                throw new Exception(
                    'El monto supera el pago inicial pendiente ($'
                    . number_format($resumen['saldo_inicial'], 2) . ').'
                );
            }

            $idPago = $insertarPago($monto, null);
            $estado = contratoActualizarEstado($conn, $contrato);
            $resumenNuevo = contratoResumen($conn, $contrato);

            return [
                'tipo' => 'Pago Inicial',
                'pagos' => [$idPago],
                'monto' => $monto,
                'monto_bs' => $montoBs,
                'tasa_bcv' => $tasaBcv,
                'cuotas' => 0,
                'estado' => $estado,
                'inicial_completo' => $resumenNuevo['inicial_completo'],
                'saldo_inicial' => $resumenNuevo['saldo_inicial'],
            ];
        }

        if ($tipoPago !== 'Cuota') {
            throw new Exception('Tipo de pago no válido.');
        }

        if (!$resumen['inicial_completo']) {
            throw new Exception('Debe completar el pago inicial del 30 % antes de pagar cuotas.');
        }

        $pendientes = contratoCuotasPendientes($conn, $idContrato);
        if (!$pendientes) {
            throw new Exception('El contrato no tiene cuotas pendientes.');
        }

        $cantidad = $cantidadCuotas !== null && $cantidadCuotas > 0 ? $cantidadCuotas : 1;
        if ($cantidad > count($pendientes)) {
            throw new Exception(
                'Solo hay ' . count($pendientes) . ' cuota(s) pendiente(s).'
            );
        }

        $seleccion = array_slice($pendientes, 0, $cantidad);
        $esperado = 0.0;
        foreach ($seleccion as $cuota) {
            $esperado += round((float) $cuota['monto'] - (float) $cuota['monto_pagado'], 2);
        }
        $esperado = round($esperado, 2);

        if (abs($monto - $esperado) > 0.009) {
            throw new Exception(
                'El monto debe ser exactamente $' . number_format($esperado, 2)
                . ' por ' . $cantidad . ' cuota(s) completa(s).'
            );
        }

        $idsPagos = [];
        $stmtCuota = $conn->prepare(
            "UPDATE contrato_cuotas
             SET monto_pagado = monto, estado = 'Pagada', id_pago = ?
             WHERE id_cuota = ? AND monto_pagado < monto"
        );
        if (!$stmtCuota) {
            throw new Exception('No se pudieron actualizar las cuotas.');
        }

        foreach ($seleccion as $cuota) {
            $saldoCuota = round((float) $cuota['monto'] - (float) $cuota['monto_pagado'], 2);
            $idPago = $insertarPago($saldoCuota, (int) $cuota['numero_cuota']);

            $idCuota = (int) $cuota['id_cuota'];
            $stmtCuota->bind_param('ii', $idPago, $idCuota);
            $stmtCuota->execute();
            if ($stmtCuota->affected_rows !== 1) {
                throw new Exception('La cuota #' . $cuota['numero_cuota'] . ' ya fue pagada.');
            }
            $idsPagos[] = $idPago;
        }
        $stmtCuota->close();

        $estado = contratoActualizarEstado($conn, $contrato);

        return [
            'tipo' => 'Cuota',
            'pagos' => $idsPagos,
            'monto' => $esperado,
            'monto_bs' => round($esperado * $tasaBcv, 2),
            'tasa_bcv' => $tasaBcv,
            'cuotas' => $cantidad,
            'estado' => $estado,
            'inicial_completo' => true,
            'saldo_inicial' => 0.0,
        ];
    }
}
