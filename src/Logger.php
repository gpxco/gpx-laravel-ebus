<?php

namespace GPX\EventBus;

class Logger implements \DanikDantist\QueueWrapper\Interfaces\iLogable
{
    public function info($info)
    {
        \Log::info($info);
    }

    public function error($error)
    {
        \Log::error($error);
    }

    public function debug($debug)
    {
        \Log::debug($debug);
    }
}
