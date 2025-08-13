<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PredictionEstimation extends Model
{
    protected $table = 'prediction_estimations';

    protected $fillable = [
        'process_id',
        'estimasi_durasi',
        'timestamp',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function process()
    {
        return $this->belongsTo(DryingProcess::class, 'process_id', 'process_id');
    }
}
