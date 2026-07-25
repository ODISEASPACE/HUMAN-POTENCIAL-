<?php
/**
 * auth_guard.php
 *
 * Incluir al inicio de cada dashboard (admin.php, developer.php, user.php)
 * indicando qué rol(es) pueden ver esa página. Si no hay sesión válida o el
 * rol no está permitido, redirige a prod_space.php.
 *
 * Uso en dashboards/admin.php:
 *   <?php
 *   require_once __DIR__ . '/../auth_guard.php';
 *   $usuario = verificar_sesion(['Administrador']);
 *   ?>
 */

function verificar_sesion(array $roles_permitidos) {
    $token = $_COOKIE['session_token'] ?? null;

    if (!$token) {
        header('Location: /prod_space.php');
        exit;
    }

    $ch = curl_init('http://127.0.0.1:8000/api/v1/auth/verify');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $respuesta = curl_exec($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($codigo !== 200) {
        header('Location: /prod_space.php');
        exit;
    }

    $usuario = json_decode($respuesta, true);

    if (!in_array($usuario['rol'], $roles_permitidos, true)) {
        header('Location: /prod_space.php');
        exit;
    }

    return $usuario;
}
