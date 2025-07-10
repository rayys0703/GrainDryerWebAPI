<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PredictionEstimation extends Model
{
    protected $fillable = ['process_id', 'estimasi_durasi', 'timestamp'];
}