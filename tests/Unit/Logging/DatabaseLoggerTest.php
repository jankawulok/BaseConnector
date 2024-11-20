<?php

namespace Tests\Unit\Logging;

use Tests\TestCase;
use App\Logging\DatabaseLogger;
use App\Models\Log;
use App\Models\Integration;
use Monolog\Level;
use Monolog\LogRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DatabaseLoggerTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseLogger $logger;
    private Integration $integration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = new DatabaseLogger();

        // Create a test integration
        $this->integration = Integration::factory()->create([
            'name' => 'Test Integration',
            'enabled' => true
        ]);
    }

    /** @test */
    public function it_creates_log_entry_when_integration_id_is_provided()
    {
        // Arrange
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Error,
            message: 'Test error message',
            context: ['integration_id' => $this->integration->id]
        );

        // Act
        $this->logger->handle($record);

        // Assert
        $this->assertDatabaseHas('logs', [
            'integration_id' => $this->integration->id,
            'level' => Level::Error->value,
            'message' => 'Test error message',
        ]);
    }

    /** @test */
    public function it_does_not_create_log_entry_when_integration_id_is_missing()
    {
        // Arrange
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Error,
            message: 'Test error message',
            context: []
        );

        // Act
        $this->logger->handle($record);

        // Assert
        $this->assertDatabaseCount('logs', 0);
    }

    /** @test */
    public function it_stores_context_as_json()
    {
        // Arrange
        $context = [
            'integration_id' => $this->integration->id,
            'extra_data' => 'test',
            'nested' => ['foo' => 'bar']
        ];

        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'Test message with context',
            context: $context
        );

        // Act
        $this->logger->handle($record);

        // Assert
        $log = Log::first();
        $this->assertEquals($context, $log->context);
    }
}
