<?php

namespace App\Services\AI;

use App\Contracts\AIParserServiceInterface;

/**
 * Implementación FAKE del analizador morfológico IA.
 *
 * Devuelve respuestas predefinidas sin llamar a ninguna API externa.
 * Útil para:
 *   - Desarrollo y pruebas sin coste de API
 *   - Tests automatizados con resultados deterministas
 *   - Demostrar el flujo completo del pipeline
 *
 * Configuración: AI_PROVIDER=fake en .env
 */
class FakeAIParserService implements AIParserServiceInterface
{
    /**
     * Respuestas predefinidas por texto de entrada.
     * Cubre los casos de uso más comunes para demo y tests.
     */
    private const RESPONSES = [
        '학교에서' => [
            'full_compound' => [
                'text'        => '학교에서',
                'translation' => 'en la escuela',
                'tags'        => ['sustantivo', 'estudios', 'lugares'],
            ],
            'components' => [
                ['text' => '학교', 'type' => 'root',     'meaning' => 'escuela',              'position_order' => 0],
                ['text' => '에서', 'type' => 'particle', 'meaning' => 'en (lugar de acción)', 'position_order' => 1],
            ],
        ],
        '안녕하세요' => [
            'full_compound' => [
                'text'        => '안녕하세요',
                'translation' => 'hola (formal)',
                'tags'        => ['expresión', 'formal'],
            ],
            'components' => [
                ['text' => '안녕', 'type' => 'root',     'meaning' => 'paz, bienestar',    'position_order' => 0],
                ['text' => '하다', 'type' => 'root',     'meaning' => 'hacer, ser',        'position_order' => 1],
                ['text' => '세요', 'type' => 'particle', 'meaning' => 'forma honorífica',  'position_order' => 2],
            ],
        ],
        '감사합니다' => [
            'full_compound' => [
                'text'        => '감사합니다',
                'translation' => 'gracias (formal)',
                'tags'        => ['expresión', 'formal'],
            ],
            'components' => [
                ['text' => '감사',  'type' => 'root',     'meaning' => 'gratitud',              'position_order' => 0],
                ['text' => '하다',  'type' => 'root',     'meaning' => 'hacer',                 'position_order' => 1],
                ['text' => '습니다', 'type' => 'particle', 'meaning' => 'terminación formal',   'position_order' => 2],
            ],
        ],
        '한국어' => [
            'full_compound' => [
                'text'        => '한국어',
                'translation' => 'idioma coreano',
                'tags'        => ['sustantivo'],
            ],
            'components' => [
                ['text' => '한국', 'type' => 'root',     'meaning' => 'Corea',   'position_order' => 0],
                ['text' => '어',   'type' => 'particle', 'meaning' => 'idioma',  'position_order' => 1],
            ],
        ],
        '사랑해요' => [
            'full_compound' => [
                'text'        => '사랑해요',
                'translation' => 'te quiero (semi-formal)',
                'tags'        => ['verbo', 'emociones', 'informal'],
            ],
            'components' => [
                ['text' => '사랑', 'type' => 'root',     'meaning' => 'amor',              'position_order' => 0],
                ['text' => '하다', 'type' => 'root',     'meaning' => 'hacer, sentir',     'position_order' => 1],
                ['text' => '아요', 'type' => 'particle', 'meaning' => 'terminación cortés', 'position_order' => 2],
            ],
        ],
        '먹어요' => [
            'full_compound' => [
                'text'        => '먹어요',
                'translation' => 'como / comer (presente cortés)',
                'tags'        => ['verbo', 'comida'],
            ],
            'components' => [
                ['text' => '먹다', 'type' => 'root',     'meaning' => 'comer',                          'position_order' => 0],
                ['text' => '어요', 'type' => 'particle', 'meaning' => 'terminación cortés presente',    'position_order' => 1],
            ],
        ],
    ];

    /**
     * Devuelve la estructura morfológica del texto.
     * Si el texto no está en el diccionario predefinido, genera una respuesta genérica.
     */
    public function parse(string $text): array
    {
        $text = trim($text);

        if (empty($text)) {
            throw new \InvalidArgumentException('El texto a analizar no puede estar vacío.');
        }

        // Respuesta predefinida exacta
        if (isset(self::RESPONSES[$text])) {
            return self::RESPONSES[$text];
        }

        // Respuesta genérica para cualquier otro texto coreano
        return $this->buildGenericResponse($text);
    }

    /**
     * Genera una respuesta genérica cuando el texto no está en el diccionario.
     * Trata el texto completo como una única raíz (word).
     */
    private function buildGenericResponse(string $text): array
    {
        return [
            'full_compound' => [
                'text'        => $text,
                'translation' => "[traducción pendiente de: {$text}]",
                'tags'        => ['sustantivo'],   // tag genérico del catálogo estándar
            ],
            'components' => [
                [
                    'text'           => $text,
                    'type'           => 'word',
                    'meaning'        => "[significado pendiente de: {$text}]",
                    'position_order' => 0,
                ],
            ],
        ];
    }
}
