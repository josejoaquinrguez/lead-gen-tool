# Lead Gen Tool - Extractor y Analizador de Negocios Locales

Lead Gen Tool es una aplicacion web en PHP nativo para encontrar negocios locales por codigo postal y nicho, extraer datos publicos desde OpenStreetMap / Overpass API y priorizar oportunidades comerciales para auditorias digitales.

El objetivo no es solo listar negocios. La herramienta ayuda a detectar leads con carencias digitales: negocios sin web, webs caidas, webs poco optimizadas, falta de datos de contacto, ausencia de redes visibles o senales debiles de conversion.

## Caracteristicas

- Busqueda de negocios por codigo postal y palabra clave.
- Integracion con OpenStreetMap / Overpass API.
- Extraccion de datos publicos: nombre, direccion, telefono, web, categoria y coordenadas.
- Normalizacion de nichos comunes como restaurantes, hoteles, inmobiliarias, clinicas, estetica, supermercados y otros.
- Analisis basico de presencia digital.
- Scoring de oportunidad comercial de 0 a 100.
- Filtros inteligentes por prioridad, web mejorable, sin web, web dudosa y web caida.
- Validacion de coincidencia entre nombre del negocio y dominio para reducir falsos positivos.
- Exportacion CSV compatible con Excel.
- Cache local para evitar repetir consultas innecesarias.
- Persistencia opcional en MySQL.
- Entorno completo con Docker, MySQL y phpMyAdmin.
- Dashboard responsive con modo claro y oscuro.

## Tecnologias Utilizadas

- PHP nativo
- MySQL
- Docker
- phpMyAdmin
- HTML
- CSS
- JavaScript
- OpenStreetMap
- Overpass API

## Capturas

Puedes anadir capturas del proyecto en una carpeta `docs/screenshots/` y enlazarlas aqui:

```md
![Dashboard oscuro](docs/screenshots/dashboard-dark.png)
![Dashboard claro](docs/screenshots/dashboard-light.png)
```

## Instalacion Local

Requisitos:

- PHP 8.1 o superior
- Extension cURL de PHP
- Extension PDO MySQL si quieres guardar resultados en base de datos
- Acceso a internet para consultar Overpass API y analizar webs publicas

Pasos:

```bash
cp .env.example .env
php -S 127.0.0.1:8000
```

Despues abre:

```text
http://127.0.0.1:8000
```

Si no vas a usar MySQL en local, puedes configurar:

```env
DB_ENABLED=false
```

## Instalacion con Docker

Requisitos:

- Docker
- Docker Compose

Pasos:

```bash
cp .env.example .env
docker compose up -d
```

Servicios disponibles:

- Aplicacion: `http://127.0.0.1:8000`
- phpMyAdmin: `http://127.0.0.1:8081`
- MySQL: servicio interno `mysql:3306`

La base de datos se inicializa automaticamente con:

```text
database/schema.sql
```

Nota: si ya existe un volumen de MySQL creado anteriormente, Docker no volvera a importar el schema automaticamente. En ese caso, elimina el volumen solo si no necesitas sus datos.

## Configuracion

La configuracion sensible se gestiona mediante variables de entorno. El repositorio incluye `.env.example` como plantilla publica.

Variables principales:

```env
APP_DOCKER=true
DB_ENABLED=true
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=lead_gen_tool
DB_USERNAME=root
DB_PASSWORD=change_me_for_local_development
DB_CHARSET=utf8mb4
OVERPASS_API_URL=https://overpass-api.de/api/interpreter
TAVILY_API_KEY=
```

Archivos `.env`, `.env.local`, `.env.production` y variantes locales estan excluidos del repositorio.

## Estructura del Proyecto

```text
.
├── api/                  # Endpoints auxiliares
├── assets/               # CSS y JavaScript del dashboard
├── config/               # Configuracion de entorno y base de datos
├── database/             # Schema SQL inicial
├── exports/              # Exportacion CSV
├── services/             # Logica de busqueda, analisis y persistencia
├── storage/              # Cache y debug local, no versionado
├── views/                # Plantillas PHP de interfaz
├── Dockerfile
├── docker-compose.yml
├── index.php
└── README.md
```

## Como Funciona

Flujo principal:

```text
Codigo postal
  -> coordenadas internas
  -> consulta Overpass API
  -> normalizacion de negocios
  -> eliminacion de duplicados
  -> validacion de web oficial
  -> analisis de presencia digital
  -> calculo de score
  -> dashboard y exportacion CSV
```

## Sistema de Puntuacion

Cada negocio recibe una puntuacion de oportunidad comercial de 0 a 100. Una puntuacion mas alta significa mayor potencial para ofrecer una auditoria digital.

Factores que aumentan la oportunidad:

- No tiene web.
- La web no responde o devuelve errores.
- La web no usa HTTPS.
- Falta telefono, email o datos de contacto.
- Direccion incompleta.
- No hay Instagram, Facebook o WhatsApp visible.
- No hay formulario de contacto.
- No hay llamadas a la accion claras.
- No hay favicon.
- No hay meta viewport o responsive basico.
- No hay meta description u Open Graph.
- Dominio dudoso o poco relacionado con el nombre del negocio.
- Deteccion de WordPress, Elementor o WooCommerce como posibles oportunidades de auditoria.

Niveles:

- Lead Alto: oportunidad prioritaria.
- Lead Medio: oportunidad interesante.
- Lead Bajo: presencia digital aceptable o menor urgencia.

## Exportacion CSV

La aplicacion permite exportar resultados con columnas como:

- Nombre
- Categoria
- Direccion
- Telefono
- Email
- Web
- Coordenadas
- Score
- Nivel
- Problemas detectados
- Redes sociales
- WordPress
- Elementor
- WooCommerce
- Responsive
- SSL
- Estado de la web
- Tiempo de carga

El CSV se genera con separador compatible con Excel y codificacion preparada para caracteres en espanol.

## Base de Datos

MySQL es opcional, pero recomendado para conservar resultados entre sesiones.

La base de datos por defecto es:

```text
lead_gen_tool
```

El schema inicial se encuentra en:

```text
database/schema.sql
```

La aplicacion tambien mantiene cache local en `storage/cache/` para evitar repetir llamadas a Overpass y analisis de webs continuamente.

## Seguridad

Medidas incluidas:

- Variables sensibles fuera del repositorio mediante `.env`.
- `.env` y variantes locales ignoradas por Git.
- Cache, logs y archivos de debug excluidos del repositorio.
- Inputs validados y salida HTML escapada.
- Timeouts cortos para peticiones externas.
- No se inventan webs cuando no hay una fuente fiable.
- Validacion basica de coincidencia entre nombre del negocio y dominio.
- Uso de Overpass API como fuente gratuita y publica.

Importante: si alguna clave real fue subida antes al historial de Git, debe rotarse o eliminarse del historial antes de publicar el repositorio de forma publica.

## Mejoras Futuras

- Integrar una fuente gratuita adicional para enriquecer telefonos, emails y redes sin scraping agresivo.
- Mejorar la deteccion de negocios cerrados permanentemente con fuentes complementarias.
- Anadir panel de detalle por negocio.
- Guardar auditorias historicas por fecha.
- Anadir autenticacion basica para uso interno.
- Crear informes PDF de auditoria por lead.
- Mejorar el sistema de scoring con pesos configurables.
- Ampliar el mapa interno de codigos postales.
- Anadir pruebas automatizadas para normalizacion, deduplicacion y scoring.

## Autor

Proyecto desarrollado por Jose Joaquin Rodriguez como herramienta de generacion de leads locales y auditoria digital.
