# LearnKoreapp — TODO.md
## Plan de Implementación por Fases

> **Stack fijo**: Laravel (última versión estable) + Blade + Livewire | PostgreSQL | Redis | Laravel Sanctum | AIParserServiceInterface (mock/fake inicial)

---

## ✅ Fase 1 — Fundamentos — COMPLETADA
> **Objetivo**: Proyecto Laravel inicializado, esquema de BD desplegado, autenticación con roles funcionando.

### 1.1. Inicialización del Proyecto
- [x] Inicializar proyecto Laravel 13.21.1 con `composer create-project`
- [x] Configurar `.gitignore` correctamente
- [x] Crear `.env.example` con todas las variables necesarias
- [x] Configurar `.env` local con PostgreSQL (y file-driver para sesión local sin Redis)
- [x] Verificar que `php84 artisan serve` arranca sin errores

### 1.2. Configuración de Base de Datos (PostgreSQL)
- [x] Configurar conexión PostgreSQL en `.env` (PostgreSQL 18.4, BD: learnkoreapp)
- [x] Crear migración: `users` (role CHECK, email_verified_at, HasApiTokens Sanctum)
- [x] Crear migración: `entities` (text, type CHECK, meaning, status CHECK, unique(text,type), índices)
- [x] Crear migración: `compounds` (full_text UNIQUE, translation, status CHECK, índice status)
- [x] Crear migración: `compound_entity` (pivot con position_order, claves foráneas CASCADE, índice entity_id)
- [x] Crear migración: `tags` (name UNIQUE)
- [x] Crear migración: `taggables` (polimórfica, tag_id, taggable_id, taggable_type CHECK, PK compuesta, índice morph)
- [x] Crear migración: `user_progress` (polimórfica, ease_factor REAL, interval_days, repetitions, UNIQUE, índice crítico)
- [x] Crear migración: `study_logs` (inmutable: UPDATED_AT=null, polimórfica, is_correct, time_taken_ms, índices)
- [x] Crear migración: Vistas SQL analíticas (`v_accuracy_by_entity`, `v_accuracy_by_tag`, `v_accuracy_by_type`)
- [x] Añadir todos los índices críticos de rendimiento

### 1.3. Configuración de Redis / Drivers locales
- [x] Instalar `predis/predis` v3.5 (driver Redis PHP puro)
- [x] Configurar Redis como driver de cache y colas en `.env` (con fallback a `file`/`sync` en dev sin Redis)
- [x] PHP 8.4.23 completo con `pdo_pgsql` instalado en `php84/`

### 1.4. Modelos Eloquent
- [x] Crear modelo `User` (role constantes ROLE_USER/ROLE_ADMIN, helpers isAdmin/isUser, HasApiTokens)
- [x] Crear modelo `Entity` (relaciones compounds pivot + tags polimórfico, scopes: byStatus, byType, verified, pending)
- [x] Crear modelo `Compound` (relaciones entities con position_order, tags polimórfico, scopes)
- [x] Crear modelo `Tag` (relación polimórfica morphedByMany entities y compounds)
- [x] Crear modelo `UserProgress` (SM-2 fields, morphTo item, scope dueToday/forUser)
- [x] Crear modelo `StudyLog` (UPDATED_AT=null, morphTo item, solo insert)

### 1.5. Autenticación con Laravel Sanctum
- [x] Instalar y publicar Laravel Sanctum 4.x
- [x] Configurar Sanctum para sesión web (Blade/Livewire)
- [x] Implementar pantalla de **registro** (Livewire Register con validación en español)
- [x] Implementar pantalla de **login** (Livewire Login con "recuérdame")
- [x] Implementar **logout** funcional (POST /logout con invalidación de sesión)
- [x] Implementar middleware RBAC `CheckRole` (`role:admin`)
- [x] Registrar middleware en `bootstrap/app.php` con alias `role`
- [x] Proteger rutas `/admin/*` con middleware `role:admin`

### 1.6. Layouts y Estructura Blade
- [x] Crear layout principal `layouts/app.blade.php` (dark glassmorphism, navbar RBAC-aware)
- [x] Crear layout auth `layouts/auth.blade.php` (usa `$slot` para Livewire v4)
- [x] Livewire v4.3.3 instalado
- [x] Crear componente Livewire `Auth\Register` + vista con un único root div
- [x] Crear componente Livewire `Auth\Login` + vista con un único root div
- [x] Definir rutas web completas con middleware guest/auth/role
- [x] Definir rutas API con estructura completa para futuras fases

### 1.7. Seeders de Prueba
- [x] Crear `UserSeeder`: admin@learnkoreapp.com y user@learnkoreapp.com (idempotente con firstOrCreate)
- [x] Actualizar `DatabaseSeeder` para invocar todos los seeders
- [x] `php84 artisan db:seed` crea los usuarios correctamente

### 1.8. Tests de Fase 1 — 13/13 ✅
- [x] Test: registro de usuario nuevo con datos válidos
- [x] Test: registro falla con email duplicado
- [x] Test: registro falla con passwords no coincidentes
- [x] Test: registro falla sin nombre
- [x] Test: login correcto redirige al dashboard
- [x] Test: login falla con credenciales incorrectas
- [x] Test: usuario no autenticado → redirige a /login
- [x] Test: acceso web `/admin` con usuario normal → 403
- [x] Test: acceso web `/admin` con admin → 200
- [x] Test: acceso web `/admin` sin autenticar → redirect login
- [x] Test: API `/api/admin/queue` con usuario normal → 403
- [x] Test: API `/api/admin/queue` con admin → 200
- [x] Test: API `/api/admin/queue` sin autenticar → 401

### 1.9. Verificación de Fase 1 — COMPLETADA
- [x] `php84 artisan migrate:fresh --seed` sin errores (11 migraciones + 2 usuarios)
- [x] `php84 artisan serve --port=8000` arranca sin errores
- [x] Flujo login/registro/logout funcional en navegador (dark UI verificada)
- [x] Ruta protegida por `role:admin` devuelve 403 a usuario normal
- [x] Suite de tests Fase 1: **13/13 pasando** en <2s

**Resumen de verificación Fase 1**: completada con éxito.

---

## ✅ Fase 2 — Núcleo de Vocabulario — COMPLETADA
> **Objetivo**: Pipeline completo de ingesta de vocabulario con caché Hit/Miss y llamada asíncrona a IA.

### 2.1. Interfaz AIParserService
- [x] Crear interfaz `App\Contracts\AIParserServiceInterface` con método `parse(string $text): array`
- [x] Crear implementación mock `App\Services\AI\FakeAIParserService` (6 palabras predefinidas + respuesta genérica)
- [x] Crear implementación real `App\Services\AI\ClaudeAIParserService` (preparado para Claude; activa con AI_PROVIDER=claude)
- [x] Definir esquema JSON de respuesta: `{full_compound: {text, translation, tags[]}, components: [{text, type, meaning, position_order}]}`
- [x] Implementar validación del esquema JSON en ClaudeAIParserService (rechaza si formato inválido)
- [x] Añadir variables al `.env.example`: `AI_PROVIDER=fake`, `AI_API_KEY=`, `AI_MODEL=`
- [x] Vincular `AIParserServiceInterface` en `AppServiceProvider` según `AI_PROVIDER` (match expression)
- [x] Crear `config/ai.php` con las variables de configuración

### 2.2. VocabularyIngestService + ParseVocabularyJob
- [x] Crear `App\Services\VocabularyIngestService` inyectable
- [x] Implementar lógica **Hit**: buscar `Compound` por `full_text`; si existe → reutilizar + crear UserProgress
- [x] Implementar lógica **Miss**: si no existe → encolar `ParseVocabularyJob` → retorna `pending`
- [x] Implementar método `ingest(string $text, int $userId): array` retorna `{status: hit|pending}`
- [x] Crear `App\Jobs\ParseVocabularyJob` con transacción atómica
  - [x] Crear/reutilizar `Entity` records via `firstOrCreate(text, type)`
  - [x] Crear `Compound` con status `pending_review` via `firstOrCreate(full_text)`
  - [x] Sincronizar `compound_entity` con `position_order` correcto
  - [x] Crear/reutilizar `Tag` y asociar via `taggables` con `syncWithoutDetaching`
  - [x] Crear `UserProgress` inicial para el usuario
- [x] Configurar reintentos del Job: `tries = 3`, `backoff = [10, 30, 60]`
- [x] Implementar `failed()` handler con Log::error para diagnóstico
- [x] Registrar `Relation::morphMap(['compound', 'entity'])` en `AppServiceProvider::boot()`

### 2.3. Controladores y Rutas de Vocabulario
- [x] Crear `Api\VocabularyController`: `store` (POST), `show` (GET /{id}), `collection` (GET /me/collection)
- [x] Crear `StoreVocabularyRequest` (validación: text requerido, string, max:255, con mensajes en español)
- [x] Crear `CollectionController` para la vista web `/collection`
- [x] Registrar rutas en `routes/api.php` protegidas con `auth:sanctum`
- [x] Registrar ruta web `/collection` en `routes/web.php`

### 2.4. Interfaz Livewire de Ingesta
- [x] Crear componente Livewire `Vocabulary\AddWord` con estados hit/pending/error
- [x] Mostrar spinner "Analizando..." mientras el Job está en cola (wire:loading)
- [x] Mostrar resultado completo (morfemas + tags) en hit con diseño glassmorphism
- [x] Crear vista Blade `/collection` con lista de palabras + indicadores SRS (vencida/hoy/futura)
- [x] Actualizar navbar con enlace funcional a "Mi Colección"

### 2.5. Tests de Fase 2 — 16/16 ✅
- [x] Test: FakeAIParserService devuelve estructura correcta para texto predefinido
- [x] Test: FakeAIParserService lanza excepción con texto vacío
- [x] Test: FakeAIParserService genera respuesta genérica para texto desconocido
- [x] Test: cache Hit no despacha Job si compound existe
- [x] Test: cache Hit crea UserProgress si no existe
- [x] Test: cache Hit no duplica UserProgress en segunda llamada
- [x] Test: cache Miss despacha ParseVocabularyJob con texto y userId correctos
- [x] Test: Job crea compound + entities + tags + user_progress
- [x] Test: Job es idempotente (segunda ejecución no duplica)
- [x] Test: Job reutiliza entity existente con mismo text+type
- [x] Test: API POST /vocabulary miss → 202
- [x] Test: API POST /vocabulary hit → 200
- [x] Test: API POST /vocabulary sin autenticar → 401
- [x] Test: API POST /vocabulary texto vacío → 422
- [x] Test: API GET /me/collection → 200 con estructura correcta
- [x] Test: API GET /me/collection sin autenticar → 401

### 2.6. Verificación de Fase 2 — COMPLETADA
- [x] `php84 artisan migrate:fresh --seed` sin errores
- [x] Flujo Hit verificado en navegador: 학교에서 → resultado inmediato con morfemas y tags
- [x] Flujo Miss verificado en navegador: 감사합니다 → "Análisis en progreso" → aparece en colección
- [x] Suite de tests Fase 2: **16/16 pasando** en <1.5s
- [x] Suite completa (Fase 1 + Fase 2): **31/31 pasando**

**Resumen de verificación Fase 2**: completada con éxito. Pipeline Hit/Miss funcional end-to-end.

---

## ✅ Fase 3 — Motor SRS — COMPLETADA
> **Objetivo**: Sesión de repaso end-to-end con algoritmo SM-2, registro en study_logs.

### 3.1. SrsService
- [x] Crear `App\Services\SrsService` inyectable
- [x] Implementar algoritmo **SM-2**: `calculate(UserProgress $progress, bool $isCorrect): array`
  - Acierto: interval = 1/6/round(prev*ef), ease_factor sube con fórmula SM-2, repetitions++
  - Fallo: interval = 1, repetitions = 0, ease_factor = max(1.3, ef con quality=1)
  - Retorna `{interval_days, ease_factor, repetitions, next_review_date}`
- [x] Implementar `getNextBatch(int $userId, int $limit = 20): Collection` — usa índice crítico idx_user_progress_due
- [x] Implementar `recordAnswer(int $progressId, int $userId, bool $isCorrect, int $timeTakenMs): UserProgress`

### 3.2. Inicialización de UserProgress
- [x] UserProgress inicial creado en `ParseVocabularyJob` y en cache Hit (Fase 2, ya completado)
  - `next_review_date = today`, `ease_factor = 2.5`, `interval_days = 0`, `repetitions = 0`

### 3.3. Controladores y Rutas de Repaso
- [x] Crear `Api\ReviewController`: `nextBatch` (GET /api/review/next-batch), `answer` (POST /api/review/{progressId}/answer)
- [x] Crear `AnswerReviewRequest` (validación: `is_correct` boolean, `time_taken_ms` integer, max 300.000ms)
- [x] En `answer`: INSERT en `study_logs` (inmutable) + UPDATE `user_progress` via SrsService (transacción atómica)
- [x] Registrar rutas en `routes/api.php` protegidas con `auth:sanctum`

### 3.4. Interfaz Livewire de Repaso
- [x] Crear componente Livewire `Study\ReviewSession` con estado reveal/answer/next
- [x] Tarjeta muestra: hangul en grande, tags semánticos, botón "Ver respuesta"
- [x] Al revelar: traducción + desglose de morfemas + botones ✓/✗ con colores success/danger
- [x] Mide `time_taken_ms` desde que se revela la respuesta hasta que se pulsa el botón
- [x] Al terminar el lote: pantalla de resumen (aciertos/fallos/precisión/barra visual + trofeo)
- [x] Crear vista Blade `study/review.blade.php` y activar ruta `/study`
- [x] Actualizar navbar con enlace funcional a "Repasar"

### 3.5. Tests de Fase 3 — 18/18 ✅
- [x] Test: SM-2 acierto 1ª rep → interval=1, repetitions=1, ef no cambia con quality=4
- [x] Test: SM-2 acierto 2ª rep → interval=6
- [x] Test: SM-2 acierto 3ª rep → interval=round(prev*ef)
- [x] Test: SM-2 fallo → interval=1, repetitions=0, ease_factor baja (>= 1.3)
- [x] Test: ease_factor nunca baja de 1.3
- [x] Test: StudyLog no tiene UPDATED_AT (inmutable)
- [x] Test: recordAnswer inserta study_log
- [x] Test: getNextBatch devuelve solo tarjetas vencidas (<=today)
- [x] Test: getNextBatch incluye tarjetas de hoy
- [x] Test: getNextBatch respeta límite
- [x] Test: getNextBatch no devuelve tarjetas de otro usuario
- [x] Test: recordAnswer actualiza user_progress con nuevos valores SM-2
- [x] Test: recordAnswer lanza ModelNotFoundException si progress no pertenece al usuario
- [x] Test: API GET /api/review/next-batch → 200 con estructura correcta
- [x] Test: API GET /api/review/next-batch sin autenticar → 401
- [x] Test: API POST /api/review/{id}/answer correcto → 200 con nuevos valores SM-2
- [x] Test: API POST /api/review/{id}/answer sin autenticar → 401
- [x] Test: API POST /api/review/{id}/answer sin is_correct → 422

### 3.6. Verificación de Fase 3 — COMPLETADA
- [x] `php84 artisan migrate:fresh --seed` sin errores
- [x] Sesión de repaso end-to-end verificada: 3 tarjetas, todas "Lo sabía" → pantalla resumen 100%
- [x] `next_review_date` actualizado correctamente tras cada respuesta (SM-2)
- [x] Suite de tests Fase 3: **18/18 pasando** en <1.5s
- [x] Suite completa (Fases 1+2+3): **49/49 pasando**

**Resumen de verificación Fase 3**: completada con éxito. Motor SM-2 funcional end-to-end.

---

## ✅ Fase 4 — Backoffice y Control de Calidad — COMPLETADA
> **Objetivo**: Panel de administración para revisar, aprobar, editar y eliminar términos con cascade delete controlado.

### 4.1. AdminCurationService
- [x] Crear `App\Services\AdminCurationService` inyectable
- [x] Implementar `getPendingQueue(int $perPage = 20): LengthAwarePaginator` (status = pending_review, con entities y tags)
- [x] Implementar `approve(string $type, int $id): void` (status → 'verified') con Log::info
- [x] Implementar `update(string $type, int $id, array $data): Model` (translation, tags sync, entity fields)
- [x] Implementar `delete(string $type, int $id): void` — cascade delete **controlado**:
  - [x] Eliminar user_progress de todos los usuarios (con count de registros borrados)
  - [x] Eliminar study_logs asociados
  - [x] Eliminar taggables del término
  - [x] Eliminar compound_entity si aplica
  - [x] Eliminar el compound/entity
  - [x] Log::warning con auditoría (tipo, id, counts eliminados)

### 4.2. Controladores y Rutas de Backoffice
- [x] Crear `Api\Admin\AdminController`: `queue` (GET), `approve` (POST), `update` (PUT), `destroy` (DELETE)
- [x] Crear `UpdateTermRequest` (validación: translation, meaning, type, status, tags[])
- [x] Registrar rutas en `routes/api.php` bajo `/admin` con `auth:sanctum` + `role:admin`

### 4.3. Panel de Administración (Livewire)
- [x] Crear componente Livewire `Admin\PendingQueue`: lista paginada con acciones inline (Aprobar/Eliminar)
- [x] Crear componente Livewire `Admin\ReviewItem`: edición de traducción + tags (comma-separated)
- [x] Mostrar: hangul, disección morfológica IA (solo lectura), traducción editable, tags editables
- [x] Botones: "Guardar y Aprobar" | "Solo guardar" con wire:loading states
- [x] wire:confirm en botones destructivos (Aprobar sin edición, Eliminar)
- [x] Actualizar `admin/dashboard.blade.php` con stats reales (pending/verified/users) + PendingQueue embebido

### 4.4. Tests de Fase 4 — 13/13 ✅
- [x] Test: usuario normal recibe 403 en GET /api/admin/queue
- [x] Test: usuario normal recibe 403 en POST /api/admin/compound/{id}/approve
- [x] Test: usuario normal recibe 403 en DELETE /api/admin/compound/{id}
- [x] Test: sin autenticar recibe 401 en /api/admin/queue
- [x] Test: approve cambia status a 'verified' (service)
- [x] Test: API approve cambia status a 'verified'
- [x] Test: delete elimina compound y su user_progress
- [x] Test: delete limpia user_progress de múltiples usuarios (cascade total)
- [x] Test: API delete elimina compound
- [x] Test: update cambia traducción
- [x] Test: update sincroniza tags (añade nuevos, elimina viejos)
- [x] Test: API update devuelve 200
- [x] Test: API queue devuelve solo pendientes con meta de paginación

### 4.5. Verificación de Fase 4 — COMPLETADA
- [x] `php84 artisan migrate:fresh --seed` sin errores
- [x] Usuario normal no puede acceder a rutas admin (403 verificado en tests)
- [x] Admin: ver queue (3 pendientes) → expandir → editar traducción → "Guardar y Aprobar"
- [x] Post-aprobación: stats actualizan a 2 pendientes / 1 verificado
- [x] Suite de tests Fase 4: **13/13 pasando** en <1.5s
- [x] Suite completa (Fases 1+2+3+4): **62/62 pasando**

**Resumen de verificación Fase 4**: completada con éxito. Panel admin funcional end-to-end.

---

## ✅ Fase 5 — Motor de Analítica — COMPLETADA
> **Objetivo**: Dashboard de rendimiento personal y análisis cruzado estructural × semántico.

### 5.1. StatsService
- [x] Crear `App\Services\StatsService` inyectable
- [x] Implementar `getPersonalStats(int $userId): array`:
  - [x] Tasa de acierto global (SUM CASE, SQLite+PostgreSQL compatible)
  - [x] Tasa de acierto por tag (JOIN taggables+tags → barras de progreso)
  - [x] Tasa de acierto por tipo morfológico (JOIN entities)
  - [x] Total tarjetas estudiadas, total en colección, pendientes hoy
  - [x] Sesiones recientes (últimos 7 días agrupadas por fecha)
- [x] Implementar `getCrossAnalysis(int $userId, ?string $type, ?string $tag): array` — tabla cruzada tipo × tag con filtros opcionales
- [x] Implementar `getGlobalStats(): array` (todos los usuarios: total_users, compounds, pending, global_accuracy, top_tags)
- [x] Caché con `Cache::remember` TTL 5 min para todas las consultas
- [x] `invalidateUserCache(int $userId)` + `invalidateGlobalCache()` estáticos
- [x] Integrar invalidación en `SrsService::recordAnswer()` (se llama tras cada respuesta)

### 5.2. Controladores y Rutas de Estadísticas
- [x] Crear `StatsController`: `personal` (GET /api/stats/personal), `cross` (GET /api/stats/cross?type=&tag=)
- [x] Crear `AdminStatsController`: `globalStats` (GET /api/admin/stats/global)
- [x] Registrar rutas protegidas en `routes/api.php`

### 5.3. Dashboard de Analítica (Livewire)
- [x] Crear componente Livewire `Stats\PersonalDashboard` que carga stats on mount
- [x] 4 KPIs: precisión global (color semáforo), respuestas totales, pendientes hoy, en colección
- [x] Tabla precisión por tag: barras de progreso con colores verde/amarillo/rojo
- [x] Tabla precisión por tipo morfológico: barras de progreso
- [x] Sesiones recientes: cuadrícula de tarjetas por día con fecha y porcentaje
- [x] CTA vacío: botones "Añadir vocabulario" y "Repasar ahora" cuando no hay datos
- [x] Crear vista Blade `stats/personal.blade.php` y activar ruta `/stats`
- [x] Actualizar navbar: enlace funcional "Estadísticas" → `/stats`

### 5.4. Tests de Fase 5 — 14/14 ✅
- [x] Test: getPersonalStats devuelve estructura correcta con 7 claves
- [x] Test: getPersonalStats sin datos → nulls y ceros
- [x] Test: getPersonalStats con 4 study_logs (3 correctos) → 75% accuracy
- [x] Test: total_in_collection cuenta user_progress del usuario
- [x] Test: due_today cuenta solo tarjetas vencidas hoy o antes
- [x] Test: segunda llamada usa caché (datos no se actualizan)
- [x] Test: invalidateUserCache borra la entrada de caché
- [x] Test: API GET /api/stats/personal → 200 con estructura completa
- [x] Test: API GET /api/stats/personal sin autenticar → 401
- [x] Test: API GET /api/stats/cross → 200
- [x] Test: API GET /api/stats/cross sin autenticar → 401
- [x] Test: API GET /api/admin/stats/global → 200 para admin con estructura
- [x] Test: API GET /api/admin/stats/global → 403 para usuario normal
- [x] Test: getGlobalStats cuenta usuarios y compounds correctamente

### 5.5. Verificación de Fase 5 — COMPLETADA
- [x] `php84 artisan migrate:fresh --seed` sin errores
- [x] 4 rutas registradas correctamente (api/stats/personal, api/stats/cross, api/admin/stats/global, stats)
- [x] Suite de tests Fase 5: **14/14 pasando** en <1.5s
- [x] Suite completa (Fases 1+2+3+4+5): **76/76 pasando**

**Resumen de verificación Fase 5**: completada con éxito. Motor de analítica funcional con caché automática.

---

## ✅ Fase 6 — Interfaz Blade/Livewire Completa — COMPLETADA
> **Objetivo**: Aplicación completa y usable de extremo a extremo, con UI/UX pulida.

### 6.1. Diseño y Sistema de Estilos
- [x] Sistema de diseño en Vanilla CSS con variables CSS (dark mode, paleta violeta/esmeralda)
- [x] Tipografía Inter (Google Fonts) con pesos 300-700
- [x] Layout principal responsive con navbar horizontal + hamburger implícito en mobile
- [x] Favicon SVG (字 한 sobre fondo violeta) + Open Graph + theme-color

### 6.2. Vistas de Usuario
- [x] Vista **Home/Dashboard**: KPIs reales (colección, pendientes hoy, precisión, repasos hoy), CTA de repaso cuando hay pendientes, acciones rápidas
- [x] Vista **Mi Colección**: AddWord inline, lista paginada con estado SRS y próxima revisión (Fase 2)
- [x] Vista **Sesión de Repaso**: tarjetas flip, contador, resumen final con trofeo (Fase 3)
- [x] Vista **Estadísticas Personales**: 4 KPIs + barras de precisión por tag y tipo + sesiones recientes (Fase 5)
- [x] Vista **Perfil de Usuario**: editar nombre/email + cambiar contraseña con validación en español

### 6.3. Vistas de Administración
- [x] Vista **Panel Admin**: stats reales (pending/verified/users) + cola de revisión embebida (Fase 4)
- [x] Vista **Cola de Revisión**: lista paginada con Aprobar/Eliminar inline + confirmaciones
- [x] Vista **Editor de Término**: panel expandible con morfemas + edición traducción/tags

### 6.4. Componentes Reutilizables Blade
- [x] `x-accuracy-badge`: badge tasa de acierto con color semáforo (verde/amarillo/rojo), tamaños sm/md/lg
- [x] `x-morpheme-display`: visualización morfológica con color por tipo (root/particle/word) + modo compact
- [x] `x-progress-bar`: barra animada con label/pct, auto-color por valor
- [x] `x-alert`: alertas info/success/danger/warning con dismiss opcional

### 6.5. Mejoras de UX
- [x] Mensajes de validación en español en ProfileController
- [x] wire:loading states en ReviewSession y ReviewItem (Fase 3/4)
- [x] wire:confirm en acciones destructivas del admin
- [x] Avatar de usuario con inicial en navbar + enlace al perfil
- [x] Botón logout mejorado (↩) con hover rojo
- [x] Favicon SVG + meta description dinámica por página + Open Graph

### 6.6. Tests E2E — 18/18 ✅
- [x] Test: dashboard muestra KPIs reales (2 pendientes visibles)
- [x] Test: páginas collection/study/stats accesibles para usuario autenticado
- [x] Test: perfil accesible y muestra nombre del usuario
- [x] Test: perfil actualiza nombre y email correctamente
- [x] Test: perfil rechaza nombre vacío (validación)
- [x] Test: perfil rechaza email duplicado
- [x] Test: cambio de contraseña falla con contraseña actual incorrecta
- [x] Test: usuario normal no accede a rutas admin (403)
- [x] Test: admin ve cola con datos
- [x] Test: admin aprueba compound via API
- [x] Test: admin elimina compound y limpia user_progress
- [x] Test: dashboard redirige a login si no autenticado
- [x] Test: collection redirige a login si no autenticado
- [x] Test: study redirige a login si no autenticado
- [x] Test: stats redirige a login si no autenticado
- [x] Test: profile redirige a login si no autenticado

### 6.7. Verificación Final — COMPLETADA
- [x] `php84 artisan migrate:fresh --seed` sin errores
- [x] Flujo completo usuario: dashboard → colección → repaso → estadísticas → perfil
- [x] Flujo completo admin: login → cola → aprobar/eliminar con confirmación
- [x] Suite completa de tests: **94/94 pasando** en <5s

**Resumen de verificación Fase 6**: completada con éxito. Aplicación completa end-to-end.

---

## Tests Automatizados por Fase (Resumen)

| Fase | Tests críticos |
|------|---------------|
| 1 | Registro, login, 403 en ruta admin con usuario normal |
| 2 | Caché Hit/Miss, persistencia compound+entities, validación esquema IA |
| 3 | SM-2 en acierto y fallo, study_logs inmutable, getNextBatch con índice |
| 4 | Cascade delete, aprobación, edición, autorización admin vs user |
| 5 | StatsService estructura correcta, caché Redis, vistas SQL |
| 6 | Flujo completo usuario, flujo completo admin |

---

## Decisiones de Implementación (no explícitas en el documento)

1. **Sanctum modo sesión web** para Blade/Livewire (no tokens API para el frontend). Los endpoints `/api/*` usan `auth:sanctum` con sesión web.
2. **Algoritmo SM-2 estándar** (el doc menciona SM-2 o FSRS; se implementa SM-2 por ser más documentado y fácil de testear unitariamente).
3. **`FakeAIParserService`** como implementación inicial: devuelve morfología coreana de ejemplo pre-programada sin llamar a ninguna API externa.
4. **`AI_PROVIDER=fake`** en `.env.example` por defecto; el `AppServiceProvider` hace el binding condicional.
5. **Vistas SQL** se crean en una migración dedicada usando `DB::statement()` para mantener coherencia con el sistema de migraciones de Laravel.
6. **`study_logs` inmutable**: garantizado a nivel de servicio (no se expone endpoint de actualización ni borrado de logs).
