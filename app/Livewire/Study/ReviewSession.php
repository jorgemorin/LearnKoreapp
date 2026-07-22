<?php

namespace App\Livewire\Study;

use App\Models\UserProgress;
use App\Models\UserSrsSettings;
use App\Services\SrsService;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Componente Livewire para la sesión de repaso SRS (Anki completo).
 *
 * Flujo UX:
 *   1. Carga el lote de tarjetas vencidas para el usuario (máx 20)
 *   2. Muestra una tarjeta a la vez (hangul visible, traducción oculta)
 *   3. Usuario pulsa "Ver respuesta" → se revela la traducción + morfemas
 *   4. Usuario elige: Otra vez | Difícil | Bien | Fácil
 *      → Se muestran los intervalos estimados sobre cada botón
 *   5. Al registrar la respuesta → siguiente tarjeta
 *   6. Al terminar el lote → pantalla de resumen
 */
class ReviewSession extends Component
{
    /** Lote de tarjetas a repasar. */
    public array $cards = [];

    /** Índice de la tarjeta actual. */
    public int $currentIndex = 0;

    /** Si la respuesta (traducción) está visible. */
    public bool $showAnswer = false;

    /** Intervalos estimados para los 4 botones. */
    public array $estimatedIntervals = ['again' => '…', 'hard' => '…', 'good' => '…', 'easy' => '…'];

    /** Contadores de esta sesión. */
    public int $againCount   = 0;
    public int $hardCount    = 0;
    public int $goodCount    = 0;
    public int $easyCount    = 0;

    /** Alias de compatibilidad para el template anterior. */
    public int $correctCount   = 0;
    public int $incorrectCount = 0;

    /** Si la sesión ha terminado. */
    public bool $sessionComplete = false;

    /** Timestamp de inicio de la tarjeta (para time_taken_ms). */
    public ?int $cardStartedAt = null;

    public function mount(SrsService $srsService): void
    {
        $this->loadCards($srsService);
    }

    // =========================================================================
    // Acciones del usuario
    // =========================================================================

    /**
     * Revela la respuesta y calcula los intervalos estimados para los 4 botones.
     */
    public function reveal(SrsService $srsService): void
    {
        $this->showAnswer    = true;
        $this->cardStartedAt = (int) (microtime(true) * 1000);

        // Calcular intervalos estimados para mostrar sobre los botones
        if (isset($this->cards[$this->currentIndex])) {
            $progressId = $this->cards[$this->currentIndex]['progress_id'];
            $progress   = UserProgress::find($progressId);
            $settings   = UserSrsSettings::firstOrCreate(
                ['user_id' => auth()->id()],
                []
            );

            if ($progress) {
                $this->estimatedIntervals = $srsService->getEstimatedIntervals($progress, $settings);
            }
        }
    }

    /** Usuario pulsa "Otra vez". */
    public function rateAgain(SrsService $srsService): void
    {
        $this->processRating($srsService, 'again');
        $this->againCount++;
        $this->incorrectCount++;
    }

    /** Usuario pulsa "Difícil". */
    public function rateHard(SrsService $srsService): void
    {
        $this->processRating($srsService, 'hard');
        $this->hardCount++;
        $this->correctCount++;
    }

    /** Usuario pulsa "Bien". */
    public function rateGood(SrsService $srsService): void
    {
        $this->processRating($srsService, 'good');
        $this->goodCount++;
        $this->correctCount++;
    }

    /** Usuario pulsa "Fácil". */
    public function rateEasy(SrsService $srsService): void
    {
        $this->processRating($srsService, 'easy');
        $this->easyCount++;
        $this->correctCount++;
    }

    // Retrocompatibilidad con el template anterior
    public function markCorrect(SrsService $srsService): void  { $this->rateGood($srsService); }
    public function markIncorrect(SrsService $srsService): void { $this->rateAgain($srsService); }

    /** Reinicia la sesión recargando el lote. */
    public function restart(SrsService $srsService): void
    {
        $this->currentIndex       = 0;
        $this->againCount         = 0;
        $this->hardCount          = 0;
        $this->goodCount          = 0;
        $this->easyCount          = 0;
        $this->correctCount       = 0;
        $this->incorrectCount     = 0;
        $this->sessionComplete    = false;
        $this->showAnswer         = false;
        $this->cardStartedAt      = null;
        $this->estimatedIntervals = ['again' => '…', 'hard' => '…', 'good' => '…', 'easy' => '…'];

        $this->loadCards($srsService);
    }

    // =========================================================================
    // Helpers internos
    // =========================================================================

    private function processRating(SrsService $srsService, string $rating): void
    {
        if (! isset($this->cards[$this->currentIndex])) {
            return;
        }

        $timeTakenMs = $this->cardStartedAt
            ? max(0, (int)(microtime(true) * 1000) - $this->cardStartedAt)
            : 1000;

        $progressId = $this->cards[$this->currentIndex]['progress_id'];

        $srsService->recordAnswer(
            progressId:  $progressId,
            userId:      auth()->id(),
            rating:      $rating,
            timeTakenMs: $timeTakenMs,
        );

        $this->advance();
    }

    private function advance(): void
    {
        $this->showAnswer         = false;
        $this->cardStartedAt      = null;
        $this->estimatedIntervals = ['again' => '…', 'hard' => '…', 'good' => '…', 'easy' => '…'];
        $this->currentIndex++;

        if ($this->currentIndex >= count($this->cards)) {
            $this->sessionComplete = true;
        }
    }

    private function loadCards(SrsService $srsService): void
    {
        $batch = $srsService->getNextBatch(auth()->id(), 20);

        $this->cards = $batch->map(fn ($progress) => [
            'progress_id'    => $progress->id,
            'hangul'         => $progress->item?->full_text ?? $progress->item?->text ?? '—',
            'translation'    => $progress->item?->translation ?? $progress->item?->meaning ?? '—',
            'type'           => $progress->item_type,
            'entities'       => $this->extractEntities($progress),
            'tags'           => $progress->item?->tags?->pluck('name')->toArray() ?? [],
            'interval_days'  => $progress->interval_days,
            'repetitions'    => $progress->repetitions,
            'card_state'     => $progress->card_state,
            'state_label'    => $progress->stateLabel(),
        ])->toArray();

        $this->sessionComplete = empty($this->cards);
    }

    private function extractEntities($progress): array
    {
        if ($progress->item_type !== 'compound' || ! $progress->item) {
            return [];
        }

        return $progress->item->entities?->map(fn ($e) => [
            'text'    => $e->text,
            'type'    => $e->type,
            'meaning' => $e->meaning,
        ])->toArray() ?? [];
    }

    public function render()
    {
        return view('livewire.study.review-session');
    }
}
