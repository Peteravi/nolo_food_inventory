<?php
session_start();
include("../includes/header.php");
include("../db/conexion.php"); // Ruta correcta

// Inicializar variables para errores y valores previos
$errores = [];
$nombre = '';
$descripcion = '';
$categoria_actual = null;

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recoger datos con trim para evitar espacios extras
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    // Validaciones
    if ($nombre === '') {
        $errores[] = "El nombre de la categoría es obligatorio.";
    } elseif (strlen($nombre) > 100) {
        $errores[] = "El nombre de la categoría no puede tener más de 100 caracteres.";
    }

    if (strlen($descripcion) > 255) {
        $errores[] = "La descripción no puede tener más de 255 caracteres.";
    }

    // Si no hay errores, proceder con inserción o actualización
    if (empty($errores)) {
        try {
            if (isset($_POST['crear'])) {
                $sql = "INSERT INTO categorias (nombre, descripcion) VALUES (:nombre, :descripcion)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['nombre' => $nombre, 'descripcion' => $descripcion]);

                $_SESSION['mensaje'] = "Categoría creada correctamente";
                header("Location: index.php");
                exit();
            } elseif (isset($_POST['actualizar'])) {
                $id = $_POST['id'];
                $sql = "UPDATE categorias SET nombre = :nombre, descripcion = :descripcion WHERE id_categoria = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['id' => $id, 'nombre' => $nombre, 'descripcion' => $descripcion]);

                $_SESSION['mensaje'] = "Categoría actualizada correctamente";
                header("Location: index.php");
                exit();
            }
        } catch (PDOException $e) {
            $errores[] = "Error en la base de datos: " . $e->getMessage();
        }
    }
}

// Eliminar categoría
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    try {
        $sql = "DELETE FROM categorias WHERE id_categoria = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        $_SESSION['mensaje'] = "Categoría eliminada correctamente";
        header("Location: index.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['mensaje_error'] = "Error al eliminar categoría: " . $e->getMessage();
        header("Location: index.php");
        exit();
    }
}

// Obtener categorías para listar
$sql = "SELECT * FROM categorias ORDER BY nombre";
$categorias = $pdo->query($sql)->fetchAll();

// Obtener categoría para editar (si aplica)
if (isset($_GET['editar'])) {
    $id = $_GET['editar'];
    $sql = "SELECT * FROM categorias WHERE id_categoria = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $categoria_actual = $stmt->fetch();

    // Si vino del POST (errores), sobreescribir valores con los datos del POST para que no se pierdan
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($errores)) {
        $categoria_actual['nombre'] = $nombre;
        $categoria_actual['descripcion'] = $descripcion;
    }
}
?>

<div class="container mt-4">
    <h2 class="mb-4"><i class="bi bi-tags"></i> Gestión de Categorías</h2>

    <!-- Botón para volver al menú principal -->
    <a href="http://localhost:8080/nolo_food_inventory/" class="btn btn-success mb-3">
        <i class="bi bi-arrow-left-circle-fill"></i> Volver al Menú Principal
    </a>

    <!-- Mostrar mensajes de éxito -->
    <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill"></i> <?= $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Mostrar mensajes de error -->
    <?php if (!empty($errores)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i> 
            <ul class="mb-0">
                <?php foreach ($errores as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['mensaje_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i> <?= $_SESSION['mensaje_error']; unset($_SESSION['mensaje_error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Formulario de Agregar / Editar Categoría -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-success text-white">
            <i class="bi bi-plus-circle-fill"></i> <?= $categoria_actual ? 'Editar' : 'Agregar' ?> Categoría
        </div>
        <div class="card-body">
            <form method="POST" novalidate>
                <?php if ($categoria_actual): ?>
                    <input type="hidden" name="id" value="<?= $categoria_actual['id_categoria'] ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label for="nombre" class="form-label">
                        <i class="bi bi-tag-fill"></i> Nombre de la Categoría
                    </label>
                    <input type="text" class="form-control" id="nombre" name="nombre"
                        value="<?= htmlspecialchars($categoria_actual['nombre'] ?? '') ?>" required maxlength="100">
                </div>

                <div class="mb-3">
                    <label for="descripcion" class="form-label">
                        <i class="bi bi-card-text"></i> Descripción
                    </label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3" maxlength="255"><?= htmlspecialchars($categoria_actual['descripcion'] ?? '') ?></textarea>
                </div>

                <div class="mt-4">
                    <button type="submit" name="<?= $categoria_actual ? 'actualizar' : 'crear' ?>" class="btn btn-success">
                        <i class="bi bi-floppy-fill"></i> <?= $categoria_actual ? 'Actualizar' : 'Guardar' ?>
                    </button>

                    <?php if ($categoria_actual): ?>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle-fill"></i> Cancelar
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Listado -->
    <div class="card">
        <div class="card-header bg-success text-white">
            <i class="bi bi-list-ul"></i> Lista de Categorías
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th><i class="bi bi-hash"></i> ID</th>
                            <th><i class="bi bi-type"></i> Nombre</th>
                            <th><i class="bi bi-card-text"></i> Descripción</th>
                            <th><i class="bi bi-gear-fill"></i> Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias as $cat): ?>
                            <tr>
                                <td><?= $cat['id_categoria'] ?></td>
                                <td><?= htmlspecialchars($cat['nombre']) ?></td>
                                <td><?= $cat['descripcion'] ? htmlspecialchars(substr($cat['descripcion'], 0, 50)) . (strlen($cat['descripcion']) > 50 ? '...' : '') : '-' ?></td>
                                <td>
                                    <a href="index.php?editar=<?= $cat['id_categoria'] ?>" class="btn btn-sm btn-success" title="Editar">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>

                                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalEliminar" data-id="<?= $cat['id_categoria'] ?>" title="Eliminar">
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

<!-- Modal de Confirmación de Eliminación  -->
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
                ¿Estás seguro de que deseas eliminar esta categoría? Esta acción no se puede deshacer.
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
