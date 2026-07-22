<?php

namespace App\Services\AI;

use App\Contracts\AIParserServiceInterface;
use App\Support\TagCatalog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Implementación REAL del analizador morfológico usando la API de Anthropic Claude.
 *
 * Configuración requerida en .env:
 *   AI_PROVIDER=claude
 *   AI_API_KEY=sk-ant-api03-...
 *   AI_MODEL=claude-3-5-sonnet-20241022
 *
 * NOTA: Esta implementación NO está activa en desarrollo. Solo se activa
 * cuando AI_PROVIDER=claude en el .env. Se puede alternar sin cambiar código.
 */
class ClaudeAIParserService implements AIParserServiceInterface
{
    private const API_URL    = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION = '2023-06-01';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
    ) {}

    /**
     * Construye el prompt del sistema inyectando dinámicamente la lista de tags autorizados.
     * El catálogo se cachea 24h en TagCatalog, así que no hay overhead significativo.
     */
    private function buildSystemPrompt(): string
    {
        $tagList = TagCatalog::promptList();

        return <<<PROMPT
Eres un experto en lingüística coreana. Analiza morfológicamente el texto coreano dado.

Responde ÚNICAMENTE con un JSON válido con la siguiente estructura exacta (sin texto adicional, sin markdown, sin explicaciones):

{
  "full_compound": {
    "text": "texto_completo",
    "translation": "traducción al español",
    "tags": ["etiqueta1", "etiqueta2"]
  },
  "components": [
    {
      "text": "morfema",
      "type": "root|particle|word",
      "meaning": "significado en español",
      "position_order": 0
    }
  ]
}

Reglas OBLIGATORIAS:
- type solo puede ser: "root", "particle" o "word"
- position_order es 0-indexed según el orden de aparición en el texto original
- Separa todos los morfemas reconocibles, incluso los muy cortos
- IMPORTANTE: el campo "tags" SOLO puede contener etiquetas de la siguiente lista autorizada:
  [{$tagList}]
- No inventes etiquetas nuevas. Si no encaja ninguna, usa la más genérica disponible.
- Puedes asignar entre 1 y 3 etiquetas por término.
PROMPT;
    }

    public function parse(string $text): array
    {
        $text = trim($text);

        if (empty($text)) {
            throw new \InvalidArgumentException('El texto a analizar no puede estar vacío.');
        }

        Log::info('[ClaudeAI] Analizando texto', ['text' => $text, 'model' => $this->model]);

        $response = Http::withHeaders([
            'x-api-key'         => $this->apiKey,
            'anthropic-version' => self::API_VERSION,
            'Content-Type'      => 'application/json',
        ])->timeout(30)->post(self::API_URL, [
            'model'      => $this->model,
            'max_tokens' => 1024,
            'system'     => $this->buildSystemPrompt(),
            'messages'   => [
                ['role' => 'user', 'content' => "Analiza morfológicamente: {$text}"],
            ],
        ]);

        if (! $response->successful()) {
            Log::error('[ClaudeAI] Error de API', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException("Error en la API de Claude: HTTP {$response->status()}");
        }

        $rawContent = $response->json('content.0.text', '');
        Log::debug('[ClaudeAI] Respuesta cruda', ['raw' => $rawContent]);

        return $this->parseAndValidate($rawContent);
    }

    /**
     * Parsea el JSON de la respuesta y valida su estructura.
     *
     * @throws \RuntimeException Si el JSON es inválido o no respeta el esquema
     */
    private function parseAndValidate(string $raw): array
    {
        // Extraer JSON si viene envuelto en markdown
        $cleaned = preg_replace('/^```(?:json)?\s*/m', '', $raw);
        $cleaned = preg_replace('/\s*```$/m', '', $cleaned);

        $data = json_decode(trim($cleaned), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('La respuesta de IA no es JSON válido: ' . json_last_error_msg());
        }

        $this->validateSchema($data);

        return $data;
    }

    /**
     * Valida que el array tiene el esquema esperado.
     *
     * @throws \RuntimeException Si el esquema no es válido
     */
    private function validateSchema(array $data): void
    {
        // Claves raíz requeridas
        if (! isset($data['full_compound'], $data['components'])) {
            throw new \RuntimeException('Esquema IA inválido: faltan claves full_compound o components.');
        }

        // full_compound
        $fc = $data['full_compound'];
        if (! isset($fc['text'], $fc['translation'], $fc['tags']) || ! is_array($fc['tags'])) {
            throw new \RuntimeException('Esquema IA inválido: full_compound incompleto.');
        }

        // components
        if (! is_array($data['components']) || empty($data['components'])) {
            throw new \RuntimeException('Esquema IA inválido: components debe ser un array no vacío.');
        }

        $validTypes = ['root', 'particle', 'word'];

        foreach ($data['components'] as $i => $component) {
            if (! isset($component['text'], $component['type'], $component['meaning'], $component['position_order'])) {
                throw new \RuntimeException("Esquema IA inválido: componente #{$i} incompleto.");
            }
            if (! in_array($component['type'], $validTypes, true)) {
                throw new \RuntimeException("Esquema IA inválido: tipo '{$component['type']}' no permitido en componente #{$i}.");
            }
        }
    }
}
