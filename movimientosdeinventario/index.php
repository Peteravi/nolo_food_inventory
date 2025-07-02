<?php
session_start();
include("../includes/header.php");
include("../db/conexion.php");

// Inicializar variables y array de errores
$errores = [];
$tipo = '';
$cantidad = '';
$motivo = '';
$id_articulo = 1; // Ajusta este valor si es dinámico

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tipo = $_POST['tipo_movimiento'] ?? '';
    $cantidad = $_POST['cantidad'] ?? '';
    $motivo = trim($_POST['motivo'] ?? '');
    $id_articulo = $_POST['id_articulo'] ?? 1;
    $id_usuario = 1; // Ajustar según usuario real

    // Validaciones
    $tipos_validos = ['entrada', 'salida', 'ajuste'];
    if (!in_array($tipo, $tipos_validos, true)) {
        $errores[] = "Tipo de movimiento inválido.";
    }
    if (!is_numeric($cantidad) || $cantidad <= 0) {
        $errores[] = "La cantidad debe ser un número positivo.";
    }
    if (strlen($motivo) > 255) {
        $errores[] = "El motivo no puede tener más de 255 caracteres.";
    }

    if (empty($errores)) {
        try {
            if (isset($_POST['crear'])) {
                $sql = "INSERT INTO movimientos_inventario (id_articulo, tipo_movimiento, cantidad, motivo, id_usuario)
                        VALUES (:id_articulo, :tipo_movimiento, :cantidad, :motivo, :id_usuario)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'id_articulo' => $id_articulo,
                    'tipo_movimiento' => $tipo,
                    'cantidad' => $cantidad,
                    'motivo' => $motivo,
                    'id_usuario' => $id_usuario
                ]);

                $_SESSION['mensaje'] = "Movimiento registrado correctamente";
                header("Location: index.php");
                exit();
            } elseif (isset($_POST['actualizar'])) {
                $id = $_POST['id'];
                $sql = "UPDATE movimientos_inventario 
                        SET tipo_movimiento = :tipo, cantidad = :cantidad, motivo = :motivo
                        WHERE id_movimiento = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'id' => $id,
                    'tipo' => $tipo,
                    'cantidad' => $cantidad,
                    'motivo' => $motivo
                ]);

                $_SESSION['mensaje'] = "Movimiento actualizado correctamente";
                header("Location: index.php");
                exit();
            }
        } catch (PDOException $e) {
            $errores[] = "Error en la base de datos: " . $e->getMessage();
        }
    }
}

// Eliminar movimiento
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    try {
        $sql = "DELETE FROM movimientos_inventario WHERE id_movimiento = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        $_SESSION['mensaje'] = "Movimiento eliminado correctamente";
        header("Location: index.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['mensaje_error'] = "Error al eliminar movimiento: " . $e->getMessage();
        header("Location: index.php");
        exit();
    }
}

// Obtener lista de movimientos
$sql = "SELECT * FROM movimientos_inventario ORDER BY fecha_movimiento DESC";
$movimientos = $pdo->query($sql)->fetchAll();

// Obtener movimiento para edición
$movimiento_actual = null;
if (isset($_GET['editar'])) {
    $id = $_GET['editar'];
    $sql = "SELECT * FROM movimientos_inventario WHERE id_movimiento = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $movimiento_actual = $stmt->fetch();

    // Si hay errores y estamos editando, conserva los valores del POST para no perder datos
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($errores)) {
        $movimiento_actual['tipo_movimiento'] = $tipo;
        $movimiento_actual['cantidad'] = $cantidad;
        $movimiento_actual['motivo'] = $motivo;
    }
}
?>

<div class="container mt-4">
    <h2 class="mb-4"><i class="bi bi-box-arrow-in-down"></i> Movimientos de Inventario</h2>

    <!-- Botón volver -->
    <a href="http://localhost:8080/nolo_food_inventory/" class="btn btn-success mb-3">
        <i class="bi bi-arrow-left-circle-fill"></i> Volver al Menú Principal
    </a>

    <!-- Mensajes de éxito -->
    <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill"></i> <?= $_SESSION['mensaje'];
                                                    unset($_SESSION['mensaje']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <!-- Mensajes de error -->
    <?php if (!empty($errores)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <ul class="mb-0">
                <?php foreach ($errores as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['mensaje_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i> <?= $_SESSION['mensaje_error'];
                                                            unset($_SESSION['mensaje_error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <!-- Formulario -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <i class="bi bi-pencil-square"></i> <?= $movimiento_actual ? 'Editar Movimiento' : 'Registrar Movimiento' ?>
        </div>
        <div class="card-body">
            <form method="POST" novalidate>
                <?php if ($movimiento_actual): ?>
                    <input type="hidden" name="id" value="<?= $movimiento_actual['id_movimiento'] ?>">
                    <input type="hidden" name="id_articulo" value="<?= htmlspecialchars($movimiento_actual['id_articulo']) ?>">
                <?php else: ?>
                    <input type="hidden" name="id_articulo" value="<?= htmlspecialchars($id_articulo) ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label for="tipo_movimiento" class="form-label">
                        <i class="bi bi-arrow-repeat"></i> Tipo de Movimiento
                    </label>
                    <select class="form-select" id="tipo_movimiento" name="tipo_movimiento" required>
                        <option value="">Seleccionar...</option>
                        <option value="entrada" <?= ($movimiento_actual && $movimiento_actual['tipo_movimiento'] == 'entrada') || (!$movimiento_actual && $tipo == 'entrada') ? 'selected' : '' ?>>Entrada</option>
                        <option value="salida" <?= ($movimiento_actual && $movimiento_actual['tipo_movimiento'] == 'salida') || (!$movimiento_actual && $tipo == 'salida') ? 'selected' : '' ?>>Salida</option>
                        <option value="ajuste" <?= ($movimiento_actual && $movimiento_actual['tipo_movimiento'] == 'ajuste') || (!$movimiento_actual && $tipo == 'ajuste') ? 'selected' : '' ?>>Ajuste</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="cantidad" class="form-label">
                        <i class="bi bi-calculator"></i> Cantidad
                    </label>
                    <input type="number" step="0.01" min="0.01" class="form-control" id="cantidad" name="cantidad"
                        value="<?= htmlspecialchars($movimiento_actual['cantidad'] ?? $cantidad) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="motivo" class="form-label">
                        <i class="bi bi-journal-text"></i> Motivo
                    </label>
                    <textarea class="form-control" id="motivo" name="motivo" rows="3" maxlength="255"><?= htmlspecialchars($movimiento_actual['motivo'] ?? $motivo) ?></textarea>
                </div>

                <button type="submit" name="<?= $movimiento_actual ? 'actualizar' : 'crear' ?>" class="btn btn-success">
                    <i class="bi bi-save-fill"></i> <?= $movimiento_actual ? 'Actualizar' : 'Registrar' ?>
                </button>

                <?php if ($movimiento_actual): ?>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle-fill"></i> Cancelar
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </div>


    <!-- Tabla de movimientos -->
    <div class="card">
        <div class="card-header bg-success text-white">
            <i class="bi bi-clock-history"></i> Historial de Movimientos
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th><i class="bi bi-hash"></i> ID</th>
                            <th><i class="bi bi-arrow-left-right"></i> Tipo</th>
                            <th><i class="bi bi-123"></i> Cantidad</th>
                            <th><i class="bi bi-card-text"></i> Motivo</th>
                            <th><i class="bi bi-calendar-event"></i> Fecha</th>
                            <th><i class="bi bi-gear-fill"></i> Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimientos as $mov): ?>
                            <tr>
                                <td><?= $mov['id_movimiento'] ?></td>
                                <td><?= ucfirst(htmlspecialchars($mov['tipo_movimiento'])) ?></td>
                                <td><?= htmlspecialchars($mov['cantidad']) ?></td>
                                <td><?= $mov['motivo'] ? htmlspecialchars(substr($mov['motivo'], 0, 50)) . (strlen($mov['motivo']) > 50 ? '...' : '') : '-' ?></td>
                                <td><?= date('Y-m-d H:i', strtotime($mov['fecha_movimiento'])) ?></td>
                                <td>
                                    <a href="index.php?editar=<?= $mov['id_movimiento'] ?>" class="btn btn-sm btn-success" title="Editar">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalEliminar" data-id="<?= $mov['id_movimiento'] ?>" title="Eliminar">
                                        <i class="bi bi-trash3-fill"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Confirmación de Eliminación -->
<div class="modal fade" id="modalEliminar" tabindex="-1" aria-labelledby="modalEliminarLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalEliminarLabel">
                    <i class="bi bi-exclamation-triangle-fill"></i> Confirmar Eliminación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                ¿Estás seguro de que deseas eliminar este movimiento? Esta acción no se puede deshacer.
            </div>
            <div class="modal-footer">
                <a href="#" id="btnConfirmarEliminar" class="btn btn-success">Eliminar</a>
                <button type="button" class="btn btn-outline-success" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modalEliminar = document.getElementById('modalEliminar');
        modalEliminar.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const link = document.getElementById('btnConfirmarEliminar');
            link.href = `index.php?eliminar=${id}`;
        });
    });
</script>

<?php include("../includes/footer.php"); ?>