<?php

namespace App\Services\Gmail;

/** Tracked links could not be created; the send is abandoned before anything is queued. */
class TrackingSetupFailed extends \RuntimeException
{
}
