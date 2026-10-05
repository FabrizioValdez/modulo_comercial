# Módulo comercial: Plan de Ataque

Módulo para el CRM en Laravel. Toma el Excel del plan de ataque comercial y arma un listado de cuentas con su ficha de proyecto, sus decisores (con foto), su facturación del año en curso y un prompt para investigar cada proyecto con la IA que prefieras.

**No llama a ninguna API de IA.** El CRM arma el prompt con los datos de la ficha, tú lo copias y lo usas en tu herramienta (Claude, ChatGPT, Perplexity…), y pegas la respuesta en la ficha. Si la respuesta viene en JSON, la ficha se completa sola.

## Requisitos

- PHP 7.4 y Laravel 8.
- PostgreSQL para el CRM (usa las tablas existentes `clients` y `quotations`).
- Conexión a SQL Server para el ERP (tablas `cliente` y `comprobante`). Requiere la extensión `pdo_sqlsrv`.
- `phpoffice/phpspreadsheet` para leer el Excel.

## Instalación

1. Copia las carpetas `app`, `config`, `database`, `public`, `resources` y `routes` sobre la raíz de tu CRM. Todo va en carpetas `Ataque/` o en archivos nuevos, así que no reemplaza nada existente.

2. Instala la librería del Excel. La versión 1.29 es compatible con PHP 7.4:

   ```bash
   composer require phpoffice/phpspreadsheet:^1.29
   ```

3. Agrega la conexión del ERP en `config/database.php`, dentro de `connections`:

   ```php
   'erp' => [
       'driver' => 'sqlsrv',
       'host' => env('ERP_DB_HOST'),
       'port' => env('ERP_DB_PORT', '1433'),
       'database' => env('ERP_DB_DATABASE', 'flowdesk'),
       'username' => env('ERP_DB_USERNAME'),
       'password' => env('ERP_DB_PASSWORD'),
       'charset' => 'utf8',
       'prefix' => '',
   ],
   ```

   Y en el `.env` (con un usuario de **solo lectura**):

   ```
   ERP_DB_HOST=
   ERP_DB_DATABASE=flowdesk
   ERP_DB_USERNAME=
   ERP_DB_PASSWORD=
   ```

4. Incluye las rutas al final de `routes/web.php`:

   ```php
   require __DIR__ . '/ataque.php';
   ```

5. Crea las tablas y el enlace público para las fotos:

   ```bash
   php artisan migrate
   php artisan storage:link
   ```

6. Importa el Excel. Se puede repetir sin duplicar: actualiza por empresa y proyecto.

   ```bash
   php artisan ataque:importar "storage/app/Plan_Estrategico_Ataque_Comercial_Aceros_F.xlsx"
   ```

7. Entra a `/ataque`.

### Usar el layout de tu CRM

Por defecto las vistas usan su propio layout (`resources/views/ataque/layout.blade.php`). Para que se vean dentro de tu menú, en el `.env`:

```
ATAQUE_LAYOUT=layouts.app
```

Ese layout tiene que tener `@yield('content')`. Los estilos del módulo van todos bajo la clase `.atq`, así que no tocan los del CRM.

## Qué hace cada pantalla

| Ruta | Pantalla |
|---|---|
| `/ataque` | Listado de cuentas: filtros por prioridad, búsqueda y decisor principal. |
| `/ataque/proyectos/{id}` | Ficha con **datos actuales**: resumen de lo actual, cliente en tu base con la facturación del año en curso, contratista, qué venderle, decisores con foto y actividad. |
| `/ataque/proyectos/{id}/investigar` | Arma el prompt (eliges personas, fuentes y la ventana de novedades), lo copias y pegas la respuesta. |
| `/ataque/investigaciones/{id}` | Informe guardado: estado, novedades, quién decide, personas, ángulo comercial y fuentes. |

## Facturación (ERP)

`app/Services/Ataque/ErpFacturacion.php` busca por RUC y devuelve solo el año en curso:

- **Neto facturado sin IGV** (`subtotal_loc`), restando las notas de crédito.
- Número de facturas y fecha de la última.
- **Saldo pendiente** de cualquier año, porque es lo que el cliente debe hoy.
- Cotizaciones del año, desde la tabla `quotations` del CRM.

Los tipos de comprobante están en `config/ataque.php`:

- Facturas: `90` y `94` (series F001 y F002).
- Notas de crédito: `91` y `95`.

Si en tu ERP hay otras series, agrégalas ahí. El resultado se guarda en caché 30 minutos.

## Relacionar con tus clientes

La ficha busca en `clients` los nombres parecidos y muestra un botón **Relacionar**. También puedes relacionar escribiendo el RUC. El contratista (por ejemplo, CUBIC 33 en Mapei) se relaciona aparte y muestra su propia facturación del año.

## Fotos y LinkedIn

- Las fotos las sube el equipo desde la ficha ("Editar o cambiar foto") y se guardan en `storage/app/public/ataque/decisores`.
- Si guardas la URL del perfil, el botón abre ese perfil. Si no, abre una búsqueda en LinkedIn con el nombre y la empresa.
- No hay scraping de LinkedIn: va contra sus términos y arriesga que bloqueen la cuenta.

## El prompt

Lo arma `app/Services/Ataque/PromptInvestigacion.php` con la empresa, el proyecto, la inversión, el contratista, las personas, el último contacto y lo que quieres vender. Pide la respuesta en este JSON:

```json
{
  "resumen_actual": "",
  "estado_proyecto": {"etapa": "", "avance": "", "ultimo_hito": "", "fecha": "", "fuente": ""},
  "novedades": [{"fecha": "", "titulo": "", "resumen": "", "url": ""}],
  "mapa_decision": [{"rol": "", "empresa": "", "nombre": "", "cargo": "", "fuente": ""}],
  "personas": [{"nombre": "", "cargo": "", "desde": "", "trayectoria": "", "publicaciones": "", "temas": "", "conexiones": "", "como_abordar": "", "linkedin": "", "fuentes": [], "confianza": ""}],
  "angulo_comercial": {"que": "", "a_quien": "", "cuando": "", "riesgos": ""},
  "fuentes": [{"titulo": "", "medio": "", "fecha": "", "url": ""}]
}
```

Al pegar la respuesta:

- `resumen_actual` pasa a ser el texto "Lo actual" de la ficha.
- Las personas nuevas se agregan como decisores **sugeridos por IA**, para que el equipo las confirme.
- Si la respuesta no es JSON, se guarda igual como texto.

## Archivos

```
app/Console/Commands/Ataque/ImportarPlan.php      php artisan ataque:importar
app/Http/Controllers/Ataque/                      Plan, Decisor, Investigacion
app/Models/Ataque/                                AtaqueProyecto, AtaqueDecisor, AtaqueActividad, AtaqueInvestigacion, ObraPublica
app/Services/Ataque/                              PromptInvestigacion, ErpFacturacion, ClienteMatcher
config/ataque.php
database/migrations/2026_10_05_100000_create_ataque_tables.php
public/css/ataque.css
resources/views/ataque/
routes/ataque.php
```
