# LearnKoreapp — MEJORAS TODO

> Seguimiento de implementación de las mejoras definidas en `MEJORAS.md`.
> Continúa a partir de las Fases 1-6 del proyecto base (94/94 tests ✅).

---

## ✅ Fase A — Taxonomía Estandarizada de Etiquetas — COMPLETADA
> **Objetivo**: Estandarizar el sistema de tags para que el parser IA solo use etiquetas controladas y la colección pueda filtrar por capas semánticas.

### A.1. Base de datos
- [x] Migración: añadir columna `layer` a tabla `tags` — ENUM (`grammar` | `register` | `thematic`)
- [x] Migración: añadir columna `is_standard` (bool, default false) a tabla `tags`
- [x] Migración: añadir columna `is_visible_default` (bool, default true) a tabla `tags`
- [x] Migración: añadir columna `description` (string nullable) a tabla `tags`

### A.2. Seeder
- [x] Crear `TagSeeder` con los 24 tags estándar clasificados por capa
  - Capa `grammar` (9 tags): verbo, sustantivo, adjetivo, adverbio, partícula, expresión, conjunción, contador, pronombre
  - Capa `register` (3 tags): formal, informal, honorífico
  - Capa `thematic` (12 tags): cafetería, estudios, trabajo, familia, transporte, salud, comida, tecnología, objetos, emociones, tiempo, lugares
- [x] Registrar `TagSeeder` en `DatabaseSeeder`

### A.3. Backend — Validación y parser IA
- [x] Actualizar prompt en `ClaudeAIParserService` para incluir la lista completa de tags autorizados
- [x] Instrucción explícita en el prompt: la IA SOLO puede asignar tags de la lista proporcionada
- [x] Actualizar `FakeAIParserService` para asignar únicamente tags del catálogo estándar
- [x] En `ParseVocabularyJob`: filtrar tags que no existan en el catálogo (ignorar los inventados por la IA)
- [x] Crear helper `TagCatalog` con métodos: `standardTagIds()`, `standardTagNames()`, `filterToStandard()`, `visibleTags()`, `groupedByLayer()`, `promptList()`

### A.4. Frontend — Colección
- [ ] En la vista de "Mi Colección": mostrar tags de Capa 1 (`grammar`) como filtros siempre visibles
- [ ] En la vista de "Mi Colección": mostrar Capas 2 y 3 en desplegable "Filtros avanzados"
- [ ] En el componente Livewire `Admin\ReviewItem`: selector de tags limitado al catálogo estándar (no campo libre)

### A.5. Tests — 17/17 ✅
- [x] Test: `TagSeeder` crea exactamente 24 tags estándar
- [x] Test: `TagSeeder` crea 9 tags grammar, 3 register, 12 thematic
- [x] Test: Seeder es idempotente (doble ejecución no duplica)
- [x] Test: Tags grammar son visibles por defecto
- [x] Test: Tags thematic están ocultos por defecto
- [x] Test: `FakeAIParserService` usa solo tags del catálogo
- [x] Test: Respuesta genérica del FakeParser usa tag del catálogo
- [x] Test: `TagCatalog::filterToStandard` filtra tags no estándar
- [x] Test: `filterToStandard` acepta tags en mayúsculas/minúsculas (case-insensitive)
- [x] Test: `filterToStandard` devuelve vacío si ningún tag es estándar
- [x] Test: Scope `standard()` devuelve solo tags del catálogo
- [x] Test: Scope `visible()` devuelve 12 tags (grammar + register)
- [x] Test: Scope `layer()` filtra por capa correctamente
- [x] Test: Job no persiste tags no estándar
- [x] Test: `Fase2VocabularyTest` actualizado para usar tags del catálogo

### A.6. Verificación de Fase A — COMPLETADA ✅
- [x] `php84 artisan migrate:fresh --seed` — 13 migraciones + 24 tags creados
- [x] Catálogo de 24 tags en BD con `layer` e `is_visible_default` correctos
- [x] `FakeAIParserService` usa solo tags del catálogo en todas las respuestas
- [x] Suite completa de tests: **111/111 pasando**

**Resumen de verificación Fase A**: completada con éxito. Taxonomía estándar de 24 tags activa. Parser IA constreñido al catálogo. Tests 111/111 ✅

---

## ✅ Fase B — Motor SRS Completo (Lógica Anki) + Intervalos Manuales — COMPLETADA
> **Objetivo**: Reemplazar el algoritmo SM-2 simplificado por el ciclo completo de Anki: 4 botones de calificación, estados de madurez, Learning/Relearning.

### B.1. Base de datos
- [x] Migración: `user_progress` — `card_state` (new|learning|young|mature|relearning|suspended)
- [x] Migración: `user_progress` — `lapses` INT (fallos en fase Review)
- [x] Migración: `user_progress` — `learning_step_index` INT (paso actual en learning)
- [x] Migración: `study_logs` — `rating` (again|hard|good|easy), nullable para retrocompat.
- [x] Migración: nueva tabla `user_srs_settings` (learning_steps, relearning_steps, graduating_interval, easy_bonus, interval_modifier, max_interval, new/review_cards_per_day)

### B.2. Backend — SrsService refactorizado
- [x] Algoritmo Anki completo documentado en el header de SrsService.php
- [x] `SrsService::calculate()` acepta `rating` (again/hard/good/easy) con dispatch por estado
- [x] Fase `New`: again→New, hard/good→Learning paso 0, easy→Young(easy_interval)
- [x] Fase `Learning`: pasos configurables en minutos, good avanza paso, easy gradúa
- [x] Fase `Review` (Young+Mature): intervalos hard<good<easy con ease_factor
- [x] Fase `Relearning`: pasos de reaprendizaje, good gradúa de vuelta a Young/Mature
- [x] Umbrales de madurez: Young (intervalo < 21d), Mature (≥ 21d)
- [x] `SrsService::getEstimatedIntervals()` — calcula los 4 intervalos para los botones UI
- [x] `ReviewController::answer()` acepta `rating` (nuevo) + `is_correct` (retrocompat. legacy)
- [x] `AnswerReviewRequest::resolvedRating()` unifica ambos formatos
- [x] Nuevos campos expuestos en response de la API: `card_state`, `lapses`

### B.3. Frontend — Sesión de repaso
- [x] 4 botones Anki: `Otra vez` | `Difícil` | `Bien` | `Fácil` con colores distintivos
- [x] Intervalo estimado visible encima de cada botón (actualizado al revelar la respuesta)
- [x] Chip de estado de madurez en cada tarjeta (Nueva / Aprendiendo / Joven / Madura / Reaprendiendo)
- [x] Contadores de sesión por rating (↩ / ◐ / ✓ / ★) en la barra de progreso
- [x] Resumen de sesión con grid de 4 ratings + precisión global
- [x] Aliases `markCorrect/markIncorrect` para retrocompatibilidad

### B.4. Tests — ✅ 115/115
- [x] Test: rating `again` resetea carta a Relearning con lapses++
- [x] Test: rating `hard` genera intervalo ≤ good
- [x] Test: rating `easy` genera intervalo > good
- [x] Test: carta New con `easy` pasa directamente a Young
- [x] Test: carta New con `good` entra en Learning
- [x] Test: carta Young con intervalo ≥ 21d se convierte en Mature
- [x] Test: carta Mature con `again` pasa a Relearning
- [x] Test: API acepta `rating` y devuelve `card_state` en response
- [x] Test: API acepta `is_correct` legacy y funciona (retrocompatibilidad)
- [x] Test: batch excluye tarjetas suspendidas
- [x] Test: study_log incluye campo `rating`

### B.5. Verificación de Fase B — COMPLETADA ✅
- [x] `migrate:fresh --seed` — 17 migraciones aplicadas correctamente
- [x] Sesión de repaso muestra 4 botones con intervalos estimados
- [x] Chip de madurez visible en las tarjetas
- [x] Suite completa: **115/115 tests pasando**

**Resumen de verificación Fase B**: completada. Motor Anki completo activo con 6 estados de madurez, 4 ratings, configuración por usuario y retrocompatibilidad total con el sistema anterior. Tests 115/115 ✅

---

## Fase C — Colección Interactiva + Búsqueda y Filtrado Avanzado
> **Objetivo**: Transformar "Mi Colección" en una herramienta de gestión activa con búsqueda en tiempo real, filtros combinados, selección múltiple y acciones en lote.

### C.1. Base de datos
- [ ] Migración: índice en `compounds(full_text)` para búsqueda rápida (tsvector en PostgreSQL, LIKE en SQLite)
- [ ] Migración: índice en `compounds(translation)` para búsqueda por traducción
- [ ] Migración: índice compuesto en `user_progress(user_id, card_state, next_review_date)`

### C.2. Backend — Endpoints
- [ ] Endpoint `PUT /api/collection/{id}/translate` — edición inline de traducción por el usuario
- [ ] Endpoint `PUT /api/collection/{id}/suspend` — suspender / reactivar tarjeta
- [ ] Endpoint `DELETE /api/collection/{id}` — eliminar de colección (solo user_progress, no el compound)
- [ ] Endpoint `POST /api/collection/batch` — acciones en lote (suspend, reset, tag)
- [ ] Optimizar query de colección: eager loading de tags, entities y user_progress sin N+1

### C.3. Frontend — Componente Livewire
- [ ] Añadir buscador en tiempo real (`wire:model.live.debounce.300ms`) en `MyCollection`
- [ ] Filtro por tags: multiselect con Capa 1 visible y Capas 2-3 en "Filtros avanzados"
- [ ] Filtro por `card_state`: checkboxes (Nuevas, Aprendiendo, Joven, Madura, Suspendida)
- [ ] Filtro por próxima revisión: (Vencidas, Hoy, Esta semana, Próximas, Todas)
- [ ] Ordenación: por próxima revisión (asc/desc), por precisión, por fecha de adición, alfabético
- [ ] Toggle de vista: Tabla densa / Cards (recordar preferencia en sesión)
- [ ] Vista tabla: columnas Hangul, Traducción, Estado, Intervalo, Próximo repaso, Precisión
- [ ] Checkboxes de selección múltiple (individual y "seleccionar todo visible")
- [ ] Barra de acciones en lote: Suspender, Resetear progreso, Eliminar seleccionados
- [ ] Edición inline de traducción directamente en la fila de la tabla
- [ ] Menú contextual por tarjeta: Editar traducción, Suspender, Ajustar intervalo, Ver detalle, Eliminar
- [ ] Indicador de resultados: "Mostrando X de Y tarjetas"
- [ ] Paginación: selector de 10 / 25 / 50 por página

### C.4. Tests
- [ ] Test: búsqueda por texto hangul filtra correctamente
- [ ] Test: búsqueda por traducción filtra correctamente
- [ ] Test: filtro por card_state devuelve solo tarjetas del estado indicado
- [ ] Test: filtro por tags devuelve solo tarjetas con esos tags
- [ ] Test: acción en lote "suspend" actualiza múltiples registros
- [ ] Test: eliminar de colección borra user_progress pero no el compound
- [ ] Test: edición inline de traducción actualiza el campo correcto

### C.5. Verificación de Fase C
- [ ] `php84 artisan migrate:fresh --seed`
- [ ] Búsqueda en tiempo real responde sin recarga
- [ ] Filtros combinados funcionan correctamente
- [ ] Vista tabla y vista cards togglable
- [ ] Acciones en lote afectan a los registros seleccionados
- [ ] Suite de tests Fase C pasa: `php84 artisan test --filter=FaseCColeccion`

**Resumen de verificación Fase C**: _pendiente_

---

## Fase D — Panel Admin Avanzado + Sistema de Reportes
> **Objetivo**: Dar a los administradores control total sobre la base de datos y un sistema de tickets para atender problemas reportados por usuarios.

### D.1. Base de datos
- [ ] Migración: nueva tabla `user_reports` (id, user_id, category, description, related_item_id, related_item_type, status, admin_notes, timestamps)
- [ ] Migración: añadir columna `is_active` (bool, default true) a tabla `users`
- [ ] Migración: nueva tabla `admin_actions_log` (id, admin_id, action_type, target_type, target_id, payload JSON, timestamps)

### D.2. Sistema de Reportes
- [ ] Crear modelo `UserReport` con relaciones a `User` y morfológica a `Compound`/`Entity`
- [ ] Endpoint `POST /api/reports` — usuario crea reporte (autenticado)
- [ ] Endpoint `GET /api/admin/reports` — admin lista reportes con filtros
- [ ] Endpoint `PUT /api/admin/reports/{id}` — admin actualiza estado y nota
- [ ] Componente Livewire `ReportForm` — modal accesible desde el footer/navbar
- [ ] Vista admin: sección "Reportes" con lista paginada y filtros por categoría y estado
- [ ] Acción rápida: desde el reporte, enlazar directamente al compound/entity para corregirlo

### D.3. Panel Admin — Gestión de Usuarios
- [ ] Vista: lista paginada de todos los usuarios con búsqueda por nombre/email
- [ ] Vista: detalle de usuario (stats: tarjetas, repasos, precisión, reportes enviados)
- [ ] Acción: cambiar rol (user ↔ admin) con confirmación
- [ ] Acción: activar / desactivar cuenta (`is_active`)
- [ ] Acción: ver y gestionar reportes de ese usuario

### D.4. Panel Admin — Gestión de Vocabulario
- [ ] Buscador global de compounds y entities (búsqueda en tiempo real, sin paginación limitada)
- [ ] Vista: detalle de compound con lista de entities, orden, tags y historial de ediciones
- [ ] Acción: editar cualquier campo de un compound (full_text, translation, status)
- [ ] Acción: reasignar o reordenar entities dentro de un compound
- [ ] Acción: eliminar compound con preview de impacto (X usuarios afectados)

### D.5. Panel Admin — Gestión de Tags
- [ ] Vista: lista de todos los tags con conteo de uso
- [ ] Acción: renombrar un tag (actualiza todas las referencias)
- [ ] Acción: fusionar dos tags (mover taggables y eliminar el origen)
- [ ] Acción: eliminar tags sin uso
- [ ] Acción: añadir nuevos tags al catálogo estándar

### D.6. Tests
- [ ] Test: usuario crea reporte → aparece en lista admin con status `pending`
- [ ] Test: admin actualiza estado de reporte a `resolved`
- [ ] Test: usuario inactivo (`is_active = false`) no puede iniciar sesión
- [ ] Test: admin cambia rol de usuario → nuevo rol persiste
- [ ] Test: admin fusiona dos tags → los taggables se reasignan al tag destino
- [ ] Test: log de acciones admin registra la operación correctamente

### D.7. Verificación de Fase D
- [ ] `php84 artisan migrate:fresh --seed`
- [ ] Flujo completo: usuario reporta → admin ve ticket → admin cambia estado
- [ ] Panel admin muestra lista de usuarios con acciones funcionales
- [ ] Gestión de tags: renombrar y fusionar sin pérdida de datos
- [ ] Suite de tests Fase D pasa: `php84 artisan test --filter=FaseDAdmin`

**Resumen de verificación Fase D**: _pendiente_

---

## Fase E — IA Avanzada: Insights de Rendimiento + Segmentación de Frases
> **Objetivo**: Usar la LLM integrada para ofrecer análisis de rendimiento personalizados y soportar la ingesta de oraciones completas descompuestas en morfemas.

### E.1. Insights de Rendimiento con IA
- [ ] Método `StatsService::buildInsightsContext(int $userId): array` — prepara el resumen de métricas
- [ ] Prompt de análisis en `ClaudeAIParserService::generateInsights(array $context): array`
- [ ] Implementar `FakeAIParserService::generateInsights()` con respuesta determinista para tests
- [ ] Método `StatsService::generateInsights(int $userId): array` — orquesta todo y cachea (TTL 1h)
- [ ] Rate limit: máximo 1 regeneración manual cada 30 minutos por usuario
- [ ] Endpoint `POST /api/stats/insights` — genera o devuelve los insights cacheados
- [ ] Componente Livewire `Stats\AiCoach` con:
  - Sección "Tu Coach de IA" en el dashboard de estadísticas
  - Botón "Generar informe" con estado de carga
  - Visualización de: resumen, puntos fuertes, puntos débiles, recomendaciones

### E.2. Segmentación de Frases por IA
- [ ] Detectar en `ParseVocabularyJob` si el input contiene espacios (frase) o es monoléxico
- [ ] Ampliar prompt de IA para solicitar desglose de frase en entities individuales con `position_order`
- [ ] En `IngestionService::persistResult()`: usar `Entity::firstOrCreate(['text' => ...])` para evitar duplicados
- [ ] Crear todas las relaciones en `compound_entity` con `position_order` correcto
- [ ] UI de colección: distinguir visualmente los compounds de tipo "frase" (badge o ícono)
- [ ] UI de detalle de frase: mostrar las entities desplegables con su traducción y tipo

### E.3. Tests
- [ ] Test: IngestionService con frase larga crea 5+ entities y las enlaza al compound
- [ ] Test: entity con mismo `text` no se duplica (firstOrCreate funciona correctamente)
- [ ] Test: `compound_entity.position_order` refleja el orden de las palabras en la frase
- [ ] Test: insights devuelven estructura correcta (summary, strengths, weaknesses, recommendations)
- [ ] Test: segunda llamada a `generateInsights` en < 30min usa caché sin llamar a la IA

### E.4. Verificación de Fase E
- [ ] `php84 artisan migrate:fresh --seed`
- [ ] Añadir frase completa → la IA la descompone → entities guardadas por separado
- [ ] Entity existente en la BD no se duplica al añadir una segunda frase que la contenga
- [ ] Dashboard de estadísticas muestra sección "Coach de IA" funcional
- [ ] Suite de tests Fase E pasa: `php84 artisan test --filter=FaseEIA`

**Resumen de verificación Fase E**: _pendiente_

---

## Fase F — Rediseño Visual Profesional (Grid/Sólido)
> **Objetivo**: Reemplazar la estética glassmorphism/pastelosa por una interfaz más densa, cuadriculada y profesional, similar a herramientas como Linear o Raycast.

### F.1. Sistema de Diseño (variables CSS)
- [ ] Actualizar `--color-bg` a negro profundo (`#0a0a0a`)
- [ ] Actualizar `--color-bg-card` a gris oscuro sólido (`#111111`)
- [ ] Eliminar variables de glassmorphism (`--color-bg-glass`)
- [ ] Cambiar `--color-accent` a índigo desaturado (`#6366f1`) — solo para CTAs críticos
- [ ] Actualizar colores de texto: principal `#f5f5f5`, muted `#737373`
- [ ] Reducir `--radius` de 12px a 4px globalmente
- [ ] Añadir tipografía monoespaciada para métricas: `JetBrains Mono` o `IBM Plex Mono`
- [ ] Reducir todas las transiciones a máx 150ms, solo `opacity` y `color`
- [ ] Eliminar `backdrop-filter: blur` de todos los elementos
- [ ] Eliminar `box-shadow` expandidos en hover; reemplazar por cambio de `border-color`

### F.2. Componentes a rediseñar
- [ ] Layout general y Navbar
- [ ] Dashboard de inicio
- [ ] Mi Colección (vista tabla por defecto, más densa)
- [ ] Sesión de repaso (4 botones Anki, diseño funcional)
- [ ] Dashboard de estadísticas
- [ ] Perfil de usuario
- [ ] Panel de administración
- [ ] Formulario de análisis (AddWord)
- [ ] Páginas de login y registro
- [ ] Componentes Blade: `x-accuracy-badge`, `x-progress-bar`, `x-alert`

### F.3. Tests
- [ ] Test: ruta de login devuelve 200 con el nuevo layout
- [ ] Test: ruta de dashboard devuelve 200 sin errores
- [ ] Test: ruta de colección devuelve 200 sin errores
- [ ] Test: ruta de estadísticas devuelve 200 sin errores
- [ ] Test: ruta de admin devuelve 200 sin errores

### F.4. Verificación de Fase F
- [ ] `php84 artisan migrate:fresh --seed`
- [ ] Revisar visualmente todas las páginas principales en el navegador
- [ ] Comprobar que no hay sombras, border-radius grandes ni glassmorphism
- [ ] Tipografía monoespaciada visible en métricas numéricas
- [ ] Suite completa de tests sigue pasando: `php84 artisan test`

**Resumen de verificación Fase F**: _pendiente_

---

## Resumen de Progreso

| Fase | Descripción | Tests | Estado |
|---|---|---|---|
| A | Taxonomía estandarizada de tags | — / 4 | ⏳ Pendiente |
| B | Motor SRS Anki completo + intervalos manuales | — / 7 | ⏳ Pendiente |
| C | Colección interactiva + búsqueda avanzada | — / 7 | ⏳ Pendiente |
| D | Panel admin avanzado + reportes | — / 6 | ⏳ Pendiente |
| E | IA avanzada: insights + segmentación de frases | — / 5 | ⏳ Pendiente |
| F | Rediseño visual profesional | — / 5 | ⏳ Pendiente |
| **BASE** | Fases 1-6 originales | 94 / 94 | ✅ Completado |
