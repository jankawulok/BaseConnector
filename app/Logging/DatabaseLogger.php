<?php

namespace App\Logging;

use App\Models\Log;
use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;

class DatabaseLogger extends AbstractProcessingHandler
{
    public function __construct($level = Logger::DEBUG)
    {
        parent::__construct($level);
    }

    protected function write(\Monolog\LogRecord $record): void
    {
        try {
            $level = Logger::getLevelName($record->level->value);
            $level = strtolower($level);

            Log::create([
                'integration_id' => $record->context['integration_id'] ?? null,
                'level' => $level,
                'message' => $record->message,
                'context' => $record->context
            ]);
        } catch (\Exception $e) {
            // Log to default channel if database logging fails
            \Illuminate\Support\Facades\Log::channel('single')
                ->error('Database logger failed: ' . $e->getMessage());
        }
    }
}
