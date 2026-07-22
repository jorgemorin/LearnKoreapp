# LearnKoreapp — Especificación de Mejoras y Nuevas Funcionalidades

> Este documento detalla técnica y funcionalmente la lista de mejoras propuestas. Servirá como guía (prompt) para la implementación futura de cada característica, asegurando que la arquitectura, el diseño y la base de datos evolucionen de forma coherente.

---

## 1. Sistema de Reportes de Usuarios a Administradores

### Objetivo
Permitir que los usuarios autenticados puedan notificar problemas específicos a los administradores (ej. errores en una traducción, análisis morfológico incorrecto de la IA, bugs de la aplicación, etc.).

### Comportamiento esperado
- **Lado Usuario:**
  - Botón o enlace "Reportar un problema" accesible globalmente (ej. en el footer o en el menú del usuario).
  - Modal o formulario que solicite:
    - `Categoría`: Selector (Error de traducción, Error de análisis IA, Bug técnico, Otro).
    - `Descripción`: Área de texto detallada.
    - `Contexto` (opcional): Si se reporta desde la vista de una tarjeta específica, el ID de la tarjeta se adjunta automáticamente.
  - Al enviar, se notifica al usuario que el equipo revisará su caso.
- **Lado Admin:**
  - Nueva sección "Reportes" en el panel de administración.
  - Lista de tickets con estados (`Pendiente`, `En revisión`, `Resuelto`).
  - Capacidad para cambiar el estado, añadir notas internas y enlazar directamente al ítem reportado para corregirlo.

### Requisitos Técnicos
- Nueva tabla `user_reports` (id, user_id, category, description, related_item_id, related_item_type, status, admin_notes, timestamps).
- Endpoints API para creación (usuario) y gestión (admin).
- Componente Livewire para la gestión de tickets en el backoffice.

---

## 2. Panel de Administración de Base de Datos (CRUD Completo)

### Objetivo
Proporcionar a los administradores herramientas completas de gestión sobre los datos principales del sistema, reduciendo la dependencia de acceso directo a la base de datos o consola para mantenimiento y soporte.

### Comportamiento esperado
- **Gestión de Usuarios:** Ver lista completa, estadísticas de uso, posibilidad de banear/activar cuentas, restablecer contraseñas, o editar datos básicos.
- **Gestión de Vocabulario (Compounds & Entities):**
  - Buscador global avanzado que ignore la paginación tradicional.
  - Editor completo que permita modificar traducciones, reasignar morfemas (entities) a compuestos, alterar el orden, y purgar tarjetas corruptas.
- **Auditoría:** Registro (logs) de acciones administrativas sensibles para trazabilidad.

### Requisitos Técnicos
- Ampliación del actual `AdminController`.
- Vistas de tabla complejas, idealmente con búsqueda en tiempo real usando Livewire.

---

## 3. Motor de Repetición Espaciada Completo (Lógica Anki / FSRS)

### Objetivo
Reemplazar el algoritmo actual simplificado por un modelo SRS profesional, equivalente o superior al de Anki, introduciendo los 4 botones de evaluación y el ciclo de vida real de las tarjetas (learning, review, relearning).

### Comportamiento esperado
- **4 Botones de Calificación:** 
  1. `Otra vez (Again)`: Fallo completo. Resetea la facilidad y devuelve la carta a la fase de aprendizaje o reaprendizaje.
  2. `Difícil (Hard)`: Recordado con dificultad. Aumenta ligeramente el intervalo, disminuye la facilidad.
  3. `Bien (Good)`: Recordado normalmente. Multiplica el intervalo por la facilidad actual.
  4. `Fácil (Easy)`: Recordado instantáneamente. Multiplica el intervalo por la facilidad y un bonus adicional.
- **Estados de Madurez:**
  - `New`: Nunca vista.
  - `Learning`: Pasando por los pasos iniciales (ej. 1m, 10m).
  - `Young`: Graduada pero con intervalo < 21 días.
  - `Mature`: Intervalo ≥ 21 días.
- **Configuración:** (Opcional a futuro) Permitir al usuario modificar sus pasos de aprendizaje (ej. `1m 10m 1d`).

### Requisitos Técnicos
- Modificar el esquema de `user_progress` para incluir: `card_state` (new, learning, review, relearning), `lapses` (fallos), `learning_step_index`.
- Refactorización total de `SrsService` para calcular intervalos basándose en el algoritmo elegido (ej. SM-2 completo o FSRS).
- Actualizar la UI de `ReviewSession` para mostrar 4 botones con sus tiempos estimados.

---

## 4. Edición Manual de Intervalos de Repaso

### Objetivo
Dar flexibilidad al usuario para sobreescribir las decisiones del algoritmo cuando necesite seguir repasando una tarjeta que el sistema considera aprendida.

### Comportamiento esperado
- En la interfaz de "Mi Colección", añadir una opción en el menú contextual de cada tarjeta: "Ajustar repaso".
- Modal que permita introducir manualmente "Ver de nuevo en X días" o forzar el retorno de la carta al estado "Learning" (reaprender).
- Útil para preparar exámenes inminentes o refrescar conceptos que, aunque tengan intervalo alto, el usuario siente inseguros.

### Requisitos Técnicos
- Endpoint `PUT /api/collection/{id}/interval` que actualice directamente `next_review_date` en la tabla `user_progress` y genere un registro especial de auditoría en `study_logs` (ej. `is_manual_override = true`).

---

## 5. Rediseño Profesional de Interfaz (Estética Grid/Sólida)

### Objetivo
Alejarse de la estética "pastelosa/smooth" (bordes muy redondeados, sombras difusas, glassmorphism) hacia un diseño más profesional, estructurado, denso en información y menos estimulante (dopamínico).

### Pautas de Diseño
- **Geometría:** Reducir el `border-radius` (ej. de 12px/16px a 4px/6px o totalmente cuadrados).
- **Colores:** Cambiar a un tema oscuro más puro (grises oscuros/negros verdaderos) con acentos desaturados en lugar de morados/verdes brillantes.
- **Layout:** Mayor uso de líneas divisorias finas (borders) en lugar de tarjetas flotantes con sombras. Inspiración en herramientas de productividad para desarrolladores (ej. Linear, Notion, Raycast).
- **Tipografía:** Fomentar el uso de tipografía monoespaciada o sans-serif muy geométrica para métricas y datos, reduciendo los tamaños de fuente excesivos para condensar más información por pantalla.

---

## 6. Colección Interactiva y Gestión Avanzada

### Objetivo
Transformar "Mi Colección" en un verdadero gestor de base de conocimiento, y no solo en una lista de lectura pasiva.

### Comportamiento esperado
- **Selección Múltiple:** Checkboxes para seleccionar múltiples tarjetas a la vez.
- **Acciones en Lote:** Suspender tarjetas masivamente (pausar repasos), resetear su progreso SRS a 0, o añadirles una misma etiqueta de golpe.
- **Edición Inline:** Posibilidad de corregir la traducción de una tarjeta directamente desde la lista, sin tener que ir a un formulario separado.

---

## 7. Análisis de Resultados e Insights con IA

### Objetivo
Aprovechar la LLM integrada no solo para procesar vocabulario, sino para analizar el rendimiento del estudiante y ofrecer retroalimentación en lenguaje natural.

### Comportamiento esperado
- En el dashboard de estadísticas, añadir una sección "Tu Coach de IA".
- Se enviará a la IA un resumen de las métricas del usuario (precisión por etiquetas, tipos morfológicos que más falla, rachas de estudio).
- La IA devolverá:
  - **Diagnóstico:** (Ej. "Estás confundiendo frecuentemente las partículas de lugar '에서' y '에'").
  - **Recomendación:** (Ej. "Deberías dedicar tu próxima sesión exclusivamente a conjunciones, donde tu precisión es del 45%").
- Este análisis no se generará en cada carga (para ahorrar costes/tokens), sino bajo demanda (botón "Generar informe") o semanalmente (vía CRON).

---

## 8. Taxonomía Estandarizada de Etiquetas (Tags)

### Objetivo
Evitar la proliferación de etiquetas inútiles ("basura" semántica) forzando el uso de una plantilla estructurada y jerárquica.

### Estructura de la Taxonomía
- **Capa 1: Gramática (Pública/Filtros principales)**: Verbo, Sustantivo, Adjetivo, Adverbio, Partícula, Conjunción, Expresión.
- **Capa 2: Registro/Formalidad**: Formal, Informal, Honorífico.
- **Capa 3: Temática/Situacional (Ocultas/Búsqueda avanzada)**: Cafetería, Estudios, Objetos, Transporte, Comida.

### Requisitos Técnicos
- Crear una tabla `tag_categories` o pre-poblar los tags principales marcándolos como "estándar".
- Modificar el prompt en `ClaudeAIParserService` para que *obligatoriamente* elija etiquetas de una lista predefinida proporcionada, limitando la creación de etiquetas arbitrarias.

---

## 9. Verificación de Segmentación de Frases por IA

### Objetivo
Garantizar que el sistema soporta la ingesta de oraciones completas y que la IA las descompone exitosamente en conceptos atómicos (entities), guardando cada uno por separado pero manteniendo la cohesión de la oración (compound).

### Comportamiento esperado
- Al introducir: "저는 학교에 갑니다" (Yo voy a la escuela).
- La IA debe aislar: 저 (yo) + 는 (tema) + 학교 (escuela) + 에 (dirección) + 갑니다 (ir, formal).
- El sistema debe guardar el Compound completo y enlazarlo en la tabla pivote a las 5 entities creadas/existentes.
- **Requisito Técnico:** Asegurar que los Jobs de procesamiento de IA manejen correctamente grandes arrays de `entities` sin fallos de timeout, y que la UI de revisión sea capaz de renderizar frases largas compuestas de múltiples componentes sin romperse visualmente.

---

## 10. Optimización de Búsqueda y Filtrado en Colección

### Objetivo
Permitir que el usuario encuentre cualquier información rápidamente en su colección, asegurando que la base de datos escale sin degradar el rendimiento de las consultas.

### Comportamiento esperado
- Filtros combinados: Búsqueda por palabra clave + Selección múltiple de Tags + Rango de Fechas + Estado SRS (ej. "Mostrar Verbos Formales que tengo en estado Relearning").

### Requisitos Técnicos
- Crear índices en PostgreSQL (y migraciones correspondientes):
  - Índices GIN / Full-Text Search para la búsqueda de `full_text` y `translation` en `compounds`.
  - Índices compuestos en `user_progress` (user_id + card_state + next_review_date).
- Refactorizar las consultas Eloquent de la colección para asegurar que se utilicen estos índices, evitando escaneos secuenciales masivos.
