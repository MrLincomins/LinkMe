<?php

namespace App\Events;

use App\Models\ShortLink;
use App\Models\ShortLinkPassword;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LinkVisited
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ShortLink $link,
        public ?ShortLinkPassword $password = null,
        public ?string $ip = null,
        public ?string $userAgent = null,
        public ?string $referer = null
    ) {}
}
