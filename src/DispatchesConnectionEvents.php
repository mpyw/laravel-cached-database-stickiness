<?php

namespace Mpyw\LaravelCachedDatabaseStickiness;

use Mpyw\LaravelCachedDatabaseStickiness\Events\RecordsHaveBeenModified;

/**
 * Class DispatchesConnectionEvents
 *
 * @mixin \Illuminate\Database\Connection
 */
trait DispatchesConnectionEvents
{
    /**
     * Dispatch RecordsHaveBeenModified event when records newly modified.
     *
     * @param bool $value
     */
    public function recordsHaveBeenModified($value = true)
    {
        if (!$this->recordsModified && ($this->recordsModified = $value) && ($dispatcher = $this->getEventDispatcher())) {
            $dispatcher->dispatch(new RecordsHaveBeenModified($this));
        }
    }
}
