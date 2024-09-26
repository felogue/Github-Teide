<?php
require_once 'includes/funciones.php';
include 'includes/header.php';

$pdo = connectDB();

// Obtener el nombre del archivo actual sin la extensión
$paginaActual = basename($_SERVER['PHP_SELF'], ".php");

// Incluir la hoja de estilos específica para la página actual
if ($paginaActual == 'ingredientes') {
    echo '<link rel = "stylesheet" href="css/ingredientes.css">';
}

// Obtener opciones para los selectores
$unidades_medida = getOptions($pdo, 'unidad_medida', 'id', 'nombre');
$familias = getOptions($pdo, 'familias', 'id', 'nombre');
$supermercados = getOptions($pdo, 'supermercado', 'id', 'nombre');


// Inicializar mensaje
$mensaje = 'null';

// Procesar formulario añadir ingredientes
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
            case 'update':
                $nombre = $_POST['nombre_ingrediente'];
                $precio = $_POST['precio_compra'];
                $cantidad = $_POST['cantidad_compra'];
                $unidad = $_POST['IDUnidad_medida'];
                $familia = $_POST['id_familia'];
                $supermercado = $_POST['id_supermercado'];
                $coste = $precio * $cantidad;
                $id = isset($_POST['id']) ? $_POST['id'] : null;


                // Verificar si el ingrediente ya existe
                if (ingredienteExiste($pdo, $nombre, $id)) {
                    $mensaje = ["type" => "error", "text" => "El ingrediente '$nombre' ya existe en la base de datos."];
                } else {
                    if ($_POST['action'] == 'create') {
                        $sql = "INSERT INTO ingredientes (nombre_ingrediente, precio_compra, cantidad_compra, IDUnidad_medida, id_familia, id_supermercado, coste_compra) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)";
                        $params = [$nombre, $precio, $cantidad, $unidad, $familia, $supermercado, $coste];
                        $mensaje = ["type" => "success", "text" => "Ingrediente añadido con éxito."];
                    } else {
                        $sql = "UPDATE ingredientes SET 
                                nombre_ingrediente = ?, precio_compra = ?, cantidad_compra = ?, 
                                IDUnidad_medida = ?, id_familia = ?, id_supermercado = ?, coste_compra = ? 
                                WHERE IDIngrediente = ?";
                        $params = [$nombre, $precio, $cantidad, $unidad, $familia, $supermercado, $coste, $id];
                        $mensaje = ["type" => "success", "text" => "Ingrediente actualizado con éxito."];
                    }

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                }
                break;

            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM ingredientes WHERE IDIngrediente = ?");
                $stmt->execute([$_POST['id']]);
                $mensaje = ["type" => "success", "text" => "Ingrediente eliminado con éxito."];
                break;
        }
    }
}
// Fin formulario añadir ingredientes

// Convertir el mensaje a JSON para pasarlo al JavaScript
$mensajeJSON = json_encode($mensaje);

// Obtener ingrediente para editar
$ingrediente_editar = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM ingredientes WHERE IDIngrediente = ?");
    $stmt->execute([$_GET['edit']]);
    $ingrediente_editar = $stmt->fetch();
}

// Configuración de paginación
$items_per_page = 10; // Número de ingredientes por página
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($current_page - 1) * $items_per_page;


// Lógica de búsqueda
$where = [];
$params = [];
if (isset($_GET['buscar_nombre']) && !empty($_GET['buscar_nombre'])) {
    $where[] = "nombre_ingrediente LIKE :param1";
    $params[':param1'] = "%" . $_GET['buscar_nombre'] . "%";
}
if (isset($_GET['buscar_familia']) && !empty($_GET['buscar_familia'])) {
    $where[] = "id_familia = :param2";
    $params[':param2'] = $_GET['buscar_familia'];
}


// Consulta para contar el total de ingredientes
$count_sql = "SELECT COUNT(DISTINCT i.nombre_ingrediente) as total FROM ingredientes i";
if (!empty($where)) {
    $count_sql .= " WHERE " . implode(" AND ", $where);
}

$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_items = $count_stmt->fetchColumn();

// Calcular el total de páginas
$total_pages = ($total_items > 0) ? ceil($total_items / $items_per_page) : 1;

// Asegurarse de que la página actual no exceda el total de páginas
$current_page = min($current_page, $total_pages);


// Consulta principal para obtener los ingredientes
$sql = "SELECT DISTINCT i.*, u.nombre AS unidad, f.nombre AS familia, s.nombre AS supermercado 
        FROM ingredientes i 
        LEFT JOIN unidad_medida u ON i.IDUnidad_medida = u.id
        LEFT JOIN familias f ON i.id_familia = f.id
        LEFT JOIN supermercado s ON i.id_supermercado = s.id";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " GROUP BY i.nombre_ingrediente ORDER BY i.nombre_ingrediente LIMIT :offset, :items_per_page";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':items_per_page', $items_per_page, PDO::PARAM_INT);


// Vincular los parámetros de búsqueda si existen
if (!empty($params)) {
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
}

$stmt->execute();
$ingredientes = $stmt->fetchAll();

?>

<!-- Formulario de búsqueda de ingredientes -->
<div class="flexbox-ingredientes">
    <div class="card">
        <h2>Buscar Ingredientes</h2>
        <form action="" method="GET">
            <div class="card-content">
                <label for="buscar_nombre">Nombre del ingrediente:</label>
                <input type="text" id="buscar_nombre" name="buscar_nombre" value="<?= htmlspecialchars($_GET['buscar_nombre'] ?? '') ?>">
            </div>
            <div class="card-content">
                <label for="buscar_familia">Familia:</label>
                <select id="buscar_familia" name="buscar_familia">
                    <option value="">Todas las familias</option>
                    <?php foreach ($familias as $id => $nombre) : ?>
                        <option value="<?= $id ?>" <?= (isset($_GET['buscar_familia']) && $_GET['buscar_familia'] == $id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($nombre) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="card-content">
                <button type="submit">Buscar</button>
                <?php if (!empty($_GET['buscar_nombre']) || !empty($_GET['buscar_familia'])) : ?>
                    <a href="?" class="btn btn-edit">Mostrar todos</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <!-- Fin formulario de búsqueda ingredientes -->

    <!-- Añadir o editar ingredientes -->
    <div class="card">
        <h2><?= $ingrediente_editar ? 'Editar' : 'Nuevo' ?> Ingrediente</h2>
        <form action="" method="POST">
            <input type="hidden" name="action" value="<?= $ingrediente_editar ? 'update' : 'create' ?>">
            <?php if ($ingrediente_editar) : ?>
                <input type="hidden" name="id" value="<?= $ingrediente_editar['IDIngrediente'] ?>">
            <?php endif; ?>

            <div class="card-content">
                <label for="nombre_ingrediente">Nombre:</label>
                <input type="text" id="nombre_ingrediente" name="nombre_ingrediente" autofocus required value="<?= $ingrediente_editar ? htmlspecialchars($ingrediente_editar['nombre_ingrediente']) : '' ?>">
            </div>

            <div class="card-content">
                <label for="precio_compra">Precio de compra:</label>
                <input type="number" step="0.01" id="precio_compra" name="precio_compra" required value="<?= $ingrediente_editar ? $ingrediente_editar['precio_compra'] : '' ?>">
            </div>

            <div class="card-content">
                <label for="cantidad_compra">Cantidad de compra:</label>
                <input type="number" step="0.01" id="cantidad_compra" name="cantidad_compra" required value="<?= $ingrediente_editar ? $ingrediente_editar['cantidad_compra'] : '' ?>">
            </div>

            <div class="card-content">
                <label for="IDUnidad_medida">Unidad de medida:</label>
                <select id="IDUnidad_medida" name="IDUnidad_medida" required>
                    <?php foreach ($unidades_medida as $id => $nombre) : ?>
                        <option value="<?= $id ?>" <?= ($ingrediente_editar && $ingrediente_editar['IDUnidad_medida'] == $id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($nombre) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="card-content">
                <label for="id_familia">Familia:</label>
                <select id="id_familia" name="id_familia" required>
                    <?php foreach ($familias as $id => $nombre) : ?>
                        <option value="<?= $id ?>" <?= ($ingrediente_editar && $ingrediente_editar['id_familia'] == $id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($nombre) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="card-content">
                <label for="id_supermercado">Supermercado:</label>
                <select id="id_supermercado" name="id_supermercado" required>
                    <?php foreach ($supermercados as $id => $nombre) : ?>
                        <option value="<?= $id ?>" <?= ($ingrediente_editar && $ingrediente_editar['id_supermercado'] == $id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($nombre) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="card-content">
                <button type="submit"><?= $ingrediente_editar ? 'Actualizar' : 'Crear' ?> Ingrediente</button>
            </div>
        </form>
    </div>
</div>
<!-- Fin añadir o editar ingredientes -->

<!-- Listado de ingredietnes -->
<div class="card-listado">
    <h2>Lista de Ingredientes</h2>
    <?php if (empty($ingredientes)) : ?>
        <p>No se encontraron ingredientes.</p>
    <?php else : ?>
        <table>
            <thead>
            <tr>
                <th>Nombre</th>
                <th>Precio de compra</th>
                <th>Cantidad de compra</th>
                <th>Unidad de medida</th>
                <th>Familia</th>
                <th>Supermercado</th>
                <th>Coste de compra</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <?php foreach ($ingredientes as $row) : ?>
                <tr>
                    <td data-label="Nombre del ingrediente"><?= htmlspecialchars($row['nombre_ingrediente']) ?></td>
                    <td data-label="Precio de compra"><?= $row['precio_compra'] ?></td>
                    <td data-label="Cantidad de compra"><?= $row['cantidad_compra'] ?></td>
                    <td data-label="Unidad"><?= htmlspecialchars($row['unidad']) ?></td>
                    <td data-label="Familia"><?= htmlspecialchars($row['familia']) ?></td>
                    <td data-label="Supermercado"><?= htmlspecialchars($row['supermercado']) ?></td>
                    <td data-label="Coste de compra"><?= $row['coste_compra'] ?></td>
                    <td>
                        <a href="?edit=<?= $row['IDIngrediente'] ?>" class="btn btn-edit">Editar</a>
                        <form action="" method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $row['IDIngrediente'] ?>">
                            <button type="submit" class="btn btn-delete" onclick="return">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <div class="card">
            <div class="card-content">
        Mostrando <?= count($ingredientes) ?> de <?= $total_items ?> ingredientes
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Paginación -->
<div class="paginacion">
    <?php
    $paginacion = generarPaginacion($total_items, $items_per_page, $current_page, $_GET);
    echo $paginacion;
    ?>
</div>


<!-- Alerta  -->
<script>
    $(document).ready(function() {
        var mensaje = <?php echo $mensajeJSON; ?>;
        if (mensaje && mensaje.text) {
            Swal.fire({
                title: mensaje.type === 'error' ? 'Error' : 'Éxito',
                text: mensaje.text,
                icon: mensaje.type === 'error' ? 'error' : 'success',
                confirmButtonText: 'Aceptar'
            });
        }

        // Agregar confirmación para eliminar
        $('.btn-delete').on('click', function(e) {
            e.preventDefault();
            var form = $(this).closest('form');
            Swal.fire({
                title: 'Confirmar eliminación',
                text: '¿Estás seguro de que quieres eliminar este ingrediente?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>

<?php include 'includes/footer.php'; ?>