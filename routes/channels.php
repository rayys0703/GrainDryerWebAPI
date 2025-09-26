<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('dryer.{dryerId}', function ($user, $dryerId) {
    // authorize channel
    return true; // nanti bisa cek apakah user punya akses ke dryer tsb
});
