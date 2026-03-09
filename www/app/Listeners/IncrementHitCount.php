<?php

namespace App\Listeners;

use App\Events\LinkVisited;
use App\Models\ShortLink;
use App\Models\ShortLinkPassword;

class IncrementHitCount
{
    public function handle(LinkVisited $event): void
    {
        ShortLink::where('id', $event->link->id)->increment('hit_count');

        if ($event->password) {
            ShortLinkPassword::where('id', $event->password->id)->increment('hit_count');

            $event->password->refresh();

            if ($event->password->max_uses !== null && $event->password->hit_count >= $event->password->max_uses) {
                $event->password->update(['is_active' => false]);
            }
        }
    }
}
