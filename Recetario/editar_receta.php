<?php
require_once 'includes/funciones.php';
include 'includes/header.php';

$pdo = connectDB();

// Obtener opciones para los selectores
$tipos_dieta = getOptions($pdo, 'tipos_dieta', 'id', 'nombre');
$categorias = getOptions($pdo, 'categorias', 'id', 'nombre');
$tipos_plato = getOptions($pdo, 'tipos_plato', 'id', 'nombre');
$ingredientes = getOptions($pdo, 'ingredientes', 'IDIngrediente', 'nombre_ingrediente');

// Verificar si se ha proporcionado un ID de receta
if (!isset($_GET['id'])) {
    echo "No se ha especificado una receta para editar.";
    exit;
}

$id_receta = $_GET['id'];

// Obtener los datos actuales de la receta
$stmt = $pdo->prepare("SELECT * FROM recetas WHERE IDReceta = ?");
$stmt->execute([$id_receta]);
$receta = $stmt->fetch();

if (!$receta) {
    echo "La receta especificada no existe.";
    exit;
}

// Obtener los ingredientes actuales de la receta
$stmt = $pdo->prepare("SELECT * FROM receta_ingredientes WHERE IDReceta = ?");
$stmt->execute([$id_receta]);
$ingredientes_actuales = $stmt->fetchAll();

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo->beginTransaction();

        // Actualizar la receta
        $sql = "UPDATE recetas SET 
                nombre_receta = ?, descripcion = ?, raciones = ?, tiempo_preparacion = ?, 
                dificultad = ?, imagen = ?, fuente = ?, instrucciones = ?, notas = ?, 
                id_tipos_dieta = ?, id_categorias = ?, id_tipos_plato = ?
                WHERE IDReceta = ?";
        
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
            $_POST['id_tipos_plato'],
            $id_receta
        ]);

        // Eliminar los ingredientes actuales de la receta
        $stmt = $pdo->prepare("DELETE FROM receta_ingredientes WHERE IDReceta = ?");
        $stmt->execute([$id_receta]);

        // Insertar los nuevos ingredientes de la receta
        $sql_ingrediente = "INSERT INTO receta_ingredientes (IDReceta, IDIngrediente, cantidad) VALUES (?, ?, ?)";
        $stmt_ingrediente = $pdo->prepare($sql_ingrediente);

        foreach ($_POST['ingredientes'] as $ingrediente) {
            $stmt_ingrediente->execute([$id_receta, $ingrediente['id'], $ingrediente['cantidad']]);
        }

        $pdo->commit();
        echo "<p>Receta actualizada con éxito.</p>";
        echo "<p><a href='index.php'>Volver a la página principal</a></p>";
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<p>Error al actualizar la receta: " . $e->getMessage() . "</p>";
    }
} else {
    // Mostrar el formulario de edición
?>

<!-- Editar receta -->
 <div class="card">
<h2>Editar Receta</h2>

<form action="" method="POST" id="editar-receta-form">
    <label for="nombre_receta">Nombre de la receta:</label>
    <input type="text" id="nombre_receta" name="nombre_receta" value="<?= htmlspecialchars($receta['nombre_receta']) ?>" required>

    <label for="descripcion">Descripción:</label>
    <textarea id="descripcion" name="descripcion"><?= htmlspecialchars($receta['descripcion']) ?></textarea>

    <label for="raciones">Raciones:</label>
    <input type="number" id="raciones" name="raciones" value="<?= $receta['raciones'] ?>" required>

    <label for="tiempo_preparacion">Tiempo de Preparación (minutos):</label>
    <input type="number" id="tiempo_preparacion" name="tiempo_preparacion" value="<?= $receta['tiempo_preparacion'] ?>" required>

    <label for="dificultad">Dificultad:</label>
    <select id="dificultad" name="dificultad" required>
        <option value="Baja" <?= $receta['dificultad'] == 'Baja' ? 'selected' : '' ?>>Baja</option>
        <option value="Media" <?= $receta['dificultad'] == 'Media' ? 'selected' : '' ?>>Media</option>
        <option value="Alta" <?= $receta['dificultad'] == 'Alta' ? 'selected' : '' ?>>Alta</option>
    </select>

    <label for="id_tipos_dieta">Tipo de Dieta:</label>
    <select id="id_tipos_dieta" name="id_tipos_dieta" required>
        <?php foreach ($tipos_dieta as $id => $nombre): ?>
            <option value="<?= $id ?>" <?= $receta['id_tipos_dieta'] == $id ? 'selected' : '' ?>><?= htmlspecialchars($nombre) ?></option>
        <?php endforeach; ?>
    </select>

    <label for="id_categorias">Categoría:</label>
    <select id="id_categorias" name="id_categorias" required>
        <?php foreach ($categorias as $id => $nombre): ?>
            <option value="<?= $id ?>" <?= $receta['id_categorias'] == $id ? 'selected' : '' ?>><?= htmlspecialchars($nombre) ?></option>
        <?php endforeach; ?>
    </select>

    <label for="id_tipos_plato">Tipo de Plato:</label>
    <select id="id_tipos_plato" name="id_tipos_plato" required>
        <?php foreach ($tipos_plato as $id => $nombre): ?>
            <option value="<?= $id ?>" <?= $receta['id_tipos_plato'] == $id ? 'selected' : '' ?>><?= htmlspecialchars($nombre) ?></option>
        <?php endforeach; ?>
    </select>

    <label for="imagen">URL de la imagen:</label>
    <input type="url" id="imagen" name="imagen" value="<?= htmlspecialchars($receta['imagen']) ?>">

    <label for="fuente">Fuente:</label>
    <input type="text" id="fuente" name="fuente" value="<?= htmlspecialchars($receta['fuente']) ?>">

    <label for="instrucciones">Instrucciones:</label>
    <textarea id="instrucciones" name="instrucciones" required><?= htmlspecialchars($receta['instrucciones']) ?></textarea>

    <label for="notas">Notas:</label>
    <textarea id="notas" name="notas"><?= htmlspecialchars($receta['notas']) ?></textarea>

    <h3>Ingredientes:</h3>
    <div id="ingredientes-container">
        <?php foreach ($ingredientes_actuales as $index => $ingrediente): ?>
            <div class="ingrediente-row">
                <select name="ingredientes[<?= $index ?>][id]" required>
                    <?php foreach ($ingredientes as $id => $nombre): ?>
                        <option value="<?= $id ?>" <?= $ingrediente['IDIngrediente'] == $id ? 'selected' : '' ?>><?= htmlspecialchars($nombre) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="ingredientes[<?= $index ?>][cantidad]" step="0.01" value="<?= $ingrediente['cantidad'] ?>" required placeholder="Cantidad">
            </div>
        <?php endforeach; ?>
    </div>
    <button type="button" id="agregar-ingrediente">Agregar otro ingrediente</button>

    <button type="submit">Guardar Cambios</button>
</form>
</div>
<!-- Fin editar receta -->

<script>
let ingredienteCount = <?= count($ingredientes_actuales) ?>;
document.getElementById('agregar-ingrediente').addEventListener('click', function() {
    const container = document.getElementById('ingredientes-container');
    const newRow = document.createElement('div');
    newRow.className = 'ingrediente-row';
    newRow.innerHTML = `
        <select name="ingredientes[${ingredienteCount}][id]" required>
            <?php foreach ($ingredientes as $id => $nombre): ?>
                <option value="<?= $id ?>"><?= htmlspecialchars($nombre) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="ingredientes[${ingredienteCount}][cantidad]" step="0.01" required placeholder="Cantidad">
    `;
    container.appendChild(newRow);
    ingredienteCount++;
});
</script>

<?php
}
include 'includes/footer.php';
?>