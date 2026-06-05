<?php
namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\ModuleResultPdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ModuleResultPdfController extends Controller
{
    // Liste tous les PDFs uploadés
    public function index(): JsonResponse
    {
        $pdfs = ModuleResultPdf::with(['module', 'semestre'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($p) => [
                'id'            => $p->id,
                'module'        => ['id' => $p->module->id, 'name' => $p->module->name ?? $p->module->nom, 'code' => $p->module->code],
                'semestre'      => ['id' => $p->semestre->id, 'label' => $p->semestre->label, 'code' => $p->semestre->code],
                'academic_year' => $p->academic_year,
                'type'          => $p->type,
                'original_name' => $p->original_name,
                'created_at'    => $p->created_at,
            ]);

        return response()->json(['success' => true, 'data' => $pdfs]);
    }

    // Upload un PDF de résultats
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'module_id'    => ['required', 'exists:modules,id'],
            'semestre_id'  => ['required', 'exists:semestres,id'],
            'academic_year'=> ['required', 'string'],
            'type'         => ['required', 'in:controle,examen,rattrapage'],
            'pdf'          => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $file       = $request->file('pdf');
        $storedName = Str::uuid() . '.pdf';
        $path       = $file->storeAs('module-results', $storedName, 'public');

        // Remplacer si déjà existant
        $existing = ModuleResultPdf::where([
            'module_id'    => $request->module_id,
            'semestre_id'  => $request->semestre_id,
            'academic_year'=> $request->academic_year,
            'type'         => $request->type,
        ])->first();

        if ($existing) {
            Storage::disk($existing->disk)->delete($existing->file_path);
            $existing->update([
                'original_name' => $file->getClientOriginalName(),
                'file_path'     => $path,
                'uploaded_by'   => auth()->id(),
            ]);
            $pdf = $existing;
        } else {
            $pdf = ModuleResultPdf::create([
                'module_id'    => $request->module_id,
                'semestre_id'  => $request->semestre_id,
                'academic_year'=> $request->academic_year,
                'type'         => $request->type,
                'original_name'=> $file->getClientOriginalName(),
                'file_path'    => $path,
                'disk'         => 'public',
                'uploaded_by'  => auth()->id(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'PDF de résultats uploadé avec succès.',
            'data'    => ['id' => $pdf->id, 'original_name' => $pdf->original_name],
        ]);
    }

    // Supprimer un PDF
    public function destroy(int $id): JsonResponse
    {
        $pdf = ModuleResultPdf::findOrFail($id);
        Storage::disk($pdf->disk)->delete($pdf->file_path);
        $pdf->delete();
        return response()->json(['success' => true, 'message' => 'PDF supprimé.']);
    }
}