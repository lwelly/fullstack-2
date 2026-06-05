<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModuleResultPdf extends Model
{
    protected $fillable = [
        'module_id', 'semestre_id', 'academic_year',
        'type', 'original_name', 'file_path', 'disk', 'uploaded_by',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function semestre(): BelongsTo
    {
        return $this->belongsTo(Semestre::class);
    }
}