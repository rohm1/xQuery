<?php
/**
 * @author rohm1
 * @link https://github.com/rohm1/xQuery
 */

namespace RPBase\XQuery;

class Event
{

    /**
     * @var bool
     */
    protected $propagationStopped = false;

    /**
     * @return Event
     */
    public function stopPropagation()
    {
        $this->propagationStopped = true;
        return $this;
    }

    /**
     * @return bool
     */
    public function isPropagationStopped()
    {
        return $this->propagationStopped;
    }

}
