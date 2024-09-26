<?php
require_once 'includes/funciones.php';
include 'includes/header.php';

$pdo = connectDB();

// Obtener opciones para los selectores
$tipos_dieta = getOptions($pdo, 'tipos_dieta', 'id', 'nombre');
$categorias = getOptions($pdo, 'categorias', 'id', 'nombre');
$tipos_plato = getOptions($pdo, 'tipos_plato', 'id', 'nombre');
$ingredientes = getOptions($pdo, 'ingredientes', 'IDIngrediente', 'nombre_ingrediente');

$result = null;

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();

        // Insertar la nueva receta
        $sql = "INSERT INTO recetas (nombre_receta, descripcion, raciones, tiempo_preparacion, dificultad, 
                imagen, fuente, instrucciones, notas, id_tipos_dieta, id_categorias, id_tipos_plato) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['nombre_receta'],
            $_POST['descripcion'],
            $_POST['raciones'],
            $_POST['tiempo_preparacion'],
            $_POST['dificultad'],
            $_POST['imagen'],
            $_POST['fuente'],
            $_POST['instrucciones'],
            $_POST['notas'],
            $_POST['id_tipos_dieta'],
            $_POST['id_categorias'],
            $_POST['id_tipos_plato']
        ]);

        $id_receta = $pdo->lastInsertId();

        // Insertar los ingredientes de la receta
        $sql_ingrediente = "INSERT INTO receta_ingredientes (IDReceta, IDIngrediente, cantidad) VALUES (?, ?, ?)";
        $stmt_ingrediente = $pdo->prepare($sql_ingrediente);

        foreach ($_POST['ingredientes'] as $ingrediente) {
            $stmt_ingrediente->execute([$id_receta, $ingrediente['id'], $ingrediente['cantidad']]);
        }

        $pdo->commit();
        $result = ['success' => true, 'message' => 'Receta añadida con éxito.'];
    } catch (Exception $e) {
        $pdo->rollBack();
        $result = ['success' => false, 'message' => 'Error al añadir la receta: ' . $e->getMessage()];
    }
}
?>

<!-- Crear nueva receta -->
<div class="card">
    <h2>Nueva Receta</h2>

    <form action="" method="POST" id="nueva-receta-form">
        <div class="card-content">
            <label for="nombre_receta">Nombre de la receta:</label>
            <input type="text" id="nombre_receta" name="nombre_receta" autofocus required>
        </div>

        <div class="card-content">
            <label for="descripcion">Descripción:</label>
            <textarea id="descripcion" name="descripcion"></textarea>
        </div>

        <div class="card-content">
            <label for="raciones">Raciones:</label>
            <input type="number" id="raciones" name="raciones" required>
        </div>

        <div class="card-content">
            <label for="tiempo_preparacion">Tiempo de Preparación (minutos):</label>
            <input type="number" id="tiempo_preparacion" name="tiempo_preparacion" required>
        </div>

        <div class="card-content">
            <label for="dificultad">Dificultad:</label>
            <select id="dificultad" name="dificultad" required>
                <option value="Baja">Baja</option>
                <option value="Media">Media</option>
                <option value="Alta">Alta</option>
            </select>
        </div>

        <div class="card-content">
            <label for="id_tipos_dieta">Tipo de Dieta:</label>
            <select id="id_tipos_dieta" name="id_tipos_dieta" required>
                <?php foreach ($tipos_dieta as $id => $nombre) : ?>
                    <option value="<?= $id ?>"><?= htmlspecialchars($nombre) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="card-content">
            <label for="id_categorias">Categoría:</label>
            <select id="id_categorias" name="id_categorias" required>
                <?php foreach ($categorias as $id => $nombre) : ?>
                    <option value="<?= $id ?>"><?= htmlspecialchars($nombre) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="card-content">
            <label for="id_tipos_plato">Tipo de Plato:</label>
            <select id="id_tipos_plato" name="id_tipos_plato" required>
                <?php foreach ($tipos_plato as $id => $nombre) : ?>
                    <option value="<?= $id ?>"><?= htmlspecialchars($nombre) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="card-content">
            <label for="imagen">URL de la imagen:</label>
            <input type="url" id="imagen" name="imagen">
        </div>

        <div class="card-content">
            <label for="fuente">Fuente:</label>
            <input type="text" id="fuente" name="fuente">
        </div>

        <div class="card-content">
            <label for="instrucciones">Instrucciones:</label>
            <textarea id="instrucciones" name="instrucciones" required></textarea>
        </div>

        <div class="card-content">
            <label for="notas">Notas:</label>
            <textarea id="notas" name="notas"></textarea>
        </div>

        <div class="card-content">
            <h3>Ingredientes:</h3>
            <div id="ingredientes-container">
                <div class="ingrediente-row">
                    <select name="ingredientes[0][id]" required>
                        <?php foreach ($ingredientes as $id => $nombre) : ?>
                            <option value="<?= $id ?>"><?= htmlspecialchars($nombre) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="card-content">

                    <input type="number" name="ingredientes[0][cantidad]" step="0.01" required placeholder="Cantidad">
                </div>
            </div>
        </div>
        <div class="card-content">
            <button type="button" id="agregar-ingrediente">Agregar otro ingrediente</button>
        </div>
        
        <div class="card-content">
            <button type="submit">Guardar Receta</button>
        </div>
    </form>
</div>
<!-- Fin crear nueva receta -->

<script>
    let ingredienteCount = 1;
    document.getElementById('agregar-ingrediente').addEventListener('click', function() {
        const container = document.getElementById('ingredientes-container');
        const newRow = document.createElement('div');
        newRow.className = 'ingrediente-row';
        newRow.innerHTML = `
        <select name="ingredientes[${ingredienteCount}][id]" required>
            <?php foreach ($ingredientes as $id => $nombre) : ?>
                <option value="<?= $id ?>"><?= htmlspecialchars($nombre) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="ingredientes[${ingredienteCount}][cantidad]" step="0.01" required placeholder="Cantidad">
    `;
        container.appendChild(newRow);
        ingredienteCount++;
    });

    // Alerta SweetAlert
    <?php if ($result !== null): ?>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: '<?php echo $result['success'] ? 'success' : 'error'; ?>',
                title: '<?php echo $result['success'] ? 'Éxito!' : 'Error'; ?>',
                text: '<?php echo addslashes($result['message']); ?>',
                confirmButtonText: 'OK'
            });
        });
    <?php endif; ?>

</script>

<?php include 'includes/footer.php'; ?>