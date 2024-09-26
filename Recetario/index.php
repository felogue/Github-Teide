<?php
require_once 'includes/funciones.php';
include 'includes/header.php';

$pdo = connectDB();

/*
  Función getOptions utilizada para llenar 
  los menús desplegables de la interfaz de búsqueda.
*/
$tipos_dieta = getOptions($pdo, 'tipos_dieta', 'id', 'nombre');
$categorias = getOptions($pdo, 'categorias', 'id', 'nombre');

// Configuración de paginación
$recetas_por_pagina = 5; // Número de recetas por página
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1; // Página actual
$offset = ($pagina_actual - 1) * $recetas_por_pagina; // Calcular el offset para la consulta


// Procesar la búsqueda si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $where_clauses = []; // Arreglo para almacenar las cláusulas WHERE
    $params = [];  // Arreglo para almacenar los parámetros de la consulta

    // Agregar condiciones según los valores del formulario
    if (!empty($_POST['nombre_receta'])) {
        $where_clauses[] = "r.nombre_receta LIKE ?";
        $params[] = '%' . $_POST['nombre_receta'] . '%';
    }
    if (!empty($_POST['tipo_dieta'])) {
        $where_clauses[] = "r.id_tipos_dieta = ?";
        $params[] = $_POST['tipo_dieta'];
    }
    if (!empty($_POST['dificultad'])) {
        $where_clauses[] = "r.dificultad = ?";
        $params[] = $_POST['dificultad'];
    }
    if (!empty($_POST['tiempo_preparacion'])) {
        $where_clauses[] = "r.tiempo_preparacion <= ?";
        $params[] = $_POST['tiempo_preparacion'];
    }
    if (!empty($_POST['categoria'])) {
        $where_clauses[] = "r.id_categorias = ?";
        $params[] = $_POST['categoria'];
    }
    if (!empty($_POST['coste_max'])) {
        $where_clauses[] = "r.coste_total <= ?";
        $params[] = $_POST['coste_max'];
    }

    // Combinar todas las cláusulas WHERE
    $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
} else {
    $where_sql = '';
    $params = [];
}

// Consulta para contar el total de recetas
$sql_count = "SELECT COUNT(*) FROM recetas r $where_sql";
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_recetas = $stmt_count->fetchColumn(); // Obtener el total de recetas

$total_paginas = ceil($total_recetas / $recetas_por_pagina); // Calcular el total de páginas


// Consulta principal con paginación
$sql = "SELECT r.IDReceta, r.nombre_receta, td.nombre AS tipo_dieta, r.raciones, r.tiempo_preparacion, 
               r.dificultad, c.nombre AS categoria, tp.nombre AS tipo_plato, r.coste_total, r.imagen
        FROM recetas r
        LEFT JOIN tipos_dieta td ON r.id_tipos_dieta = td.id
        LEFT JOIN categorias c ON r.id_categorias = c.id
        LEFT JOIN tipos_plato tp ON r.id_tipos_plato = tp.id
        $where_sql
        ORDER BY r.nombre_receta
        LIMIT $recetas_por_pagina OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$recetas = $stmt->fetchAll();  // fetchAll() Devuelve un array que contiene todas las filas del conjunto de resultados

?>

<!-- Buscador recetas -->
 
<div class="card">
    <h2>Buscador de Recetas</h2>
    <form action="" method="POST">
        <div class="card-content">
            <label for="nombre_receta">Nombre de la receta:</label>
            <input type="text" id="nombre_receta" name="nombre_receta" autofocus value="<?= htmlspecialchars($_POST['nombre_receta'] ?? '') ?>">
        </div>

        <div class="card-content">
            <label for="tipo_dieta">Tipo de dieta:</label>
            <select id="tipo_dieta" name="tipo_dieta">
                <option value="">Todos</option>
                <?php foreach ($tipos_dieta as $id => $nombre) : ?>
                    <option value="<?= $id ?>" <?= (isset($_POST['tipo_dieta']) && $_POST['tipo_dieta'] == $id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($nombre) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="card-content">
            <label for="dificultad">Dificultad:</label>
            <select id="dificultad" name="dificultad">
                <option value="">Todas</option>
                <option value="Baja" <?= (isset($_POST['dificultad']) && $_POST['dificultad'] == 'Baja') ? 'selected' : '' ?>>Baja</option>
                <option value="Media" <?= (isset($_POST['dificultad']) && $_POST['dificultad'] == 'Media') ? 'selected' : '' ?>>Media</option>
                <option value="Alta" <?= (isset($_POST['dificultad']) && $_POST['dificultad'] == 'Alta') ? 'selected' : '' ?>>Alta</option>
            </select>
        </div>

        <div class="card-content">
            <label for="tiempo_preparacion">Tiempo máximo de preparación (minutos):</label>
            <input type="number" id="tiempo_preparacion" name="tiempo_preparacion" value="<?= htmlspecialchars($_POST['tiempo_preparacion'] ?? '') ?>">
        </div>

        <div class="card-content">
            <label for="categoria">Categoría:</label>
            <select id="categoria" name="categoria">
                <option value="">Todas</option>
                <?php foreach ($categorias as $id => $nombre) : ?>
                    <option value="<?= $id ?>" <?= (isset($_POST['categoria']) && $_POST['categoria'] == $id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($nombre) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="card-content">
            <label for="coste_max">Coste máximo:</label>
            <input type="number" step="0.01" id="coste_max" name="coste_max" value="<?= htmlspecialchars($_POST['coste_max'] ?? '') ?>">
        </div>

        <div class="card-content">
            <button type="submit">Buscar</button>
        </div>
    </form>
</div>
<!-- Fin buscador recetas -->

<!-- Añadir nueva receta -->
<div class="card">
    <h2>Nueva Receta</h2>
    <a href="nueva_receta.php" class="btn btn-edit">Añadir Nueva Receta</a>
</div>

<!-- Listado de recetas -->
<div class="card-listado">
<h2>Listado de Recetas</h2>
<table>
    <thead>
        <tr>
            <th>Imagen</th>
            <th>Nombre de la Receta</th>
            <th>Tipo de Dieta</th>
            <th>Raciones</th>
            <th>Tiempo de Preparación</th>
            <th>Dificultad</th>
            <th>Categoría</th>
            <th>Tipo de Plato</th>
            <th>Coste Total</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>

        <?php foreach ($recetas as $receta) { ?>

            <tr>
                <td class="celda-imagen">
                    <img src="<?= htmlspecialchars($receta['imagen']) ?>" alt="<?= htmlspecialchars($receta['nombre_receta']) ?>" class="receta-thumbnail">
            </td>
                <td data-label="Nombre de la Receta"><?= htmlspecialchars($receta['nombre_receta']) ?></td>

                <td data-label="Tipo de Dieta"> <?= htmlspecialchars($receta['tipo_dieta']) ?></td>
                <td data-label="Raciones"> <?= $receta['raciones'] ?></td>
                <td data-label="Tiempo de Preparación"> <?= htmlspecialchars($receta['tiempo_preparacion']) ?></td>
                <td data-label="Dificultad"> <?= htmlspecialchars($receta['dificultad']) ?></td>
                <td data-label="Categoría"> <?= htmlspecialchars($receta['categoria']) ?></td>
                <td data-label="Tipo de Plato"> <?= htmlspecialchars($receta['tipo_plato']) ?></td>
                <td> <?= number_format($receta['coste_total'] ?? 0, 2) . " € " ?></td>
                <td data-label="Acciones" class="acciones">
                    <a href="detalle_receta.php?id=<?= $receta['IDReceta'] ?>" class="btn btn-view">Ver detalle</a>
                    <a href="editar_receta.php?id=<?= $receta['IDReceta'] ?>" class="btn btn-edit">Editar</a>
                    <a href="#" onclick="confirmarEliminar(<?= $receta['IDReceta'] ?>, '<?= htmlspecialchars($receta['nombre_receta'], ENT_QUOTES) ?>')" class="btn btn-delete">Eliminar</a>
                </td>
            </tr>
        <?php } ?>

    </tbody>
</table>

<!-- Fin del listado de recetas -->
</div>

<?php

?>

<!-- paginación -->
<div class="paginacion">
    <?php if ($pagina_actual > 1) : ?>
        <!-- El símbolo menor que ?= es una forma abreviada de las etiquetas de apertura y cierre de scripts de PHP. Es equivalente a las etiquetas estándar -->
        <a href="?pagina=<?= $pagina_actual - 1 ?>">&laquo; Anterior</a>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $total_paginas; $i++) : ?>
        <?php if ($i == $pagina_actual) : ?>
            <span class="pagina-actual"><?= $i ?></span>
        <?php else : ?>
            <a href="?pagina=<?= $i ?>"><?= $i ?></a>
        <?php endif; ?>
    <?php endfor; ?>

    <?php if ($pagina_actual < $total_paginas) : ?>
        <a href="?pagina=<?= $pagina_actual + 1 ?>">Siguiente &raquo;</a>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($stmt->rowCount() == 0 && $_SERVER['REQUEST_METHOD'] == 'POST'): ?>
        Swal.fire({
            icon: 'info',
            title: 'Sin resultados',
            text: 'No se encontraron recetas que coincidan con los criterios de búsqueda.',
            confirmButtonText: 'OK'
        });
    <?php endif; ?>

    <?php if (isset($_GET['mensaje'])): ?>
        Swal.fire({
            icon: 'success',
            title: 'Éxito',
            text: '<?php echo htmlspecialchars($_GET['mensaje']); ?>',
            confirmButtonText: 'OK'
        });
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '<?php echo htmlspecialchars($_GET['error']); ?>',
            confirmButtonText: 'OK'
        });
    <?php endif; ?>
});
</script>


<?php include 'includes/footer.php'; ?>