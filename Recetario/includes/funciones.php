<?php
function connectDB() {
    // 1. Define las variables de conexión:
    $host = 'localhost';
    $db   = 'recetario';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';

    // 2. Configura el DSN (Data Source Name):
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    // El DSN es una cadena que contiene la información de conexión, como el tipo de base de datos, 
    // el host, el nombre de la base de datos y el conjunto de caracteres.

    // 3. Configura las opciones para la conexión PDO:
    $options = [// Define un array de opciones para PDO
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Modo de error, configurado para lanzar excepciones 
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Modo de obtención de resultados, configurado para devolver resultados como un array asociativo (PDO::FETCH_ASSOC).
        PDO::ATTR_EMULATE_PREPARES   => false,  //  Desactiva la emulación de consultas preparadas, utilizando consultas preparadas nativas del servidor (false).
    ];

    // 4. Intenta crear una instancia de PDO:
    try {
        $pdo = new PDO($dsn, $user, $pass, $options); // Intenta crear una nueva instancia de PDO pasando la DSN, las credenciales del usuario y las opciones definidas
        return $pdo; //  Si la conexión es exitosa, retorna el objeto PDO
    } catch (\PDOException $e) {  //  Si ocurre un error, captura la excepción PDOException
        throw new \PDOException($e->getMessage(), (int)$e->getCode());  // lanza una nueva excepción con el mensaje y el código de error.
    }
}

// Función para obtener opciones de una tabla
function getOptions($pdo, $table, $id_field, $name_field) {
    $stmt = $pdo->query("SELECT $id_field, $name_field FROM $table ORDER BY $name_field");
    $options = [];
    while ($row = $stmt->fetch()) {
        $options[$row[$id_field]] = $row[$name_field];
    }
    return $options;
}

// Función para verificar si un ingrediente ya existe
function ingredienteExiste($pdo, $nombre_ingrediente, $id = null) {
    $sql = "SELECT COUNT(*) FROM ingredientes WHERE LOWER(nombre_ingrediente) = LOWER(?)";
    $params = [trim($nombre_ingrediente)];

    if ($id !== null) {
        $sql .= " AND IDIngrediente != ?";
        $params[] = $id;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() > 0;
}



// Función para mostrar mensajes de alerta usando SweetAlert2
// function mostrarAlerta($mensaje) {
//     if ($mensaje && isset($mensaje['text'])) {
//         echo "<script>
//         document.addEventListener('DOMContentLoaded', function() {
//             Swal.fire({
//                 title: '" . ($mensaje['type'] === 'error' ? 'Error' : 'Éxito') . "',
//                 text: '" . $mensaje['text'] . "',
//                 icon: '" . ($mensaje['type'] === 'error' ? 'error' : 'success') . "',
//                 confirmButtonText: 'Aceptar'
//             });
//     });
//         </script>";
//     }
// }

// Función para generar la paginación
function generarPaginacion($total_items, $items_per_page, $current_page, $url_params = []) {
    $total_pages = ($total_items > 0) ? ceil($total_items / $items_per_page) : 1;
    $output = '<div class="paginacion">';

    if ($total_pages > 1) {
        if ($current_page > 1) {
            $output .= '<a href="?' . http_build_query(array_merge($url_params, ['page' => 1])) . '" class="pagination-link">&laquo; Primera</a>';
            $output .= '<a href="?' . http_build_query(array_merge($url_params, ['page' => $current_page - 1])) . '" class="pagination-link">&lsaquo; Anterior</a>';
        }

        $range = 2;
        $start_page = max(1, $current_page - $range);
        $end_page = min($total_pages, $current_page + $range);

        for ($i = $start_page; $i <= $end_page; $i++) {
            if ($i == $current_page) {
                $output .= "<span class='pagination-link current-page'>$i</span>";
            } else {
                $output .= "<a href='?" . http_build_query(array_merge($url_params, ['page' => $i])) . "' class='pagination-link'>$i</a>";
            }
        }

        if ($current_page < $total_pages) {
            $output .= '<a href="?' . http_build_query(array_merge($url_params, ['page' => $current_page + 1])) . '" class="pagination-link">Siguiente &rsaquo;</a>';
            $output .= '<a href="?' . http_build_query(array_merge($url_params, ['page' => $total_pages])) . '" class="pagination-link">Última &raquo;</a>';
        }
    }

    $output .= '</div>';
    return $output;
}
?>

