<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrainType extends Model
{
    use HasFactory;

    protected $table = 'grain_types';
    protected $primaryKey = 'grain_type_id';
    public $timestamps = true; // Pastikan ini true karena migrationnya pakai timestamps()

    protected $fillable = [
        'nama_jenis',
        'deskripsi',
    ];

    public $incrementing = true;

    /**
     * Get the drying processes for the grain type.
     */
    public function dryingProcesses()
    {
        return $this->hasMany(DryingProcess::class, 'grain_type_id', 'grain_type_id');
    }
}