<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('dryer.{dryerId}', function ($user, $dryerId) {
    return true;
});
