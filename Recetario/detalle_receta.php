<?php
require_once 'includes/funciones.php';
include 'includes/header.php';

$pdo = connectDB();

// Obtener el nombre del archivo actual sin la extensión
$paginaActual = basename($_SERVER['PHP_SELF'], ".php");

// Incluir la hoja de estilos específica para la página actual
if ($paginaActual == 'detalle_receta') {
    echo '<link rel = "stylesheet" href="css/detalle.css">';
}


// Verificar si se proporcionó un ID de receta
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p>Error: No se especificó una receta válida.</p>";
    include 'includes/footer.php';
    exit;
}

$id_receta = $_GET['id'];

// Obtener los detalles de la receta
$sql = "SELECT r.*, td.nombre AS tipo_dieta, c.nombre AS categoria, tp.nombre AS tipo_plato
        FROM recetas r
        LEFT JOIN tipos_dieta td ON r.id_tipos_dieta = td.id
        LEFT JOIN categorias c ON r.id_categorias = c.id
        LEFT JOIN tipos_plato tp ON r.id_tipos_plato = tp.id
        WHERE r.IDReceta = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id_receta]);
$receta = $stmt->fetch();

if (!$receta) {
    echo "<p>Error: No se encontró la receta especificada.</p>";
    include 'includes/footer.php';
    exit;
}

// Obtener los ingredientes de la receta
$sql_ingredientes = "SELECT ri.cantidad, i.nombre_ingrediente, i.precio_compra, um.nombre AS unidad_medida
                     FROM receta_ingredientes ri
                     JOIN ingredientes i ON ri.IDIngrediente = i.IDIngrediente
                     JOIN unidad_medida um ON i.IDUnidad_medida = um.id
                     WHERE ri.IDReceta = ?";

$stmt_ingredientes = $pdo->prepare($sql_ingredientes);
$stmt_ingredientes->execute([$id_receta]);
$ingredientes = $stmt_ingredientes->fetchAll(PDO::FETCH_ASSOC);


// Calcular el coste total de la receta
$coste_total = 0;
foreach ($ingredientes as $ingrediente) {
    $coste_ingrediente = $ingrediente['cantidad'] * $ingrediente['precio_compra'];
    $ingrediente['coste_en_receta'] = $coste_ingrediente;
    $coste_total += $coste_ingrediente;
    // Guardar los cambios en una nueva array si es necesario
    $ingredientes_con_coste[] = $ingrediente;
}

// Actualizar el coste total en la base de datos
$sql_update_coste = "UPDATE recetas SET coste_total = ? WHERE IDReceta = ?";
$stmt_update_coste = $pdo->prepare($sql_update_coste);
$stmt_update_coste->execute([$coste_total, $id_receta]);

?>
<div class="card">
    <h2 class="receta-detalle"><?= htmlspecialchars($receta['nombre_receta']) ?></h2>

    <div class="receta-detalle">
        <img src="<?= htmlspecialchars($receta['imagen']) ?>" alt="<?= htmlspecialchars($receta['nombre_receta']) ?>">

        <table class="detalle-receta-especificacion">
            <tr>
                <th>ID</th>
                <td><?= $receta['IDReceta'] ?></td>
            </tr>
            <tr>
                <th>Tipo de dieta</th>
                <td><?= htmlspecialchars($receta['tipo_dieta']) ?></td>
            </tr>
            <tr>
            <th>Raciones</th>
                <td><?= $receta['raciones'] ?></td>
            </tr>
            <tr>
            <th>Tiempo de preparación (Minutos)</th>
                <td><?= htmlspecialchars($receta['tiempo_preparacion']) ?></td>
            </tr>
            <tr>
            <th>Dificultad</th>
                <td><?= htmlspecialchars($receta['dificultad']) ?></td>
            </tr>
            <tr>
            <th>Categoria</th>
                <td><?= htmlspecialchars($receta['categoria']) ?></td>
            </tr>
            <tr>
            <th>Tipo de Plato</th>
                <td><?= htmlspecialchars($receta['tipo_plato']) ?></td>
            </tr>
            <tr>
            <th>Coste total</th>
                <td><?= number_format($coste_total, 2) ?> €</td>
            </tr>
            <tr>
            <th>Fuente</th>
                <td><?= htmlspecialchars($receta['fuente']) ?></td>
            </tr>
        </table>

        <h3>Descripción:</h3>
        <p><?= nl2br(htmlspecialchars($receta['descripcion'])) ?></p>

        <h3>Instrucciones:</h3>
        <p><?= nl2br(htmlspecialchars($receta['instrucciones'])) ?></p>

        <h3>Notas:</h3>
        <p><?= nl2br(htmlspecialchars($receta['notas'])) ?></p>

        <h3>Ingredientes:</h3>
        <table class="detalle-receta-ingredientes">
            <thead>
                <tr class="encabezados">
                    <th>Nombre</th>
                    <th>Cantidad</th>
                    <th>Unidad de Medida</th>
                    <th>Precio de compra</th>
                    <th>Coste en la receta</th>
                </tr>
            </thead>
            <!-- Mostrar ingredientes -->
            <?php foreach ($ingredientes_con_coste as $ingrediente) : ?>
                <tr>
                    <td data-label="Nombre"><?= htmlspecialchars($ingrediente['nombre_ingrediente']) ?></td>
                    <td data-label="Cantidad"><?= $ingrediente['cantidad'] ?></td>
                    <td data-label="Unidad de Medida"><?= htmlspecialchars($ingrediente['unidad_medida']) ?></td>
                    <td data-label="Precio de compra"><?= number_format($ingrediente['precio_compra'], 2) ?> €</td>
                    <td data-label="Coste en la receta"><?= number_format($ingrediente['coste_en_receta'], 2) ?> €</td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <!-- <td data-label="Coste Total de la Receta:" colspan="4">Coste Total de la Receta:</th> -->
                <td data-label="Coste Total Receta"><?= number_format($coste_total, 2) ?> €</td>
            </tr>
        </table>
    </div>

</div>
<div class="card">
    <a href="index.php" class="button">Volver a la página principal</a>
</div>


<?php include 'includes/footer.php'; ?>