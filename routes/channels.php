<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('dashboard.{dryerId}', function ($user, $dryerId) {
    return $user && App\Models\BedDryer::where('dryer_id', $dryerId)
        ->where('user_id', $user->id) 
        ->exists();
});