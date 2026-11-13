<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow p-4">
                <div class="logo-container">
                    <img src="/IPSPUPTM/recursos/img/logoipspsazul.png" alt="Logo" class="logo">
                </div>
                <h2 class="mb-3">Verificar Preguntas</h2>
                <form action="verificar_respuestas.php" method="POST">
                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                    <?php
                    foreach ($preguntas_usuario as $p_id => $p_texto) {
                        echo '<div class="mb-3">';
                        echo '<label class="form-label">' . htmlspecialchars($p_texto) . '</label>';
                        echo '<input type="text" class="form-control" name="respuesta_seguridad[' . $p_id . ']" required>';
                        echo '</div>';
                    }
                    ?>
                    <button type="submit" class="btn btn-primary w-100">Verificar Respuestas</button>
                </form>
            </div>
        </div>
    </div>
</div>