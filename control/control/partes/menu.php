<?php
// menu.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Rol del usuario (por si luego quieres filtrar opciones)
$rol_nombre = isset($_SESSION['rol_nombre']) ? $_SESSION['rol_nombre'] : null;

// Archivo actual, ej: "dashboard.php"
$archivo_actual = basename($_SERVER['PHP_SELF']);

/**
 * Marca 'active' si el archivo actual coincide
 */
function menuActivo(string $archivo): string {
    global $archivo_actual;
    return ($archivo_actual === $archivo) ? 'active' : '';
}

/**
 * Devuelve true si el archivo actual está dentro del grupo
 */
function grupoActivo(array $archivos): bool {
    global $archivo_actual;
    return in_array($archivo_actual, $archivos, true);
}

// Grupos de archivos por menú
$grupo_dashboard   = ['dashboard.php', 'dashboard-sitio.php'];
$grupo_turnos      = ['turnos-calendario.php', 'turnos-asignacion.php', 'turnos-extras.php', 'turnos-politicas.php'];
$grupo_checadas    = ['checadas-lista.php', 'checadas-detalle.php'];
$grupo_incidencias = ['incidencias-bandeja.php', 'incidencias-urgentes.php', 'incidencias-historico.php'];
$grupo_bitacora    = ['bitacora-sitios.php', 'bitacora-eventos.php'];
$grupo_comunicacion = ['chat-admin.php', 'chat-rh.php', 'chat-moderacion.php'];
$grupo_personas    = ['personas-lista.php', 'personas-ficha.php', 'personas-documentos.php', 'personas-historial-sitios.php'];
$grupo_sitios      = ['sitios-lista.php', 'sitios-editor-geocerca.php', 'sitios-parametros-llegada.php'];
$grupo_onboarding  = ['capacitacion-modulos.php', 'capacitacion-carga-videos.php', 'capacitacion-reporte.php'];
$grupo_nomina      = ['nomina-configuracion.php', 'nomina-calculo.php', 'nomina-exportacion.php'];
$grupo_reportes    = ['reportes-puntualidad.php', 'reportes-incidencias.php', 'reportes-extras.php', 'reportes-exportaciones.php'];
$grupo_clientes    = ['clientes-lista.php', 'clientes-usuarios.php', 'clientes-asignacion-sitios.php', 'portal-cliente-dashboard.php'];
$grupo_usuarios    = ['usuarios-lista.php', 'roles-permisos.php', 'usuarios-reset-password.php'];
$grupo_config      = ['configuracion-global.php', 'configuracion-notificaciones.php', 'configuracion-catalogos.php'];
$grupo_auditoria   = ['auditoria-bitacora.php', 'auditoria-busqueda.php'];
$grupo_permisos    = ['permisos-matriz.php', 'permisos-roles-acciones.php'];

// Estados de apertura
$open_dashboard    = grupoActivo($grupo_dashboard);
$open_turnos       = grupoActivo($grupo_turnos);
$open_checadas     = grupoActivo($grupo_checadas);
$open_incidencias  = grupoActivo($grupo_incidencias);
$open_bitacora     = grupoActivo($grupo_bitacora);
$open_comunicacion = grupoActivo($grupo_comunicacion);
$open_personas     = grupoActivo($grupo_personas);
$open_sitios       = grupoActivo($grupo_sitios);
$open_onboarding   = grupoActivo($grupo_onboarding);
$open_nomina       = grupoActivo($grupo_nomina);
$open_reportes     = grupoActivo($grupo_reportes);
$open_clientes     = grupoActivo($grupo_clientes);
$open_usuarios     = grupoActivo($grupo_usuarios);
$open_config       = grupoActivo($grupo_config);
$open_auditoria    = grupoActivo($grupo_auditoria);
$open_permisos     = grupoActivo($grupo_permisos);
?>

<ul class="list-unstyled menu-categories ps ps--active-y" id="accordionExample">
    
    <!-- DASHBOARD EJECUTIVO -->
    <li class="menu <?= $open_dashboard ? 'active' : '' ?>">
        <a href="#dashboard" data-bs-toggle="collapse" aria-expanded="<?= $open_dashboard ? 'true' : 'false' ?>" class="dropdown-toggle <?= $open_dashboard ? '' : 'collapsed' ?>">
            <div class="">
                <!-- Icono Home -->
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-home">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                <span>Dashboard Ejecutivo</span>
            </div>
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </div>
        </a>
        <ul class="collapse submenu list-unstyled <?= $open_dashboard ? 'show' : '' ?>" id="dashboard" data-bs-parent="#accordionExample">
            <li class="<?= menuActivo('dashboard.php') ?>">
                <a href="dashboard.php"> Visión general </a>
            </li>
        </ul>
    </li>

    <!-- BLOQUE: OPERACIÓN Y SUPERVISIÓN -->
    <li class="menu menu-heading">
        <div class="heading">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-minus">
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>OPERACIÓN Y SUPERVISIÓN</span>
        </div>
    </li>

    <!-- Agenda y Turnos -->
    <li class="menu <?= $open_turnos ? 'active' : '' ?>">
        <a href="#turnos" data-bs-toggle="collapse" aria-expanded="<?= $open_turnos ? 'true' : 'false' ?>" class="dropdown-toggle <?= $open_turnos ? '' : 'collapsed' ?>">
            <div class="">
                <!-- Icono Calendar -->
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-calendar">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8"  y1="2" x2="8"  y2="6"></line>
                    <line x1="3"  y1="10" x2="21" y2="10"></line>
                </svg>
                <span>Agenda y turnos</span>
            </div>
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </div>
        </a>
        <ul class="collapse submenu list-unstyled <?= $open_turnos ? 'show' : '' ?>" id="turnos" data-bs-parent="#accordionExample">
            <li class="<?= menuActivo('turnos-calendario.php') ?>">
                <a href="turnos-calendario.php"> Calendario de turnos </a>
            </li>
            <li class="<?= menuActivo('turnos-asignacion.php') ?>">
                <a href="turnos-asignacion.php"> Asignación y rotaciones </a>
            </li>
            <li class="<?= menuActivo('turnos-extras.php') ?>">
                <a href="turnos-extras.php"> Turnos extra </a>
            </li>
            <li class="<?= menuActivo('turnos-politicas.php') ?>">
                <a href="turnos-politicas.php"> Políticas de jornadas </a>
            </li>
        </ul>
    </li>

    <!-- Checadas y Evidencias -->
    <li class="menu <?= $open_checadas ? 'active' : '' ?>">
        <a href="#checadas" data-bs-toggle="collapse" aria-expanded="<?= $open_checadas ? 'true' : 'false' ?>" class="dropdown-toggle <?= $open_checadas ? '' : 'collapsed' ?>">
            <div class="">
                <!-- Icono Check-square -->
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-check-square">
                    <polyline points="9 11 12 14 22 4"></polyline>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                </svg>
                <span>Checadas y evidencias</span>
            </div>
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </div>
        </a>
        <ul class="collapse submenu list-unstyled <?= $open_checadas ? 'show' : '' ?>" id="checadas" data-bs-parent="#accordionExample">
            <li class="<?= menuActivo('checadas-lista.php') ?>">
                <a href="checadas-lista.php"> Checadas (lista) </a>
            </li>
            <li class="<?= menuActivo('checadas-detalle.php') ?>">
                <a href="checadas-detalle.php"> Detalle de evidencia </a>
            </li>
        </ul>
    </li>

    <!-- Incidencias -->
    <li class="menu <?= $open_incidencias ? 'active' : '' ?>">
        <a href="#incidencias" data-bs-toggle="collapse" aria-expanded="<?= $open_incidencias ? 'true' : 'false' ?>" class="dropdown-toggle <?= $open_incidencias ? '' : 'collapsed' ?>">
            <div class="">
                <!-- Icono Alert-circle -->
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-alert-circle">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span>Incidencias</span>
            </div>
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </div>
        </a>
        <ul class="collapse submenu list-unstyled <?= $open_incidencias ? 'show' : '' ?>" id="incidencias" data-bs-parent="#accordionExample">
            <li class="<?= menuActivo('incidencias-bandeja.php') ?>">
                <a href="incidencias-bandeja.php"> Bandeja de incidencias </a>
            </li>
<!--            
            <li class="<?= menuActivo('incidencias-urgentes.php') ?>">
                <a href="incidencias-urgentes.php"> Urgentes (alertas) </a>
            </li>
            <li class="<?= menuActivo('incidencias-historico.php') ?>">
                <a href="incidencias-historico.php"> Histórico </a>
            </li>
            -->
        </ul>
    </li>

    <!-- Bitácora 
    <li class="menu <?= $open_bitacora ? 'active' : '' ?>">
        <a href="#bitacora" data-bs-toggle="collapse" aria-expanded="<?= $open_bitacora ? 'true' : 'false' ?>" class="dropdown-toggle <?= $open_bitacora ? '' : 'collapsed' ?>">
            <div class="">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-book-open">
                    <path d="M2 3h7a4 4 0 0 1 4 4v14H6a4 4 0 0 1-4-4V3z"></path>
                    <path d="M22 3h-7a4 4 0 0 0-4 4v14h7a4 4 0 0 0 4-4V3z"></path>
                </svg>
                <span>Bitácora global</span>
            </div>
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </div>
        </a>
        <ul class="collapse submenu list-unstyled <?= $open_bitacora ? 'show' : '' ?>" id="bitacora" data-bs-parent="#accordionExample">
            <li class="<?= menuActivo('bitacora-sitios.php') ?>">
                <a href="bitacora-sitios.php"> Bitácora por sitio </a>
            </li>
            <li class="<?= menuActivo('bitacora-eventos.php') ?>">
                <a href="bitacora-eventos.php"> Eventos urgentes </a>
            </li>
        </ul>
    </li>
-->
    <!-- Comunicación interna -->
    <li class="menu <?= $open_comunicacion ? 'active' : '' ?>">
        <a href="#comunicacion" data-bs-toggle="collapse" aria-expanded="<?= $open_comunicacion ? 'true' : 'false' ?>" class="dropdown-toggle <?= $open_comunicacion ? '' : 'collapsed' ?>">
            <div class="">
                <!-- Icono Message-circle -->
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-message-circle">
                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                </svg>
                <span>Comunicación interna</span>
            </div>
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </div>
        </a>
        <ul class="collapse submenu list-unstyled <?= $open_comunicacion ? 'show' : '' ?>" id="comunicacion" data-bs-parent="#accordionExample">
            <li class="<?= menuActivo('chat-admin.php') ?>">
                <a href="chat-admin.php"> Chat interno </a>
            </li>
            <li class="<?= menuActivo('chat-moderacion.php') ?>">
                <a href="chat-moderacion.php"> Moderación (antigroserías) </a>
            </li>
        </ul>
    </li>

    <!-- BLOQUE: ADMINISTRACIÓN / RH / NÓMINA -->
    <li class="menu menu-heading">
        <div class="heading">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-minus">
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>ADMINISTRACIÓN, RH Y NÓMINA</span>
        </div>
    </li>

    <!-- Personas (guardias / personal)
    <li class="menu <?= $open_personas ? 'active' : '' ?>">
        <a href="#personas" data-bs-toggle="collapse" aria-expanded="<?= $open_personas ? 'true' : 'false' ?>" class="dropdown-toggle <?= $open_personas ? '' : 'collapsed' ?>">
            <div class="">
    
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-users">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span>Personal</span>
            </div>
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </div>
        </a>
        <ul class="collapse submenu list-unstyled <?= $open_personas ? 'show' : '' ?>" id="personas" data-bs-parent="#accordionExample">
            <li class="<?= menuActivo('personas-lista.php') ?>">
                <a href="personas-lista.php"> Base de personal </a>
            </li>
           
            <li class="<?= menuActivo('personas-ficha.php') ?>">
                <a href="personas-ficha.php"> Ficha técnica </a>
            </li>
            <li class="<?= menuActivo('personas-documentos.php') ?>">
                <a href="personas-documentos.php"> Documentos y caducidades </a>
            </li>
            <li class="<?= menuActivo('personas-historial-sitios.php') ?>">
                <a href="personas-historial-sitios.php"> Historial de sitios </a>
            </li>
            
        </ul>
    </li>
 -->
    <!-- Sitios y Geocercas -->
    <li class="menu <?= $open_sitios ? 'active' : '' ?>">
        <a href="#sitios" data-bs-toggle="collapse" aria-expanded="<?= $open_sitios ? 'true' : 'false' ?>" class="dropdown-toggle <?= $open_sitios ? '' : 'collapsed' ?>">
            <div class="">
                <!-- Icono Map -->
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-map">
                    <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon>
                    <line x1="8" y1="2" x2="8" y2="18"></line>
                    <line x1="16" y1="6" x2="16" y2="22"></line>
                </svg>
                <span>Sitios y geocercas</span>
            </div>
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </div>
        </a>
        <ul class="collapse submenu list-unstyled <?= $open_sitios ? 'show' : '' ?>" id="sitios" data-bs-parent="#accordionExample">
            <li class="<?= menuActivo('sitios-editor-geocerca.php') ?>">
                <a href="sitios-editor-geocerca.php"> Editor de geocercas </a>
            </li>
<!--            <li class="<?= menuActivo('sitios-parametros-llegada.php') ?>">
                <a href="sitios-parametros-llegada.php"> Parámetros de llegada </a>
            </li>
            -->
        </ul>
    </li>

    <!-- Onboarding y capacitación 
    <li class="menu <?= $open_onboarding ? 'active' : '' ?>">
        <a href="#onboarding" data-bs-toggle="collapse" aria-expanded="<?= $open_onboarding ? 'true' : 'false' ?>" class="dropdown-toggle <?= $open_onboarding ? '' : 'collapsed' ?>">
            <div class="">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-video">
                    <polygon points="23 7 16 12 23 17 23 7"></polygon>
                    <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                </svg>
                <span>Onboarding y capacitación</span>
            </div>
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </div>
        </a>
        <ul class="collapse submenu list-unstyled <?= $open_onboarding ? 'show' : '' ?>" id="onboarding" data-bs-parent="#accordionExample">
            <li class="<?= menuActivo('capacitacion-modulos.php') ?>">
                <a href="capacitacion-modulos.php"> Módulos de capacitación </a>
            </li>
            <li class="<?= menuActivo('capacitacion-carga-videos.php') ?>">
                <a href="capacitacion-carga-videos.php"> Carga de videos </a>
            </li>
            <li class="<?= menuActivo('capacitacion-reporte.php') ?>">
                <a href="capacitacion-reporte.php"> Reporte de cumplimiento </a>
            </li>
        </ul>
    </li>
-->
    <!-- Nómina -->
    <li class="menu <?= $open_nomina ? 'active' : '' ?>">
        <a href="#nomina" data-bs-toggle="collapse" aria-expanded="<?= $open_nomina ? 'true' : 'false' ?>" class="dropdown-toggle <?= $open_nomina ? '' : 'collapsed' ?>">
            <div class="">
                <!-- Icono Dollar-sign -->
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-dollar-sign">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                </svg>
                <span>Nómina</span>
            </div>
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </div>
        </a>
        <ul class="collapse submenu list-unstyled <?= $open_nomina ? 'show' : '' ?>" id="nomina" data-bs-parent="#accordionExample">
<!--            
            <li class="<?= menuActivo('nomina-configuracion.php') ?>">
                <a href="nomina-configuracion.php"> Configuración de layout </a>
            </li>
            -->
            <li class="<?= menuActivo('nomina-calculo.php') ?>">
                <a href="nomina-calculo.php"> Cálculo de nómina </a>
            </li>
            <li class="<?= menuActivo('nomina-exportacion.php') ?>">
                <a href="nomina-exportacion.php"> Exportación CSV/Excel </a>
            </li>
        </ul>
    </li>

    <!-- Reportes y KPIs -->
    <li class="menu <?= $open_reportes ? 'active' : '' ?>">
        <a href="#reportes" data-bs-toggle="collapse" aria-expanded="<?= $open_reportes ? 'true' : 'false' ?>" class="dropdown-toggle <?= $open_reportes ? '' : 'collapsed' ?>">
            <div class="">
                <!-- Icono Bar-chart-2 -->
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-bar-chart-2">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6"  y1="20" x2="6"  y2="14"></line>
                </svg>
                <span>Reportes y KPIs</span>
            </div>
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </div>
        </a>
        <ul class="collapse submenu list-unstyled <?= $open_reportes ? 'show' : '' ?>" id="reportes" data-bs-parent="#accordionExample">
            <li class="<?= menuActivo('reportes-puntualidad.php') ?>">
                <a href="reportes-puntualidad.php"> Puntualidad y absentismo </a>
            </li>
            <li class="<?= menuActivo('reportes-incidencias.php') ?>">
                <a href="reportes-incidencias.php"> Incidencias por sitio </a>
            </li>
            <li class="<?= menuActivo('reportes-extras.php') ?>">
                <a href="reportes-extras.php"> Costo de extras </a>
            </li>
        </ul>
    </li>

    <!-- BLOQUE: CLIENTES Y PORTAL 
    <li class="menu menu-heading">
        <div class="heading">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-minus">
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>CLIENTES</span>
        </div>
    </li>

    <li class="menu <?= $open_clientes ? 'active' : '' ?>">
        <a href="#clientes" data-bs-toggle="collapse" aria-expanded="<?= $open_clientes ? 'true' : 'false' ?>" class="dropdown-toggle <?= $open_clientes ? '' : 'collapsed' ?>">
            <div class="">

                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-briefcase">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                    <path d="M16 3H8a2 2 0 0 0-2 2v2h12V5a2 2 0 0 0-2-2z"></path>
                </svg>
                <span>Clientes y portal</span>
            </div>
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </div>
        </a>
        <ul class="collapse submenu list-unstyled <?= $open_clientes ? 'show' : '' ?>" id="clientes" data-bs-parent="#accordionExample">
            <li class="<?= menuActivo('clientes-lista.php') ?>">
                <a href="clientes-lista.php"> Catálogo de clientes </a>
            </li>
            <li class="<?= menuActivo('clientes-usuarios.php') ?>">
                <a href="clientes-usuarios.php"> Usuarios cliente </a>
            </li>
            <li class="<?= menuActivo('clientes-asignacion-sitios.php') ?>">
                <a href="clientes-asignacion-sitios.php"> Asignación de sitios </a>
            </li>
            <li class="<?= menuActivo('portal-cliente-dashboard.php') ?>">
                <a href="portal-cliente-dashboard.php"> Portal cliente (solo lectura) </a>
            </li>
        </ul>
    </li>
    -->

    <!-- BLOQUE: SISTEMA -->
    <li class="menu menu-heading">
        <div class="heading">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-minus">
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>SISTEMA</span>
        </div>
    </li>

    <!-- Usuarios y Roles -->
    <li class="menu <?= $open_usuarios ? 'active' : '' ?>">
        <a href="#usuarios-roles" data-bs-toggle="collapse" aria-expanded="<?= $open_usuarios ? 'true' : 'false' ?>" class="dropdown-toggle <?= $open_usuarios ? '' : 'collapsed' ?>">
            <div class="">
                <!-- Icono User -->
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-user">
                    <path d="M20 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M4 21v-2a4 4 0 0 1 3-3.87"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>Usuarios y roles</span>
            </div>
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </div>
        </a>
        <ul class="collapse submenu list-unstyled <?= $open_usuarios ? 'show' : '' ?>" id="usuarios-roles" data-bs-parent="#accordionExample">
            <li class="<?= menuActivo('usuarios-lista.php') ?>">
                <a href="usuarios-lista.php"> Catálogo de usuarios </a>
            </li>
            <li class="<?= menuActivo('roles-permisos.php') ?>">
                <a href="roles-permisos.php"> Roles y permisos </a>
            </li>
            <li class="<?= menuActivo('usuarios-reset-password.php') ?>">
                <a href="usuarios-reset-password.php"> Reset de contraseñas </a>
            </li>
        </ul>
    </li>

    <!-- Configuración -->
    <li class="menu <?= $open_config ? 'active' : '' ?>">
        <a href="#configuracion" data-bs-toggle="collapse" aria-expanded="<?= $open_config ? 'true' : 'false' ?>" class="dropdown-toggle <?= $open_config ? '' : 'collapsed' ?>">
            <div class="">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-settings">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 8 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 3.6 15a1.65 1.65 0 0 0-1.51-1H2a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 3.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 8 3.6a1.65 1.65 0 0 0 1-1.51V2a2 2 0 0 1 4 0v.09A1.65 1.65 0 0 0 16 3.6a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 20.4 9a1.65 1.65 0 0 0 1.51 1H22a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                </svg>
                <span>Configuración</span>
            </div>
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </div>
        </a>
        <ul class="collapse submenu list-unstyled <?= $open_config ? 'show' : '' ?>" id="configuracion" data-bs-parent="#accordionExample">
            <li class="<?= menuActivo('configuracion-global.php') ?>">
                <a href="configuracion-global.php"> Parámetros globales </a>
            </li>
            <li class="<?= menuActivo('configuracion-notificaciones.php') ?>">
                <a href="configuracion-notificaciones.php"> Notificaciones y alertas </a>
            </li>
 <!--           
            <li class="<?= menuActivo('configuracion-catalogos.php') ?>">
                <a href="configuracion-catalogos.php"> Catálogos del sistema </a>
            </li>
            -->
        </ul>
    </li>

    <!-- Auditoría 
    <li class="menu <?= $open_auditoria ? 'active' : '' ?>">
        <a href="#auditoria" data-bs-toggle="collapse" aria-expanded="<?= $open_auditoria ? 'true' : 'false' ?>" class="dropdown-toggle <?= $open_auditoria ? '' : 'collapsed' ?>">
            <div class="">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-shield">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
                <span>Auditoría</span>
            </div>
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </div>
        </a>
        <ul class="collapse submenu list-unstyled <?= $open_auditoria ? 'show' : '' ?>" id="auditoria" data-bs-parent="#accordionExample">
            <li class="<?= menuActivo('auditoria-bitacora.php') ?>">
                <a href="auditoria-bitacora.php"> Bitácora técnica </a>
            </li>
            <li class="<?= menuActivo('auditoria-busqueda.php') ?>">
                <a href="auditoria-busqueda.php"> Búsqueda avanzada </a>
            </li>
        </ul>
    </li>
-->

    <!-- Permisos finos 
    <li class="menu <?= $open_permisos ? 'active' : '' ?>">
        <a href="#permisos" data-bs-toggle="collapse" aria-expanded="<?= $open_permisos ? 'true' : 'false' ?>" class="dropdown-toggle <?= $open_permisos ? '' : 'collapsed' ?>">
            <div class="">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-grid">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                <span>Permisos finos</span>
            </div>
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </div>
        </a>
        <ul class="collapse submenu list-unstyled <?= $open_permisos ? 'show' : '' ?>" id="permisos" data-bs-parent="#accordionExample">
            <li class="<?= menuActivo('permisos-matriz.php') ?>">
                <a href="permisos-matriz.php"> Matriz de permisos </a>
            </li>
            <li class="<?= menuActivo('permisos-roles-acciones.php') ?>">
                <a href="permisos-roles-acciones.php"> Roles vs acciones </a>
            </li>
        </ul>
    </li>
-->
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
</ul>
