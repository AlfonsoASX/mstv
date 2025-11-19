<?php
/**
 * ===========================================
 *  CONSTANTES Y CATÁLOGOS DEL SISTEMA
 *  Proyecto: Seguridad Privada - ASX
 *  Archivo: constants.php
 * ===========================================
 */

/* -------------------------------------------
   ROLES DEL SISTEMA
------------------------------------------- */
const ROLES = [
    'GUARDIA'   => 'guardia',
    'SUPERVISOR'=> 'supervisor',
    'ADMIN'     => 'admin',
    'RH'        => 'rh',
    'NOMINA'    => 'nomina',
    'CLIENTE'   => 'cliente'
];

/* -------------------------------------------
   ESTADOS DE INCIDENCIAS
------------------------------------------- */
const INCIDENCIA_ESTADOS = [
    'PENDIENTE' => 'pendiente',
    'ATENDIDO'  => 'atendido',
    'CERRADO'   => 'cerrado'
];

/* -------------------------------------------
   TIPOS DE INCIDENCIAS
------------------------------------------- */
const INCIDENCIA_TIPOS = [
    'SEGURIDAD'  => 'seguridad',
    'OPERACION'  => 'operacion',
    'CLIENTE'    => 'cliente'
];

/* -------------------------------------------
   PRIORIDADES DE INCIDENCIA
------------------------------------------- */
const INCIDENCIA_PRIORIDAD = [
    'ALTA'  => 'alta',
    'MEDIA' => 'media',
    'BAJA'  => 'baja'
];

/* -------------------------------------------
   TIPOS DE CHECADA
------------------------------------------- */
const CHECADA_TIPOS = [
    'ENTRADA'       => 'entrada',
    'SALIDA'        => 'salida',
    'LLEGADA'       => 'llegada',
    'EXTRA_INICIO'  => 'extra_inicio',
    'EXTRA_FIN'     => 'extra_fin'
];

/* -------------------------------------------
   TIPOS DE TURNO
------------------------------------------- */
const TURNO_TIPOS = [
    'NORMAL' => 'normal',
    'EXTRA'  => 'extra'
];

/* -------------------------------------------
   TIPOS DE EVENTOS EN NOMINA
------------------------------------------- */
const NOMINA_EVENTOS_TIPO = [
    'NORMAL'     => 'normal',
    'EXTRA'      => 'extra',
    'DESCUENTO'  => 'descuento'
];

/* -------------------------------------------
   MENSAJES DEL SISTEMA
------------------------------------------- */
const MSG = [
    'ERROR_GENERAL'      => 'Ocurrió un error inesperado.',
    'ERROR_PERMISOS'     => 'No tienes permisos para realizar esta acción.',
    'ERROR_TOKEN'        => 'Token inválido o expirado.',
    'ERROR_CREDENCIALES' => 'Usuario o contraseña incorrectos.',
    'ERROR_CAMPOS'       => 'Faltan campos obligatorios.',
    'CHECADA_OK'         => 'Checada registrada con éxito.',
    'CHECADA_FUERA'      => 'Registrado fuera de geocerca.',
    'FACE_NO_MATCH'      => 'La verificación facial no coincide.',
];

/* -------------------------------------------
   PALABRAS PROHIBIDAS (MODERACIÓN BÁSICA)
------------------------------------------- */
const BAD_WORDS = [
    'puta','puto','idiota','imbecil','pendejo','mierda','chingar','chingada',
    'culero','culera','cabron','cabrón','verga','joto','marica','mamón',
    'bastardo','estupido','estúpido'
];

/* -------------------------------------------
   RESPUESTAS ESTÁNDAR
------------------------------------------- */
const RESPONSE = [
    'SUCCESS' => 'success',
    'ERROR'   => 'error'
];

