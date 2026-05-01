<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\API\Auth\AuthController;

// Student controllers
use App\Http\Controllers\API\Student\DashboardController    as StudentDashboard;
use App\Http\Controllers\API\Student\NoteController         as StudentNote;
use App\Http\Controllers\API\Student\ReclamationController  as StudentReclamation;
use App\Http\Controllers\API\Student\NotificationController as StudentNotification;
use App\Http\Controllers\API\Student\ProfileController      as StudentProfile;
use App\Http\Controllers\API\Student\ModuleController       as StudentModule;
use App\Http\Controllers\API\Student\DocumentController     as StudentDocument;

// Admin controllers
use App\Http\Controllers\API\Admin\DashboardController    as AdminDashboard;
use App\Http\Controllers\API\Admin\StudentController      as AdminStudent;
use App\Http\Controllers\API\Admin\NoteController         as AdminNote;
use App\Http\Controllers\API\Admin\ReclamationController  as AdminReclamation;
use App\Http\Controllers\API\Admin\SemestreController     as AdminSemestre;
use App\Http\Controllers\API\Admin\DocumentController     as AdminDocument;
use App\Http\Controllers\API\Admin\NotificationController as AdminNotification;
use App\Http\Controllers\API\Admin\SettingController      as AdminSetting;
use App\Http\Controllers\API\Admin\ProfileController      as AdminProfile;

Route::prefix('v1')->group(function () {

    // ══════════════════════════════════════════════════════
    // AUTH — Routes publiques
    // ══════════════════════════════════════════════════════
    Route::prefix('auth')->group(function () {

        Route::post('login', [AuthController::class, 'login']);

        Route::prefix('2fa')->group(function () {
            Route::post('verify', [AuthController::class, 'verify2FA']);
            Route::post('resend', [AuthController::class, 'resendOtp']);
        });

        Route::prefix('register')->group(function () {
            Route::post('verify',       [AuthController::class, 'verifyPreloaded']);
            Route::post('send-otp',     [AuthController::class, 'sendRegistrationOtp']);
            Route::post('verify-otp',   [AuthController::class, 'verifyRegistrationOtp']);
            Route::post('set-password', [AuthController::class, 'setPassword']);
        });

        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get ('me',     [AuthController::class, 'me']);
        });
    });

    // ══════════════════════════════════════════════════════
    // ADMIN
    // ══════════════════════════════════════════════════════
    Route::prefix('admin')
        ->middleware(['auth:sanctum', 'role.check:admin'])
        ->name('api.admin.')
        ->group(function () {

        Route::get('dashboard', [AdminDashboard::class, 'index'])->name('dashboard');

        // Réclamations
        Route::get ('reclamations',               [AdminReclamation::class, 'index']          )->name('reclamations.index');
        Route::get ('reclamations/{id}',          [AdminReclamation::class, 'show']           )->name('reclamations.show');
        Route::put ('reclamations/{id}/status',   [AdminReclamation::class, 'updateStatus']   )->name('reclamations.update-status');
        Route::post('reclamations/{id}/escalate', [AdminReclamation::class, 'escalate']       )->name('reclamations.escalate');
        Route::post('reclamations/{id}/meeting',  [AdminReclamation::class, 'scheduleMeeting'])->name('reclamations.meeting');

        // Semestres
        Route::get ('semestres',                        [AdminSemestre::class, 'index']           )->name('semestres.index');
        Route::post('semestres',                        [AdminSemestre::class, 'store']           )->name('semestres.store');
        Route::put ('semestres/{id}',                   [AdminSemestre::class, 'update']          )->name('semestres.update');
        Route::put ('semestres/{id}/toggle',            [AdminSemestre::class, 'toggle']          )->name('semestres.toggle');
        Route::put ('semestres/{id}/toggle-exam',       [AdminSemestre::class, 'toggleExam']      )->name('semestres.toggle-exam');
        Route::put ('semestres/{id}/toggle-rattrapage', [AdminSemestre::class, 'toggleRattrapage'])->name('semestres.toggle-rattrapage');
        Route::delete('semestres/{id}',                 [AdminSemestre::class, 'destroy']         )->name('semestres.destroy');

        // Modules
        Route::get   ('modules',      [AdminSemestre::class, 'modulesIndex']  )->name('modules.index');
        Route::post  ('modules',      [AdminSemestre::class, 'moduleStore']   )->name('modules.store');
        Route::put   ('modules/{id}', [AdminSemestre::class, 'moduleUpdate']  )->name('modules.update');
        Route::delete('modules/{id}', [AdminSemestre::class, 'moduleDestroy'] )->name('modules.destroy');

        // Étudiants
        Route::get('students',            [AdminStudent::class, 'index']       )->name('students.index');
        Route::get('students/{id}',       [AdminStudent::class, 'show']        )->name('students.show');
        Route::put('students/{id}/status',[AdminStudent::class, 'updateStatus'])->name('students.update-status');

        // Notes
        Route::get('notes',      [AdminNote::class, 'index'])->name('notes.index');
        Route::get('notes/{id}', [AdminNote::class, 'show'] )->name('notes.show');

        // Documents
        Route::get   ('documents',      [AdminDocument::class, 'index']  )->name('documents.index');
        Route::get   ('documents/{id}', [AdminDocument::class, 'show']   )->name('documents.show');
        Route::post  ('documents',      [AdminDocument::class, 'store']  )->name('documents.store');
        Route::delete('documents/{id}', [AdminDocument::class, 'destroy'])->name('documents.destroy');

        // Notifications
        Route::get ('notifications',           [AdminNotification::class, 'index']     )->name('notifications.index');
        Route::post('notifications',           [AdminNotification::class, 'store']     )->name('notifications.store');
        Route::put ('notifications/{id}/read', [AdminNotification::class, 'markAsRead'])->name('notifications.read');

        // Paramètres
        Route::get('settings', [AdminSetting::class, 'index'] )->name('settings.index');
        Route::put('settings', [AdminSetting::class, 'update'])->name('settings.update');

        // Profil admin
        Route::get ('profile',          [AdminProfile::class, 'show']          )->name('profile.show');
        Route::put ('profile',          [AdminProfile::class, 'update']        )->name('profile.update');
        Route::post('profile/photo',    [AdminProfile::class, 'updatePhoto']   )->name('profile.photo');
        Route::put ('profile/password', [AdminProfile::class, 'updatePassword'])->name('profile.password');

        // Niveaux
        Route::get('niveaux', function () {
            $niveaux = DB::table('niveaux')->orderBy('order_index')->get();
            return response()->json(['success' => true, 'data' => $niveaux]);
        })->name('niveaux.index');
    });

    // ══════════════════════════════════════════════════════
    // STUDENT
    // ══════════════════════════════════════════════════════
    Route::prefix('student')
        ->middleware(['auth:sanctum', 'role.check:student'])
        ->name('api.student.')
        ->group(function () {

        Route::get('dashboard', [StudentDashboard::class, 'index'])->name('dashboard');

        // ── Semestres ouverts ──────────────────────────────
        // Retourne uniquement les semestres où is_open = true
        // Ces semestres sont gérés par l'admin dans SemestresView
        // ── Semestres ouverts aux réclamations ──────────────────────
// Dans routes/api.php — groupe student
Route::get('semestres', function () {

    $semestres = DB::table('semestres')
        ->where(function ($q) {
            // Retourner si AU MOINS un type de réclamation est ouvert
            $q->where('is_open',             true)
              ->orWhere('is_exam_open',       true)
              ->orWhere('is_rattrapage_open', true);
        })
        ->select(
            'id',
            'code',
            'label',
            'academic_year',
            'order_index',
            'is_open',
            'is_exam_open',
            'is_rattrapage_open',
            'open_at',
            'close_at',
            'exam_open_at',
            'exam_close_at',
            'rattrapage_open_at',
            'rattrapage_close_at',
            'created_at'
        )
        ->orderBy('order_index')
        ->get()
        ->map(function ($s) {
            // Types disponibles selon ce qui est ouvert
            $types = [];
            if ($s->is_open)             $types[] = 'cc';
            if ($s->is_exam_open)        $types[] = 'examen';
            if ($s->is_rattrapage_open)  $types[] = 'rattrapage';

            return [
                'id'                  => $s->id,
                'code'                => $s->code,
                'label'               => $s->label,
                'academic_year'       => $s->academic_year,
                'is_open'             => (bool) $s->is_open,
                'is_exam_open'        => (bool) $s->is_exam_open,
                'is_rattrapage_open'  => (bool) $s->is_rattrapage_open,
                'available_types'     => $types,   // ← utilisé par le frontend
                'open_at'             => $s->open_at,
                'close_at'            => $s->close_at,
            ];
        });

    return response()->json([
        'success' => true,
        'data'    => $semestres,
    ]);

})->name('semestres.index');

        // Notes
        Route::get('notes',      [StudentNote::class, 'index'])->name('notes.index');
        Route::get('notes/{id}', [StudentNote::class, 'show'] )->name('notes.show');

        // Réclamations
        Route::get ('reclamations',      [StudentReclamation::class, 'index'] )->name('reclamations.index');
        Route::post('reclamations',      [StudentReclamation::class, 'store'] )->name('reclamations.store');
        Route::get ('reclamations/{id}', [StudentReclamation::class, 'show']  )->name('reclamations.show');
        Route::put ('reclamations/{id}', [StudentReclamation::class, 'update'])->name('reclamations.update');

        // Notifications
        Route::get('notifications',            [StudentNotification::class, 'index']      )->name('notifications.index');
        Route::put('notifications/{id}/read',  [StudentNotification::class, 'markAsRead'] )->name('notifications.read');
        Route::put('notifications/read-all',   [StudentNotification::class, 'markAllRead'])->name('notifications.read-all');

        // Profil
        Route::get ('profile',          [StudentProfile::class, 'show']          )->name('profile.show');
        Route::put ('profile',          [StudentProfile::class, 'update']        )->name('profile.update');
        Route::post('profile/photo',    [StudentProfile::class, 'updatePhoto']   )->name('profile.photo');
        Route::put ('profile/password', [StudentProfile::class, 'updatePassword'])->name('profile.password');

        // Modules — filtrés par semestre_id
        // ?semestre_id=X retourne les modules du semestre demandé
        Route::get('modules', [StudentModule::class, 'index'])->name('modules.index');

        // Documents
        Route::get('documents',      [StudentDocument::class, 'index'])->name('documents.index');
        Route::get('documents/{id}', [StudentDocument::class, 'show'] )->name('documents.show');
    });
});
