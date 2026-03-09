<?php

namespace App\Listeners;

use App\Events\LinkVisited;
use App\Models\LinkVisit;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogVisit implements ShouldQueue
{
    public function handle(LinkVisited $event): void
    {
        LinkVisit::create([
            'short_link_id' => $event->link->id,
            'short_link_password_id' => $event->password?->id,
            'ip' => $event->ip,
            'user_agent' => $event->userAgent,
            'referer' => $event->referer,
        ]);
    }
}
