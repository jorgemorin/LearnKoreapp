<?php

namespace App\Contracts;

/**
 * Contrato del servicio de análisis morfológico IA.
 *
 * Todas las implementaciones (Fake, Claude, OpenAI, etc.) deben
 * devolver un array con el siguiente esquema validado:
 *
 * {
 *   "full_compound": {
 *     "text":        string,   // Hangul completo: "학교에서"
 *     "translation": string,   // Traducción al español: "en la escuela"
 *     "tags":        string[]  // Etiquetas semánticas: ["Educación", "Lugares"]
 *   },
 *   "components": [
 *     {
 *       "text":           string,  // Morfema: "학교"
 *       "type":           string,  // "root" | "particle" | "word"
 *       "meaning":        string,  // "escuela"
 *       "position_order": int      // 0-indexed, orden dentro del compuesto
 *     }
 *   ]
 * }
 *
 * El método DEBE lanzar \InvalidArgumentException si el texto de entrada
 * está vacío, y \RuntimeException si la respuesta de la IA no se puede
 * parsear o no respeta el esquema.
 */
interface AIParserServiceInterface
{
    /**
     * Analiza morfológicamente un texto coreano y retorna su estructura.
     *
     * @param  string $text  Texto coreano a analizar (ej. "학교에서")
     * @return array         Array con claves 'full_compound' y 'components'
     *
     * @throws \InvalidArgumentException  Si el texto está vacío
     * @throws \RuntimeException          Si la respuesta IA no respeta el esquema
     */
    public function parse(string $text): array;
}
