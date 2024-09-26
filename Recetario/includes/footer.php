</main>
    <footer>
        <p>&copy; 2024 Recetario</p>
    </footer>
    

</div>
<script>
function confirmarEliminar(id, nombre) {
    if (confirm("¿Estás seguro de que quieres eliminar la receta '" + nombre + "'?")) {
        window.location.href = "eliminar_receta.php?id=" + id;
    }
}

// Mostrar mensajes de éxito o error
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const mensaje = urlParams.get('mensaje');
    const error = urlParams.get('error');

    if (mensaje) {
        alert(mensaje);
    } else if (error) {
        alert("Error: " + error);
    }
});
</script>

</body>
</html>