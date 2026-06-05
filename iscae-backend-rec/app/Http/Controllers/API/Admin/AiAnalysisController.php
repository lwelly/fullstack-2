<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AiAnalysisResource;
use App\Models\Reclamation;
use App\Services\AiAnalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiAnalysisController extends Controller
{
    public function __construct(
        private AiAnalysisService $aiService
    ) {}

    /**
     * POST /api/v1/admin/reclamations/{id}/analyze
     * Déclenche l'analyse IA d'une réclamation.
     */
    public function analyze(Request $request, int $id): JsonResponse
    {
        $reclamation = Reclamation::with(['student.user', 'module', 'semestre', 'note', 'attachments', 'aiAnalysis'])
            ->findOrFail($id);

        $request->validate([
            'attachment_id'   => ['nullable', 'integer', 'exists:reclamation_attachments,id'],
            'force_reanalyze' => ['boolean'],
        ]);

        try {
            $analysis = $this->aiService->analyze(
                reclamation:    $reclamation,
                attachmentId:   $request->input('attachment_id'),
                forceReanalyze: $request->boolean('force_reanalyze', false)
            );

            return response()->json([
                'success' => true,
                'message' => 'Analyse IA complétée avec succès.',
                'data'    => new AiAnalysisResource($analysis),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'analyse IA : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/admin/reclamations/{id}/analysis
     * Retourne l'analyse existante sans rappeler OpenAI.
     */
    public function show(int $id): JsonResponse
    {
        $reclamation = Reclamation::findOrFail($id);
        $analysis    = $reclamation->aiAnalysis;

        if (! $analysis) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune analyse IA disponible pour cette réclamation.',
                'data'    => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new AiAnalysisResource($analysis),
        ]);
    }
}