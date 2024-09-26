<?php
require_once 'includes/funciones.php';

$pdo = connectDB();

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_receta = $_GET['id'];

    try {
        $pdo->beginTransaction();

        // Primero, eliminar los ingredientes asociados a la receta
        $stmt = $pdo->prepare("DELETE FROM receta_ingredientes WHERE IDReceta = ?");
        $stmt->execute([$id_receta]);

        // Luego, eliminar la receta
        $stmt = $pdo->prepare("DELETE FROM recetas WHERE IDReceta = ?");
        $stmt->execute([$id_receta]);

        $pdo->commit();

        // Redirigir a la página principal con un mensaje de éxito
        header("Location: index.php?mensaje=Receta eliminada con éxito");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        // Redirigir a la página principal con un mensaje de error
        header("Location: index.php?error=Error al eliminar la receta: " . urlencode($e->getMessage()));
        exit();
    }
} else {
    // Si no se proporcionó un ID válido, redirigir a la página principal
    header("Location: index.php");
    exit();
}
?>