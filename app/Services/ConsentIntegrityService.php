<?php

namespace App\Services;

use App\Models\ConsentEvent;
use App\Models\Evidence;
use RuntimeException;

class ConsentIntegrityService
{
    public function attachEvidence(ConsentEvent $event, Evidence $evidence): void
    {
        if ($event->vault_id !== $evidence->vault_id) {
            throw new RuntimeException('Evidence and consent event must belong to the same vault.');
        }

        $event->evidence()->syncWithoutDetaching([$evidence->id]);
    }
}
