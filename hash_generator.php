<?php
// Script temporal para generar un hash seguro (bcrypt) de una contraseña
// para poder insertarlo manualmente en la columna 'password' de la tabla 'usuarios'.

// 1. Obtener la contraseña de la URL a través del parámetro 'pass'
$password_plana = $_GET['pass'] ?? 'contraseña_por_defecto';

if ($password_plana === 'contraseña_por_defecto') {
    $hash_generado = '¡ADVERTENCIA! Usa el parámetro ?pass=TU_CONTRASENA_AQUI en la URL.';
} else {
    // 2. Generar el hash seguro con el algoritmo PASSWORD_DEFAULT (Bcrypt)
    $hash_generado = password_hash($password_plana, PASSWORD_DEFAULT);
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Generador de Hash de Contraseña</title>
    <style>
        body { font-family: sans-serif; background-color: #f4f4f9; padding: 20px; }
        .container { max-width: 800px; margin: auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        h2 { color: #333; border-bottom: 2px solid #ccc; padding-bottom: 10px; }
        p strong { color: #d9534f; }
        textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; background-color: #f9f9f9; resize: none; font-family: monospace; }
        .warning { color: #8a6d3b; background-color: #fcf8e3; border: 1px solid #faebcc; padding: 15px; border-radius: 4px; margin-top: 20px; }
    </style>
</head>
<body>

<div class="container">
    <h2>Generador de Hash de Contraseña</h2>

    <div class="warning">
        <strong>INSTRUCCIONES CLAVE (Repite por CADA Vendedor):</strong>
        <ol>
            <li>Busca el `ID` o `email` del vendedor en la tabla `usuarios` de tu base de datos.</li>
            <li>Añade `?pass=` seguido de la contraseña real del vendedor a la URL (ej: `.../hash_generator.php?pass=Vendedor123`).</li>
            <li><strong>Copia el hash generado</strong> abajo.</li>
            <li>Ejecuta esta sentencia en phpMyAdmin, reemplazando `[ID_VENDEDOR]` y `[HASH_GENERADO]` con los valores obtenidos:
                <pre class="bg-gray-200 p-2 rounded">UPDATE usuarios SET password = '[HASH_GENERADO]' WHERE id = [ID_VENDEDOR];</pre>
            </li>
            <li><strong>¡ELIMINA ESTE ARCHIVO INMEDIATAMENTE DESPUÉS DE USARLO!</strong></li>
        </ol>
    </div>

    <p>Contraseña plana (ingresada): <strong><?php echo htmlspecialchars($password_plana); ?></strong></p>
    
    <?php if ($password_plana !== 'contraseña_por_defecto'): ?>
        <h3>Hash generado (Copia este texto):</h3>
        <textarea rows='3' cols='80' onclick="this.select()"><?php echo htmlspecialchars($hash_generado); ?></textarea>
    <?php else: ?>
        <h3><?php echo htmlspecialchars($hash_generado); ?></h3>
    <?php endif; ?>

</div>

</body>
</html>