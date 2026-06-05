<?php

namespace App\Services;

use App\Models\Reclamation;
use App\Models\ReclamationAiAnalysis;
use App\Models\ReclamationAttachment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class AiAnalysisService
{
    public function __construct(
        private PdfParserService $pdfParser
    ) {}

    /**
     * Déclenche ou récupère l'analyse IA d'une réclamation.
     *
     * @param  Reclamation  $reclamation
     * @param  int|null     $attachmentId      PDF spécifique à analyser (optionnel)
     * @param  bool         $forceReanalyze    Relance même si une analyse existe déjà
     * @return ReclamationAiAnalysis
     * @throws \Exception
     */
    public function analyze(
        Reclamation $reclamation,
        ?int $attachmentId = null,
        bool $forceReanalyze = false
    ): ReclamationAiAnalysis {

        // Retourner l'analyse existante si disponible et non forcé
        if (! $forceReanalyze && $existing = $reclamation->aiAnalysis) {
            return $existing;
        }

        $startTime = microtime(true);

        // ── 1. Identifier la pièce jointe PDF à analyser ─────────────────────
        $attachment = $this->resolveAttachment($reclamation, $attachmentId);

        // ── 2. Extraire le texte du PDF ───────────────────────────────────────
        $pdfResult = ['success' => false, 'text' => '', 'error' => 'Aucune pièce jointe PDF disponible', 'pages' => 0];
        if ($attachment) {
            $filePath  = $attachment->file_path ?? $attachment->storage_path;
            $pdfResult = $this->pdfParser->extractText(
            $filePath,
            $attachment->disk ?? $this->resolveDisk($filePath)
            );
        }

        // ── 3. Charger le contexte complet depuis la BD ───────────────────────
        $context = $this->buildContext($reclamation);

        // ── 4. Construire le prompt ───────────────────────────────────────────
        $prompt = $this->buildPrompt($context, $pdfResult['text']);

        // ── 5. Appeler OpenAI ─────────────────────────────────────────────────
        $openaiResponse = $this->callOpenAI($prompt);

        // ── 6. Parser la réponse structurée ──────────────────────────────────
        $parsed = $this->parseOpenAIResponse($openaiResponse['content']);

        $processingTime = (int) ((microtime(true) - $startTime) * 1000);

        // ── 7. Persister le résultat ──────────────────────────────────────────
        $analysis = ReclamationAiAnalysis::updateOrCreate(
            ['reclamation_id' => $reclamation->id],
            [
                'attachment_id'           => $attachment?->id,
                'pdf_extracted_text'      => $pdfResult['text'] ?: null,
                'pdf_parsed_successfully' => $pdfResult['success'],
                'pdf_parse_error'         => $pdfResult['error'],
                'prompt_sent'             => $prompt,
                'raw_response'            => $openaiResponse['content'],
                'decision'                => $parsed['decision'],
                'confidence_score'        => $parsed['confidence_score'],
                'explanation'             => $parsed['explanation'],
                'recommendation'          => $parsed['recommendation'],
                'note_from_pdf'           => $parsed['note_from_pdf'] ?? $this->pdfParser->extractNoteFromText($pdfResult['text']),
                'note_from_db'            => $context['note_officielle'],
                'note_claimed'            => $reclamation->note_reclamee,
                'note_discrepancy_found'  => $parsed['note_discrepancy_found'] ?? false,
                'discrepancy_details'     => $parsed['discrepancy_details'],
                'openai_model'            => config('openai.model', 'gpt-4o'),
                'prompt_tokens'           => $openaiResponse['usage']['prompt_tokens'] ?? null,
                'completion_tokens'       => $openaiResponse['usage']['completion_tokens'] ?? null,
                'total_tokens'            => $openaiResponse['usage']['total_tokens'] ?? null,
                'processing_time_ms'      => $processingTime,
                'analyzed_by'             => Auth::id(),
            ]
        );

        Log::info('AiAnalysisService: analyse terminée', [
            'reclamation_id' => $reclamation->id,
            'decision'       => $parsed['decision'],
            'tokens'         => $openaiResponse['usage']['total_tokens'] ?? 0,
            'time_ms'        => $processingTime,
        ]);

        return $analysis;
    }

    // ─── Construction du contexte ─────────────────────────────────────────────

    private function buildContext(Reclamation $reclamation): array
    {
        $reclamation->load(['student.user', 'module', 'semestre', 'note']);

        $student = $reclamation->student;
        $module  = $reclamation->module;
       // Chercher la note directement si note_id est null
$note = $reclamation->note ?? \Illuminate\Support\Facades\DB::table('notes')
    ->where('student_id',    $reclamation->student_id)
    ->where('module_id',     $reclamation->module_id)
    ->where('academic_year', $reclamation->academic_year)
    ->first();

$noteOfficielle = match ($reclamation->type) {
    'controle'   => $note?->note_controle,
    'examen'     => $note?->note_examen,
    'rattrapage' => $note?->note_rattrapage,
    default      => $note?->note_finale,
} ?? $reclamation->note_actuelle; // ← fallback sur la note saisie par l'étudiant

        return [
            'reference'          => $reclamation->reference_number,
            'type'               => $reclamation->type,
            'academic_year'      => $reclamation->academic_year,
            'note_actuelle'      => $reclamation->note_actuelle,
            'note_reclamee'      => $reclamation->note_reclamee,
            'note_officielle'    => $noteOfficielle,
            'justification'      => $reclamation->justification,
            'student_name'       => $student?->user?->name ?? 'Inconnu',
            'student_matricule'  => $student?->matricule ?? 'N/A',
            'student_filiere'    => $student?->filiere?->nom ?? 'N/A',
            'student_niveau'     => $student?->niveau?->nom ?? 'N/A',
            'module_code'        => $module?->code ?? 'N/A',
            'module_nom'         => $module?->name ?? $module?->nom ?? 'N/A',
            'module_coefficient' => $module?->coefficient ?? 1,
            'semestre_nom'       => $reclamation->semestre?->nom ?? 'N/A',
        ];
    }

    // ─── Construction du prompt ───────────────────────────────────────────────

    private function buildPrompt(array $context, string $pdfText): string
    {
        $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $pdfSection  = $pdfText
            ? "TEXTE EXTRAIT DE LA PIÈCE JOINTE PDF :\n---\n{$pdfText}\n---"
            : "PIÈCE JOINTE PDF : Aucun texte extractible (PDF absent, scanné ou protégé).";

        return <<<PROMPT
Tu es un expert en gestion académique chargé d'analyser la validité d'une réclamation de note d'étudiant.

DONNÉES DE LA RÉCLAMATION (issues de la base de données officielle) :
{$contextJson}

{$pdfSection}

MISSION :
1. Comparer la note réclamée par l'étudiant avec la note officielle enregistrée en base de données.
2. Analyser la pièce jointe PDF si disponible pour identifier toute preuve d'une note différente.
3. Évaluer la cohérence de la justification fournie par l'étudiant.
4. Rendre une décision motivée.

RÈGLES D'ANALYSE :
- Si la note du PDF correspond à la note réclamée ET diffère de la note BD → réclamation potentiellement fondée
- Si la note du PDF correspond à la note BD → réclamation probablement non fondée
- Si aucun PDF ou texte inexploitable → décision basée sur la justification seule, confiance réduite
- Une différence ≤ 0.25 point peut être une erreur d'arrondi, ne pas conclure trop vite
- Toujours être prudent et équitable envers l'étudiant

RÉPONSE REQUISE — JSON STRICT UNIQUEMENT, sans backticks, sans texte avant ou après :
{
  "decision": "founded" | "unfounded" | "uncertain",
  "confidence_score": 0.0 à 1.0,
  "explanation": "Explication détaillée en français (3-5 phrases)",
  "recommendation": "Action recommandée à l'administrateur en français",
  "note_from_pdf": null ou valeur numérique extraite du PDF,
  "note_discrepancy_found": true | false,
  "discrepancy_details": null ou "Description de la discordance trouvée"
}
PROMPT;
    }

    // ─── Appel OpenAI ─────────────────────────────────────────────────────────

    private function callOpenAI(string $prompt): array
{
    $client = new \GuzzleHttp\Client();

    $response = $client->post('https://api.groq.com/openai/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . config('services.groq.api_key'),
            'Content-Type'  => 'application/json',
        ],
        'json' => [
            'model'       => 'llama-3.3-70b-versatile',
            'max_tokens'  => 1000,
            'temperature' => 0.2,
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' => 'Tu es un assistant expert en gestion académique. Tu réponds TOUJOURS en JSON valide strict, sans aucun texte supplémentaire.',
                ],
                [
                    'role'    => 'user',
                    'content' => $prompt,
                ],
            ],
        ],
    ]);

    $data = json_decode($response->getBody()->getContents(), true);

    return [
        'content' => $data['choices'][0]['message']['content'],
        'usage'   => [
            'prompt_tokens'     => $data['usage']['prompt_tokens'] ?? null,
            'completion_tokens' => $data['usage']['completion_tokens'] ?? null,
            'total_tokens'      => $data['usage']['total_tokens'] ?? null,
        ],
    ];
}

    // ─── Parsing de la réponse ────────────────────────────────────────────────

    private function parseOpenAIResponse(string $content): array
    {
        // Nettoyer les éventuels blocs markdown
        $clean = preg_replace('/```(?:json)?\n?/', '', $content);
        $clean = trim($clean);

        $data = json_decode($clean, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('AiAnalysisService: réponse OpenAI non parsable', ['content' => $content]);

            // Fallback sécurisé
            return [
                'decision'               => 'uncertain',
                'confidence_score'       => 0.0,
                'explanation'            => 'Erreur lors du traitement de la réponse IA. Analyse manuelle requise.',
                'recommendation'         => 'Procéder à une vérification manuelle de cette réclamation.',
                'note_from_pdf'          => null,
                'note_discrepancy_found' => false,
                'discrepancy_details'    => null,
            ];
        }

        // Valider et normaliser les champs
        $validDecisions = ['founded', 'unfounded', 'uncertain'];
        $decision = in_array($data['decision'] ?? '', $validDecisions) ? $data['decision'] : 'uncertain';

        $confidence = (float) ($data['confidence_score'] ?? 0.5);
        $confidence = max(0.0, min(1.0, $confidence));

        return [
            'decision'               => $decision,
            'confidence_score'       => $confidence,
            'explanation'            => $data['explanation'] ?? null,
            'recommendation'         => $data['recommendation'] ?? null,
            'note_from_pdf'          => isset($data['note_from_pdf']) ? (float) $data['note_from_pdf'] : null,
            'note_discrepancy_found' => (bool) ($data['note_discrepancy_found'] ?? false),
            'discrepancy_details'    => $data['discrepancy_details'] ?? null,
        ];
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function resolveAttachment(Reclamation $reclamation, ?int $attachmentId): ?object
{
    // Si un attachment spécifique est demandé
    if ($attachmentId) {
        return \App\Models\ReclamationAttachment::where('reclamation_id', $reclamation->id)
            ->where('id', $attachmentId)
            ->first();
    }

    // Chercher automatiquement le PDF officiel du module
    $modulePdf = \App\Models\ModuleResultPdf::where('module_id',    $reclamation->module_id)
        ->where('semestre_id',   $reclamation->semestre_id)
        ->where('academic_year', $reclamation->academic_year)
        ->where('type',          $reclamation->type)
        ->first();

    if ($modulePdf) {
        // Retourner un objet compatible avec la suite du code
        return (object) [
            'id'           => $modulePdf->id,
            'file_path'    => $modulePdf->file_path,
            'storage_path' => $modulePdf->file_path,
            'disk'         => $modulePdf->disk,
            'mime_type'    => 'application/pdf',
        ];
    }

    return null;
}

    private function resolveDisk(string $storagePath): string
    {
        // Heuristique basée sur le chemin — à adapter selon votre ReclamationService
        return str_contains($storagePath, 'private') ? 'private' : 'public';
    }
}