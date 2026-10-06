<?php

namespace App\Events;

use App\Models\PowerOutage;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OutageResolved
{
    use Dispatchable, SerializesModels;

    public function __construct(public PowerOutage $outage) {}
}