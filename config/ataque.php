<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Vista y rutas
    |--------------------------------------------------------------------------
    | layout: cámbialo por el layout de tu CRM (debe tener @yield('content')).
    */
    'layout' => env('ATAQUE_LAYOUT', 'ataque.layout'),
    'middleware' => ['web', 'auth'],
    'prefix' => 'ataque',

    /*
    |--------------------------------------------------------------------------
    | ERP (SQL Server) para la facturación
    |--------------------------------------------------------------------------
    | Nombre de la conexión definida en config/database.php.
    | Tipos de comprobante según tu tabla tipocomprobante / familiacomprobante:
    |   90, 94 = Factura (F001, F002)    91, 95 = Nota de crédito (F001, F002)
    */
    'erp_connection' => env('ATAQUE_ERP_CONNECTION', 'erp'),
    'tipos_factura' => [90, 94],
    'tipos_nota_credito' => [91, 95],
    'cache_minutos' => 30,

    /*
    |--------------------------------------------------------------------------
    | Prompt de investigación
    |--------------------------------------------------------------------------
    | El CRM no llama a ninguna IA: arma el prompt para copiarlo y luego
    | se pega la respuesta en la ficha.
    */
    'empresa_vendedora' => env('ATAQUE_EMPRESA', 'una empresa peruana que vende acero (corrugado, fierro figurado, perfiles, planchas, tuberías e inoxidable)'),
    'fuentes' => [
        'linkedin' => 'Perfiles públicos de LinkedIn (site:linkedin.com/in y /company)',
        'prensa' => 'Prensa económica peruana (Gestión, Semana Económica, El Comercio, Perú Construye)',
        'produce' => 'PRODUCE y SEIA (informes ITS y EIA)',
        'seace' => 'SEACE / OSCE (licitaciones y contratos públicos)',
        'municipal' => 'Licencias de edificación municipales',
        'web' => 'Webs y redes oficiales de la empresa y del contratista',
    ],
    'fuentes_default' => ['linkedin', 'prensa', 'produce', 'municipal', 'web'],

    /*
    |--------------------------------------------------------------------------
    | Fotos de decisores
    |--------------------------------------------------------------------------
    */
    'fotos_disk' => 'public',
    'fotos_carpeta' => 'ataque/decisores',

    'prioridades' => [
        'inmediato' => 'Ataque inmediato',
        'mediano' => 'Mediano plazo',
        'seguimiento' => 'Seguimiento',
        'stand_by' => 'Stand by',
        'identificado' => 'Identificado',
        'revision' => 'Revisión',
        'asignado' => 'Asignado',
    ],

    'roles_compra' => [
        'decisor' => 'Decisor final',
        'influenciador' => 'Influenciador',
        'tecnico' => 'Evaluador técnico',
        'compras' => 'Compras',
        'contacto' => 'Contacto',
    ],
];
