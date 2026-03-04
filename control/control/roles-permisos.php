<?php
session_start();
include 'lib/db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Programadores trabajando</title>
    <link href="../src/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            text-align: center;
        }
        .card {
            border-radius: 15px;
            padding: 40px;
            max-width: 480px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
            background: white;
        }
        .gear-icon {
            width: 90px;
            animation: spin 3s linear infinite;
            margin-bottom: 20px;
        }
        @keyframes spin {
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

    <div class="card">
        <img src="https://cdn-icons-png.flaticon.com/512/4712/4712892.png" class="gear-icon" alt="Gear Icon">
        <h2>👨‍💻 Programadores trabajando</h2>
        <p>Estamos construyendo esta funcionalidad.<br>
           Muy pronto estará disponible. 🚀</p>
        
        <a href="dashboard.php" class="btn btn-primary mt-3">Volver al inicio</a>
    </div>

</body>
</html>
