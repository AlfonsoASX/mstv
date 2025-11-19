<?php
/**
 * ===============================================
 * HEADER GLOBAL DE PANEL WEB
 * Archivo: backend/admin/includes/header.php
 * ===============================================
 */

session_start();
require_once __DIR__ . '/auth_guard.php'; // asegura login y datos del usuario

// Datos del usuario (inyectado desde auth_guard.php)
$nombreUsuario = $AUTH_USER['nombre'] ?? 'Usuario';
$rolUsuario    = strtoupper($AUTH_USER['rol'] ?? 'CLIENTE');
$fotoUsuario   = $AUTH_USER['foto'] ?? null; // Si decides agregar foto en payload

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Control</title>
    <link rel="stylesheet" 
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <style>
        .navbar-brand img {
            height: 42px;
            margin-right: 10px;
        }
        .user-info {
            text-align: right;
            font-size: 0.85rem;
        }
        .user-info .rol {
            display: block;
            font-size: 0.75rem;
            color: #999;
        }
        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 8px;
        }
    </style>
</head>

<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container-fluid">

        <!-- LOGO + Nombre del sistema -->
        <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
            <img src="/assets/logo.png" alt="Logo"> <!-- Cambiar por logo real -->
            Sistema de Control de Vigilancia
        </a>

        <!-- Botón hamburguesa (responsive) -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#mainNavbar" aria-controls="mainNavbar"
                aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Menú -->
        <div class="collapse navbar-collapse" id="mainNavbar">

            <!-- Espacio flexible -->
            <div class="me-auto"></div>

            <!-- Información de usuario -->
            <div class="user-info d-flex align-items-center">

                <?php if ($fotoUsuario): ?>
                    <img src="/fotos/<?= htmlspecialchars($fotoUsuario) ?>" 
                         class="avatar" alt="Usuario">
                <?php endif; ?>

                <div class="me-3 text-white">
                    <?= htmlspecialchars($nombreUsuario) ?>
                    <span class="rol"><?= htmlspecialchars($rolUsuario) ?></span>
                </div>

                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    Cerrar sesión
                </a>
            </div>
        </div>
    </div>
</nav>

<div class="container mt-4">
