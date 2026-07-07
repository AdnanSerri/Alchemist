<?php

namespace Laravel\Octane\Events;

/**
 * Stand-in for Octane's event class (Octane itself is not a dev dependency).
 * Its presence makes the service provider register the request-boundary
 * flush listener, letting tests prove the Octane integration end to end.
 */
class RequestReceived {}
