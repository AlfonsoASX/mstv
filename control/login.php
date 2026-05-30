<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <title>Iniciar sesión | MSTV Seguridad</title>

    <link rel="icon" type="image/x-icon" href="../src/assets/img/favicon.ico"/>
    <link href="../layouts/vertical-light-menu/css/light/loader.css" rel="stylesheet" type="text/css" />
    <link href="../layouts/vertical-light-menu/css/dark/loader.css" rel="stylesheet" type="text/css" />
    <script src="../layouts/vertical-light-menu/loader.js"></script>

    <!-- BEGIN GLOBAL MANDATORY STYLES -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:400,600,700" rel="stylesheet">
    <link href="../src/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    
    <link href="../layouts/vertical-light-menu/css/light/plugins.css" rel="stylesheet" type="text/css" />
    <link href="../src/assets/css/light/authentication/auth-boxed.css" rel="stylesheet" type="text/css" />
    
    <link href="../layouts/vertical-light-menu/css/dark/plugins.css" rel="stylesheet" type="text/css" />
    <link href="../src/assets/css/dark/authentication/auth-boxed.css" rel="stylesheet" type="text/css" />
    <!-- END GLOBAL MANDATORY STYLES -->
</head>
<body class="form">

    <!-- BEGIN LOADER -->
    <div id="load_screen"> 
        <div class="loader"> 
            <div class="loader-content">
                <div class="spinner-grow align-self-center"></div>
            </div>
        </div>
    </div>
    <!--  END LOADER -->

    <div class="auth-container d-flex">

        <div class="container mx-auto align-self-center">
    
            <div class="row">
    
                <div class="col-xxl-4 col-xl-5 col-lg-5 col-md-8 col-12 d-flex flex-column align-self-center mx-auto">
                    <div class="card mt-3 mb-3">
                        <div class="card-body">
    
                            <div class="row">

                                <div class="col-md-12 mb-3 text-center">
                                    <!-- Logo de la empresa -->
                                    <img src="https://mstv.com.mx/assets/logonuevo.png" 
                                         alt="MSTV Seguridad" 
                                         class="img-fluid mb-3" 
                                         style="max-height: 80px;">
                                    
                                    <h2 class="mb-1">Iniciar sesión</h2>
                                    <p class="mb-0">Ingresa tu usuario y contraseña para acceder al panel.</p>
                                </div>

                                <!-- Mensaje de error si viene desde index.php -->
                                <?php if (isset($__mensaje_error_login) && $__mensaje_error_login !== ''): ?>
                                    <div class="col-md-12">
                                        <div class="alert alert-danger py-2">
                                            <?php echo $__mensaje_error_login; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- FORMULARIO DE LOGIN -->
                                <div class="col-md-12">
                                    <form method="post" action="index.php">
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Usuario</label>
                                            <input type="text" class="form-control" name="user" required autocomplete="username">
                                        </div>

                                        <div class="mb-4">
                                            <label class="form-label">Contraseña</label>
                                            <input type="password" class="form-control" name="password" required autocomplete="current-password">
                                        </div>

                                        <div class="mb-4">
                                            <button type="submit" class="btn btn-secondary w-100">
                                                Iniciar sesión
                                            </button>
                                        </div>

                                    </form>
                                </div>
                                
                            </div>
                            
                        </div>
                    </div>
                </div>
                
            </div>
            
        </div>

    </div>
    
    <!-- BEGIN GLOBAL MANDATORY SCRIPTS -->
    <script src="../src/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- END GLOBAL MANDATORY SCRIPTS -->

</body>
</html>
