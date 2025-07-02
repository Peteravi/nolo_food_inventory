<?php
session_start();
include("../includes/header.php");
include("../db/conexion.php");

// Inicializar variables para valores antiguos en caso de error
$nombre = $descripcion = $id_categoria = $unidad = $precio = $stock = $minimo = "";
$errores = [];

// Obtener categorías
try {
    $categorias = $pdo->query("SELECT id_categoria, nombre FROM categorias ORDER BY nombre")->fetchAll();
} catch (Exception $e) {
    die("Error al obtener categorías: " . htmlspecialchars($e->getMessage()));
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recoger datos con trim para evitar espacios extras
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $id_categoria = $_POST['id_categoria'] ?? '';
    $unidad = trim($_POST['unidad_medida'] ?? '');
    $precio = $_POST['precio_unitario'] ?? '';
    $stock = $_POST['stock_actual'] ?? '';
    $minimo = $_POST['stock_minimo'] ?? '';

    // Validaciones básicas
    if ($nombre === '') {
        $errores[] = "El nombre es obligatorio.";
    }
    if ($id_categoria === '' || !filter_var($id_categoria, FILTER_VALIDATE_INT)) {
        $errores[] = "La categoría seleccionada no es válida.";
    }
    if ($unidad === '') {
        $errores[] = "La unidad de medida es obligatoria.";
    }
    if ($precio === '' || !is_numeric($precio) || $precio < 0) {
        $errores[] = "El precio unitario debe ser un número positivo.";
    }
    if ($stock === '' || !is_numeric($stock) || $stock < 0) {
        $errores[] = "El stock actual debe ser un número positivo.";
    }
    if ($minimo !== '' && (!is_numeric($minimo) || $minimo < 0)) {
        $errores[] = "El stock mínimo debe ser un número positivo o estar vacío.";
    }

    try {
        if (isset($_POST['crear']) && empty($errores)) {
            $sql = "INSERT INTO articulos (nombre, descripcion, id_categoria, unidad_medida, precio_unitario, stock_actual, stock_minimo)
                    VALUES (:nombre, :descripcion, :id_categoria, :unidad, :precio, :stock, :minimo)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'id_categoria' => $id_categoria,
                'unidad' => $unidad,
                'precio' => $precio,
                'stock' => $stock,
                'minimo' => $minimo !== '' ? $minimo : null
            ]);

            $_SESSION['mensaje'] = "Artículo creado correctamente";
            header("Location: index.php");
            exit();
        } elseif (isset($_POST['actualizar']) && empty($errores)) {
            $id = $_POST['id'] ?? '';
            if (!filter_var($id, FILTER_VALIDATE_INT)) {
                $errores[] = "ID de artículo no válido.";
            } else {
                $sql = "UPDATE articulos 
                        SET nombre = :nombre, descripcion = :descripcion, id_categoria = :id_categoria, unidad_medida = :unidad, 
                            precio_unitario = :precio, stock_actual = :stock, stock_minimo = :minimo
                        WHERE id_articulo = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'id' => $id,
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'id_categoria' => $id_categoria,
                    'unidad' => $unidad,
                    'precio' => $precio,
                    'stock' => $stock,
                    'minimo' => $minimo !== '' ? $minimo : null
                ]);

                $_SESSION['mensaje'] = "Artículo actualizado correctamente";
                header("Location: index.php");
                exit();
            }
        }
    } catch (Exception $e) {
        $errores[] = "Error en la base de datos: " . htmlspecialchars($e->getMessage());
    }
}

// Eliminar artículo
if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    if (filter_var($id, FILTER_VALIDATE_INT)) {
        try {
            $stmt = $pdo->prepare("DELETE FROM articulos WHERE id_articulo = :id");
            $stmt->execute(['id' => $id]);
            $_SESSION['mensaje'] = "Artículo eliminado correctamente";
        } catch (Exception $e) {
            $_SESSION['mensaje'] = "Error al eliminar artículo: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $_SESSION['mensaje'] = "ID de artículo no válido para eliminar.";
    }
    header("Location: index.php");
    exit();
}

// Listar artículos
try {
    $sql = "SELECT a.*, c.nombre AS nombre_categoria FROM articulos a 
            LEFT JOIN categorias c ON a.id_categoria = c.id_categoria 
            ORDER BY a.fecha_creacion DESC";
    $articulos = $pdo->query($sql)->fetchAll();
} catch (Exception $e) {
    die("Error al obtener artículos: " . htmlspecialchars($e->getMessage()));
}

// Obtener artículo para edición
$articulo_actual = null;
if (isset($_GET['editar'])) {
    $id = $_GET['editar'];
    if (filter_var($id, FILTER_VALIDATE_INT)) {
        $stmt = $pdo->prepare("SELECT * FROM articulos WHERE id_articulo = :id");
        $stmt->execute(['id' => $id]);
        $articulo_actual = $stmt->fetch();

        if ($articulo_actual) {
            // Para mantener valores en formulario en caso de error
            $nombre = $articulo_actual['nombre'];
            $descripcion = $articulo_actual['descripcion'];
            $id_categoria = $articulo_actual['id_categoria'];
            $unidad = $articulo_actual['unidad_medida'];
            $precio = $articulo_actual['precio_unitario'];
            $stock = $articulo_actual['stock_actual'];
            $minimo = $articulo_actual['stock_minimo'];
        }
    } else {
        $_SESSION['mensaje'] = "ID de artículo no válido para editar.";
        header("Location: index.php");
        exit();
    }
}
?>

<div class="container mt-4">
    <h2 class="mb-4"><i class="bi bi-box"></i> Gestión de Artículos</h2>

    <a href="http://localhost:8080/nolo_food_inventory/" class="btn btn-success mb-3">
        <i class="bi bi-arrow-left-circle-fill"></i> Volver al Menú Principal
    </a>

    <?php if (!empty($errores)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errores as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill"></i> <?= $_SESSION['mensaje'];
                                                    unset($_SESSION['mensaje']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Formulario -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-success text-white">
            <i class="bi bi-pencil-square"></i> <?= $articulo_actual ? 'Editar Artículo' : 'Agregar Artículo' ?>
        </div>
        <div class="card-body">
            <form method="POST" novalidate>
                <?php if ($articulo_actual): ?>
                    <input type="hidden" name="id" value="<?= $articulo_actual['id_articulo'] ?>">
                <?php endif; ?>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label"><i class="bi bi-tag-fill"></i> Nombre</label>
                        <input type="text" class="form-control" name="nombre" value="<?= htmlspecialchars($nombre ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="bi bi-rulers"></i> Unidad de Medida</label>
                        <input type="text" class="form-control" name="unidad_medida" value="<?= htmlspecialchars($unidad ?? '') ?>" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label"><i class="bi bi-text-left"></i> Descripción</label>
                        <textarea class="form-control" name="descripcion" rows="2"><?= htmlspecialchars($descripcion ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="bi bi-tags-fill"></i> Categoría</label>
                        <select class="form-select" name="id_categoria" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id_categoria'] ?>" <?= (isset($id_categoria) && $cat['id_categoria'] == $id_categoria) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="bi bi-currency-dollar"></i> Precio Unitario</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="precio_unitario" value="<?= htmlspecialchars($precio ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="bi bi-boxes"></i> Stock Actual</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="stock_actual" value="<?= htmlspecialchars($stock ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="bi bi-exclamation-circle-fill"></i> Stock Mínimo</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="stock_minimo" value="<?= htmlspecialchars($minimo ?? '') ?>">
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" name="<?= $articulo_actual ? 'actualizar' : 'crear' ?>" class="btn btn-success">
                        <i class="bi bi-save-fill"></i> <?= $articulo_actual ? 'Actualizar' : 'Guardar' ?>
                    </button>
                    <?php if ($articulo_actual): ?>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle-fill"></i> Cancelar
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de Artículos -->
    <div class="card">
        <div class="card-header bg-success text-white">
            <i class="bi bi-box-seam"></i> Lista de Artículos
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th><i class="bi bi-hash"></i> ID</th>
                            <th><i class="bi bi-tag-fill"></i> Nombre</th>
                            <th><i class="bi bi-card-text"></i> Descripción</th>
                            <th><i class="bi bi-bookmark"></i> Categoría</th>
                            <th><i class="bi bi-rulers"></i> Unidad</th>
                            <th><i class="bi bi-currency-dollar"></i> Precio</th>
                            <th><i class="bi bi-boxes"></i> Stock</th>
                            <th><i class="bi bi-exclamation-triangle"></i> Mínimo</th>
                            <th><i class="bi bi-calendar-event"></i> Fecha</th>
                            <th><i class="bi bi-gear-fill"></i> Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($articulos as $art): ?>
                            <tr>
                                <td><?= $art['id_articulo'] ?></td>
                                <td><?= htmlspecialchars($art['nombre']) ?></td>
                                <td><?= htmlspecialchars(substr($art['descripcion'], 0, 50)) ?><?= strlen($art['descripcion']) > 50 ? '...' : '' ?></td>
                                <td><?= htmlspecialchars($art['nombre_categoria'] ?? 'Sin categoría') ?></td>
                                <td><?= htmlspecialchars($art['unidad_medida']) ?></td>
                                <td>$<?= number_format($art['precio_unitario'], 2) ?></td>
                                <td><?= htmlspecialchars($art['stock_actual']) ?></td>
                                <td><?= htmlspecialchars($art['stock_minimo']) ?></td>
                                <td><?= date('Y-m-d', strtotime($art['fecha_creacion'])) ?></td>
                                <td>
                                    <a href="index.php?editar=<?= $art['id_articulo'] ?>" class="btn btn-sm btn-success" title="Editar">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalEliminar" data-id="<?= $art['id_articulo'] ?>" title="Eliminar">
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
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                ¿Estás seguro de que deseas eliminar este artículo? Esta acción no se puede deshacer.
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