<?php

// app/Helpers/Notifier.php
namespace App\Helpers;

use App\Models\AppNotification;

class Notifier
{
    /**
     * Simpan notifikasi ke DB (riwayat). Flutter akan mem-poll endpoint untuk menarik ini.
     */
    public static function store(
        int $userId,
        ?int $dryerId,
        ?int $processId,
        string $type,
        string $title,
        string $body,
        array $data = []
    ): void {
        AppNotification::create([
            'user_id'    => $userId,
            'dryer_id'   => $dryerId,
            'process_id' => $processId,
            'type'       => $type,
            'title'      => $title,
            'body'       => $body,
            'data'       => $data,
        ]);
    }
}
