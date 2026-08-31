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

];
