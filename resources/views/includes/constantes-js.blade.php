<script>
/**
 * ╔════════════════════════════════════════════════════════════════════════════╗
 * ║ CONSTANTES DE CATÁLOGO USADAS EN JAVASCRIPT                               ║
 * ║ --------------------------------------------------------------------------║
 * ║ Estas constantes provienen del archivo PHP: config/constantes.php         ║
 * ║ Y se inyectan aquí para que estén disponibles en JS                       ║
 * ║                                                                           ║
 * ║ ⚠️ NO DUPLICAR ESTAS CONSTANTES EN OTROS .js                              ║
 * ╚════════════════════════════════════════════════════════════════════════════╝
 */

window.constantes = {
	// Sirve para inicializar registros en blanco (vacíos)
	CATALOGO_NO_ESPECIFICADO      	: {{ config('constantes.CATALOGO_NO_ESPECIFICADO') }},

	// 🧭 Categorías de Catálogo usadas en el sistema, consumos AJAX y llenar select2
	CATEGORIA_ROL_USUARIO             : {{ config('constantes.CATEGORIA_ROL_USUARIO') }},
	CATEGORIA_TIPO_RELACION           : {{ config('constantes.CATEGORIA_TIPO_RELACION') }},
	CATEGORIA_ZONA_SUCURSAL           : {{ config('constantes.CATEGORIA_ZONA_SUCURSAL') }},
	CATEGORIA_TIPO_SUCURSAL           : {{ config('constantes.CATEGORIA_TIPO_SUCURSAL') }},
	CATEGORIA_TIPO_EMPRESA            : {{ config('constantes.CATEGORIA_TIPO_EMPRESA') }},
	CATEGORIA_TIPO_RETIRO             : {{ config('constantes.CATEGORIA_TIPO_RETIRO') }},
	CATEGORIA_TIPO_ESPECIE            : {{ config('constantes.CATEGORIA_TIPO_ESPECIE') }},
	CATEGORIA_TIPO_MATERIA_PRIMA      : {{ config('constantes.CATEGORIA_TIPO_MATERIA_PRIMA') }},
	CATEGORIA_TIPO_CAMION             : {{ config('constantes.CATEGORIA_TIPO_CAMION') }},
	CATEGORIA_GRUPO_TIPO_CAMION       : {{ config('constantes.CATEGORIA_GRUPO_TIPO_CAMION') }},
	CATEGORIA_ESTADOS_RETIRO          : {{ config('constantes.CATEGORIA_ESTADOS_RETIRO') }},
	CATEGORIA_CAMBIOS_PLANIFICACION   : {{ config('constantes.CATEGORIA_CAMBIOS_PLANIFICACION') }},

	// Valores usados para bifurcar lógicas
	TIPO_EMPRESA_PRODUCTORA       	: {{ config('constantes.TIPO_EMPRESA_PRODUCTORA') }},
	TIPO_EMPRESA_TRANSPORTISTA    	: {{ config('constantes.TIPO_EMPRESA_TRANSPORTISTA') }},
	TIPO_SUCURSAL_PLANTA          	: {{ config('constantes.TIPO_SUCURSAL_PLANTA') }},

	// Códigos de Roles de Usuario
	ROL_SOLICITANTE_PLANTA        	: {{ config('constantes.ROL_SOLICITANTE_PLANTA') }},
	ROL_SOLICITANTE_PRODUCTOR     	: {{ config('constantes.ROL_SOLICITANTE_PRODUCTOR') }},

	// Valores de Tipo de Retiro
	TIPO_RETIRO_TOLVA             	: {{ config('constantes.TIPO_RETIRO_TOLVA') }},
	TIPO_RETIRO_BINS              	: {{ config('constantes.TIPO_RETIRO_BINS') }},

	// Estados de Solicitudes de Retiro
	ESTADO_RETIRO_ESPERANDO       	: {{ config('constantes.ESTADO_RETIRO_ESPERANDO') }},
	ESTADO_RETIRO_COMENTADO       	: {{ config('constantes.ESTADO_RETIRO_COMENTADO') }},
	ESTADO_RETIRO_ACEPTADO        	: {{ config('constantes.ESTADO_RETIRO_ACEPTADO') }},
	ESTADO_RETIRO_PLANIFICADO     	: {{ config('constantes.ESTADO_RETIRO_PLANIFICADO') }},
	ESTADO_RETIRO_PROGRAMADO      	: {{ config('constantes.ESTADO_RETIRO_PROGRAMADO') }},
	ESTADO_RETIRO_TERMINADO       	: {{ config('constantes.ESTADO_RETIRO_TERMINADO') }},
	ESTADO_RETIRO_CANCELADO       	: {{ config('constantes.ESTADO_RETIRO_CANCELADO') }},
};
</script>
