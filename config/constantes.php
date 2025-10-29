<?php

return [

    'CATALOGO_NO_ESPECIFICADO'      => 0, // Sirve para inicializar registros en blanco (vacíos)

    /*
    |--------------------------------------------------------------------------
    | Categorías de Catálogo
    |--------------------------------------------------------------------------
    |
    | Identificadores de categorías dentro de la tabla "catalogos" para
    | uso en AJAX, lógica de carga select2, etc.
    |
    */

    'CATEGORIA_ROL_USUARIO'           =>   1,
    'CATEGORIA_TIPO_RELACION'         =>  20,
    'CATEGORIA_ZONA_SUCURSAL'         =>  21,
    'CATEGORIA_TIPO_SUCURSAL'         =>  29,
    'CATEGORIA_TIPO_EMPRESA'          =>  31,
    'CATEGORIA_TIPO_RETIRO'           =>  34,
    'CATEGORIA_TIPO_ESPECIE'          =>  37,
    'CATEGORIA_TIPO_MATERIA_PRIMA'    =>  41,
    'CATEGORIA_TIPO_CAMION'           =>  43,
    'CATEGORIA_GRUPO_TIPO_CAMION'     =>  53,
    'CATEGORIA_CAMBIOS_PLANIFICACION' => 110,

    /*
    |--------------------------------------------------------------------------
    | Tipos Específicos Usados en Lógica
    |--------------------------------------------------------------------------
    |
    | Valores individuales de catálogo usados para bifurcar lógica,
    | mostrar u ocultar secciones, controlar acceso, etc.
    |
    */

    // Roles de usuarios
    'ROL_SOLICITANTE_PLANTA'        => 61,
    'ROL_SOLICITANTE_PRODUCTOR'     => 62,
    'ROL_PERSONAL_GERENCIA'         => 63,
    'ROL_PERSONAL_PRODUCCION'       => 64,
    'ROL_PERSONAL_CALIDAD'          => 65,
    'ROL_COORDINADOR'               => 66,
    'ROL_PERSONAL_MANTENCION'       => 67,
    'ROL_PERSONAL_ROMANA'           => 68,
    'ROL_ADMINISTRADOR_IT'          => 69,

    // Valores usados para bifurcar lógicas    
    'TIPO_EMPRESA_PRODUCTORA'       => 32,
    'TIPO_EMPRESA_TRANSPORTISTA'    => 33,

    'TIPO_SUCURSAL_PLANTA'          => 30,

    'TIPO_RETIRO_TOLVA'             => 35,
    'TIPO_RETIRO_BINS'              => 36,
    

    /*
    |--------------------------------------------------------------------------
    | Estados de Solicitudes de Retiro
    |--------------------------------------------------------------------------
    |
    | Estos valores se usan para identificar la etapa en el flujo del proceso de un retiro desde solicitud hasta planificación.
    | 
    | No se cargan desde la base de datos ni están en el catálogo.
    |
    */
    'CATEGORIA_ESTADOS_RETIRO'      => 90,

    'ESTADO_RETIRO_ESPERANDO'       => 91,
    'ESTADO_RETIRO_COMENTADO'       => 92,
    'ESTADO_RETIRO_ACEPTADO'        => 93,
    'ESTADO_RETIRO_PLANIFICADO'     => 94,
    'ESTADO_RETIRO_PROGRAMADO'      => 95,
    'ESTADO_RETIRO_TERMINADO'       => 96,
    'ESTADO_RETIRO_CANCELADO'       => 97,


    /*
    |--------------------------------------------------------------------------
    | Calidad del Retiro en el Programa Diario
    |--------------------------------------------------------------------------
    |
    | Estos valores se usan para identificar la calidad de un retiro dentro de un programas_diarios.
    | 
    | No se cargan desde la base de datos ni están en el catálogo.
    |
    */
    'CALIDAD_RETIRO_ORIGINAL'    => 0,
    'CALIDAD_RETIRO_ACTUALIZADO' => 1,
    'CALIDAD_RETIRO_NUEVO'       => 2,

    /*
    |--------------------------------------------------------------------------
    | Estados del Programa Diario
    |--------------------------------------------------------------------------
    |
    | Estos valores se usan en la creación y mantención de programas_diarios.
    | 
    | No se cargan desde la base de datos ni están en el catálogo.
    |
    */
    'ESTADO_PROGRAMA_EMITIDO' => 1,

    /*
    |--------------------------------------------------------------------------
    | Valores especiales para acceder a versiones del Programa Diario
    |--------------------------------------------------------------------------
    | 
    | No se cargan desde la base de datos ni están en el catálogo.
    |
    */
    'VERSION_TODAS'  => 0,
    'VERSION_ULTIMA' => -1,
    'VERSION_PRIMERA'=> 1,

    /*
    |--------------------------------------------------------------------------
    | Estados de Notificación (Correo / Telegram)
    |--------------------------------------------------------------------------
    |
    | Estos valores se usan en las tablas programas_diarios y programas_diarios_detalle
    | para indicar el estado del proceso de notificación por correo electrónico
    | y por Telegram.
    | 
    | No se cargan desde la base de datos ni están en el catálogo.
    |
    */
    'NOTIF_PENDIENTE'    => 0, // No se ha iniciado el proceso de notificación
    'NOTIF_EN_PROCESO'   => 1, // Notificaciones en curso
    'NOTIF_ENVIADO'      => 2, // Todas las notificaciones fueron enviadas correctamente
    'NOTIF_FALLIDO'      => 3, // Una o más notificaciones fallaron durante el proceso
    'NOTIF_SIN_TELEGRAM' => 4, // Conductor sin chat_id
    'NOTIF_SIN_CAMBIOS'  => 5, // Retiro no presenta cambios que notificar al Conductor por Mensaje Telegram

];
