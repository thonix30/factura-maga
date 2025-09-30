<?php
// Funciones para control de roles y sesiones

function verificarSesion() {
    if (!isset($_SESSION['usuario'])) {
        header("Location: index.php");
        exit();
    }
}

function verificarAdmin() {
    verificarSesion();
    if ($_SESSION['rol'] != 'administrador') {
        header("Location: dashboard.php");
        exit();
    }
}

function esAdmin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] == 'administrador';
}

function esUsuario() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] == 'usuario';
}

function getNombreUsuario() {
    return isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario';
}

function getRolUsuario() {
    return isset($_SESSION['rol']) ? $_SESSION['rol'] : 'usuario';
}

function mostrarErrorAcceso() {
    echo "<div class='error-acceso'>
            <h2>🚫 Acceso Denegado</h2>
            <p>No tienes permisos para acceder a esta sección.</p>
            <a href='dashboard.php' class='btn-volver'>← Volver al Dashboard</a>
          </div>";
}
?>