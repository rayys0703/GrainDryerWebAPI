<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrainType extends Model
{
    use HasFactory;

    protected $table = 'grain_types';
    protected $primaryKey = 'grain_type_id';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'nama_jenis',
        'deskripsi',
    ];

    public function dryingProcesses()
    {
        return $this->hasMany(DryingProcess::class, 'grain_type_id', 'grain_type_id');
    }
}
