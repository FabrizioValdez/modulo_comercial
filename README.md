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

7. Importa las personas y fotos del reporte de inteligencia. El paquete `datos-personas` **no está en este repositorio** porque el repositorio es público; se entrega aparte. Cópialo al servidor, por ejemplo en `storage/app/datos-personas`, y corre:

   ```bash
   php artisan ataque:importar-personas storage/app/datos-personas
   ```

   El comando:
   - Cruza cada persona con los decisores que ya vinieron del Excel, por nombre y sin importar tildes (por ejemplo, "Pedro Ríos" con "Pedro Ríos García").
   - Copia las fotos al disco público.
   - Guarda los datos de la empresa y las alertas, como la baja de oficio en SUNAT.
   - Se puede repetir sin duplicar.

8. Entra a `/ataque` desde el escritorio, o a `/ataque/movil` desde el teléfono.

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

## App móvil (`/ataque/movil`)

Es una app web instalable. En el teléfono, abre `/ataque/movil` en Chrome o Safari y elige **"Agregar a la pantalla de inicio"**.

**Sin conexión** muestra lo último que guardó:
- Proyectos con su prioridad.
- Alertas.
- Lo actual y los datos de la empresa.
- Personas con foto, cargo y universidad.
- Qué venderle, el próximo paso, la actividad y las noticias ya descargadas.

Para tener todo listo antes de salir a campo, toca **"Guardar todo para usar sin conexión"**: descarga los datos, todas las fotos y las noticias de cada proyecto.

**Con conexión**:
- Actualiza los datos sola.
- Trae las noticias de cada proyecto y de cada persona desde varias fuentes.
- Tiene botones a **LinkedIn**: el perfil guardado o una búsqueda por nombre y empresa, y la página de la empresa.
- Tiene botones a **Google** y **Google Noticias**.

La facturación **no** viaja al teléfono.

### Fuentes de noticias

Las fuentes se configuran en `config/ataque.php` → `noticias`:

- `busquedas`: buscadores de noticias por RSS. Vienen Google Noticias y Bing Noticias, y `{q}` se reemplaza por la empresa (y por una palabra del proyecto, como "Lurín").
- `feeds`: RSS de medios que sigas (Gestión, Semana Económica, El Comercio, etc.). Se filtran por el nombre de la empresa.

El servidor lee los titulares, quita los duplicados y guarda el resultado en caché una hora (`NoticiasService`).

### LinkedIn

Solo abre enlaces: el perfil guardado o una búsqueda. No consulta la API de LinkedIn ni hace scraping, porque va contra sus términos.

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
app/Console/Commands/Ataque/ImportarPersonas.php  php artisan ataque:importar-personas
app/Http/Controllers/Ataque/                      Plan, Decisor, Investigacion, Movil
app/Models/Ataque/                                AtaqueProyecto, AtaqueDecisor, AtaqueActividad, AtaqueInvestigacion, ObraPublica
app/Services/Ataque/                              PromptInvestigacion, ErpFacturacion, ClienteMatcher, NoticiasService
config/ataque.php
database/migrations/                              tablas del módulo + campos de inteligencia (2026_10_12)
public/css/ataque.css
public/js/ataque-movil.js                         app móvil (offline, noticias, enlaces)
public/img/ataque-icon-*.png                      íconos de la app
resources/views/ataque/                           incluye movil.blade.php y sw-movil.blade.php (service worker)
routes/ataque.php
```
