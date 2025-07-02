<?php include("includes/header.php"); ?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card welcome-card animate-card">
            <div class="card-header">
                <h2 class="mb-0">
                    <i class="bi bi-house-door"></i> Bienvenido al Sistema de Inventario
                </h2>
            </div>
            <div class="card-body">
                <p class="lead text-muted">
                    <i class="bi bi-info-circle"></i> Usa el menú para acceder a los módulos:
                </p>
                
                <ul class="list-group module-list animate-modules">
                    <li class="list-group-item">
                        <a href="productos/" class="d-flex justify-content-between align-items-center text-decoration-none text-dark">
                            <div>
                                <i class="bi bi-box-seam"></i> Gestión de Artículos
                                <small>Administra tu inventario de productos</small>
                            </div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                    
                    <li class="list-group-item">
                        <a href="categorias/" class="d-flex justify-content-between align-items-center text-decoration-none text-dark">
                            <div>
                                <i class="bi bi-tags"></i> Gestión de Categorías
                                <small>Organiza tus productos por categorías</small>
                            </div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                    
                    <li class="list-group-item">
                        <a href="movimientosdeinventario/" class="d-flex justify-content-between align-items-center text-decoration-none text-dark">
                            <div>
                                <i class="bi bi-arrow-left-right"></i> Gestión de Movimientos
                                <small>Registra entradas y salidas de inventario</small>
                            </div>
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
                
                <div class="alert tip-alert mt-4 animate-tip">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-lightbulb"></i>
                        <div>
                            <h5>Consejo rápido</h5>
                            <p class="mb-0">Recuerda actualizar tu inventario después de cada movimiento para mantener la precisión de tus datos.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>