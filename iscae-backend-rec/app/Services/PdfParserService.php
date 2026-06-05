<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class PdfParserService
{
    private Parser $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }

    /**
     * Extrait le texte brut d'un PDF stocké sur l'un des disques Laravel.
     *
     * @param  string  $storagePath  Chemin relatif tel que stocké en base (ex: reclamations/attachments/file.pdf)
     * @param  string  $disk         Disque Storage Laravel ('public' ou 'private')
     * @return array{success: bool, text: string, error: string|null, pages: int}
     */
    public function extractText(string $storagePath, string $disk = 'public'): array
    {
        try {
            // Résoudre le chemin absolu sur le disque
            $absolutePath = Storage::disk($disk)->path($storagePath);

            if (! Storage::disk($disk)->exists($storagePath)) {
                // Essayer l'autre disque si introuvable
                $otherDisk = $disk === 'public' ? 'private' : 'public';
                if (Storage::disk($otherDisk)->exists($storagePath)) {
                    $absolutePath = Storage::disk($otherDisk)->path($storagePath);
                } else {
                    return $this->errorResult("Fichier PDF introuvable : {$storagePath}");
                }
            }

            $pdf = $this->parser->parseFile($absolutePath);
            $pages = $pdf->getPages();
            $pageCount = count($pages);

            if ($pageCount === 0) {
                return $this->errorResult('Le PDF ne contient aucune page lisible.');
            }

            // Extraire le texte page par page
            $rawText = '';
            foreach ($pages as $page) {
                $rawText .= $page->getText() . "\n\n";
            }

            $cleanText = $this->cleanText($rawText);

            if (empty(trim($cleanText))) {
                return $this->errorResult('Aucun texte extrait — le PDF est peut-être scanné (image uniquement).');
            }

            return [
                'success' => true,
                'text'    => $cleanText,
                'error'   => null,
                'pages'   => $pageCount,
            ];

        } catch (\Exception $e) {
            Log::warning('PdfParserService: échec extraction', [
                'path'  => $storagePath,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResult('Erreur lors de la lecture du PDF : ' . $e->getMessage());
        }
    }

    /**
     * Tente d'extraire une valeur numérique ressemblant à une note (0-20) depuis le texte.
     * Retourne la première note trouvée ou null.
     */
    public function extractNoteFromText(string $text): ?float
    {
        // Patterns : "note : 12.5", "12/20", "Note finale : 14", etc.
        $patterns = [
            '/note\s*(?:finale|obtenue|:\s*)(\d{1,2}(?:[.,]\d{1,2})?)/i',
            '/(\d{1,2}(?:[.,]\d{1,2})?)\s*\/\s*20/i',
            '/moyenne\s*(?::\s*)?(\d{1,2}(?:[.,]\d{1,2})?)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $note = (float) str_replace(',', '.', $matches[1]);
                if ($note >= 0 && $note <= 20) {
                    return $note;
                }
            }
        }

        return null;
    }

    // ─── Helpers privés ──────────────────────────────────────────────────────

    private function cleanText(string $raw): string
    {
        // Supprimer les caractères non imprimables sauf les espaces et retours ligne
        $text = preg_replace('/[^\P{C}\n\t]/u', '', $raw);
        // Normaliser les espaces multiples
        $text = preg_replace('/[ \t]{2,}/', ' ', $text);
        // Limiter les sauts de ligne consécutifs
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    private function errorResult(string $message): array
    {
        return [
            'success' => false,
            'text'    => '',
            'error'   => $message,
            'pages'   => 0,
        ];
    }
}