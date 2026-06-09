# Lead Gen Tool

### Plataforma SaaS de generación de leads locales y auditoría digital

Lead Gen Tool es una aplicación web desarrollada en PHP nativo para encontrar negocios locales por código postal y nicho, extraer datos públicos desde OpenStreetMap / Overpass API y detectar oportunidades comerciales mediante análisis de presencia digital.

La herramienta no solo lista negocios: identifica automáticamente webs mejorables, negocios sin presencia digital sólida y posibles clientes potenciales para auditorías web, SEO o servicios de marketing digital.

---

# Características destacadas

✅ Búsqueda de negocios por código postal y nicho
✅ Integración con OpenStreetMap / Overpass API
✅ Detección automática de webs mejorables
✅ Validación de dominios para reducir falsos positivos
✅ Dashboard SaaS responsive
✅ Sistema de scoring comercial inteligente
✅ Exportación CSV compatible con Excel
✅ Docker + MySQL + phpMyAdmin
✅ Cache local para optimización de consultas
✅ Modo claro y oscuro
✅ Análisis básico de presencia digital
✅ Detección de WordPress, Elementor y WooCommerce
✅ Filtros inteligentes por oportunidad comercial

---

# Tecnologías utilizadas

* PHP nativo
* MySQL
* Docker
* phpMyAdmin
* HTML5
* CSS3
* JavaScript
* OpenStreetMap
* Overpass API

---

# Instalación local

## Requisitos

* PHP 8.1 o superior
* Extensión cURL habilitada
* Extensión PDO MySQL (opcional)
* Acceso a internet

## Pasos

```bash
cp .env.example .env
php -S 127.0.0.1:8000
```

Abrir en navegador:

```text
http://127.0.0.1:8000
```

Si no quieres usar MySQL:

```env
DB_ENABLED=false
```

---

# Instalación con Docker

## Requisitos

* Docker
* Docker Compose

## Inicio rápido

```bash
cp .env.example .env
docker compose up -d
```

## Servicios disponibles

| Servicio   | URL                   |
| ---------- | --------------------- |
| Aplicación | http://127.0.0.1:8000 |
| phpMyAdmin | http://127.0.0.1:8081 |
| MySQL      | mysql:3306            |

La base de datos se inicializa automáticamente utilizando:

```text
database/schema.sql
```

---

# Configuración

Toda la configuración sensible se gestiona mediante variables de entorno.

El repositorio incluye:

```text
.env.example
```

como plantilla pública segura.

## Variables principales

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

---

# Estructura del proyecto

```text
.
├── api/                  # Endpoints auxiliares
├── assets/               # CSS y JavaScript del dashboard
├── config/               # Configuración y entorno
├── database/             # Schema SQL inicial
├── exports/              # Exportación CSV
├── services/             # Lógica de negocio y análisis
├── storage/              # Cache y debug local
├── views/                # Plantillas PHP
├── Dockerfile
├── docker-compose.yml
├── index.php
└── README.md
```

---

# Cómo funciona

## Flujo principal

```text
Código postal
   ↓
Conversión a coordenadas
   ↓
Consulta Overpass API
   ↓
Extracción de negocios
   ↓
Normalización y deduplicación
   ↓
Validación de dominios
   ↓
Análisis de presencia digital
   ↓
Cálculo de score comercial
   ↓
Dashboard y exportación CSV
```

---

# Sistema de puntuación

Cada negocio recibe un score de oportunidad comercial de 0 a 100.

Cuanto mayor es el score, mayor potencial tiene el negocio para ofrecer servicios de auditoría web o mejora digital.

## Factores analizados

* Ausencia de web
* Web caída o no responsive
* Sin HTTPS
* Falta de teléfono o email
* Dirección incompleta
* Sin redes sociales visibles
* Sin formulario de contacto
* Sin CTA clara
* Sin favicon
* Sin meta viewport
* Sin meta description
* Sin Open Graph
* Dominio sospechoso o poco relacionado
* Uso de WordPress / Elementor / WooCommerce

## Niveles

| Nivel      | Descripción                 |
| ---------- | --------------------------- |
| Lead Alto  | Alta oportunidad comercial  |
| Lead Medio | Oportunidad interesante     |
| Lead Bajo  | Presencia digital aceptable |

---

# Exportación CSV

La aplicación permite exportar resultados compatibles con Excel incluyendo:

* Nombre
* Categoría
* Dirección
* Teléfono
* Email
* Web
* Coordenadas
* Score
* Nivel
* Problemas detectados
* Redes sociales
* SSL
* Responsive
* WordPress
* Elementor
* WooCommerce
* Estado de la web
* Tiempo de carga

---

# Base de datos

La persistencia MySQL es opcional pero recomendada.

## Base de datos por defecto

```text
lead_gen_tool
```

## Schema SQL

```text
database/schema.sql
```

La aplicación también utiliza:

```text
storage/cache/
```

para reducir llamadas repetidas a Overpass API y optimizar auditorías.

---

# Seguridad

## Medidas implementadas

* Variables sensibles fuera del repositorio mediante `.env`
* `.gitignore` configurado correctamente
* Cache y debug excluidos del repositorio
* Validación de inputs
* Salida HTML escapada
* Timeouts cortos para peticiones externas
* Validación básica de coincidencia entre negocio y dominio
* No se inventan webs sin fuentes fiables

---

# Mejoras futuras

* Panel detallado por negocio
* Informes PDF de auditoría
* Autenticación de usuarios
* Historial de auditorías
* Integración con APIs adicionales
* Mapa interactivo de negocios
* Configuración avanzada de scoring
* Tests automatizados
* Automatización de análisis periódicos

---

# Autor

Proyecto desarrollado como herramienta de generación de leads locales y auditoría digital enfocada en detectar oportunidades comerciales mediante análisis de presencia online.

### Stack principal

* PHP Native
* Docker
* MySQL
* OpenStreetMap / Overpass API
* Dashboard SaaS UI
* Auditoría digital automatizada

