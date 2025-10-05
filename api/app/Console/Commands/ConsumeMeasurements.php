<?php

namespace App\Console\Commands;

use App\Models\DeviceMeasurement;
use App\Services\AlertProcessorService;
use App\Services\MeasurementsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Contracts\ConsumerMessage;

class ConsumeMeasurements extends Command
{
    protected $signature = 'kafka:consume {topic=lab08_measurements}';
    protected $description = 'Consume measurements from Kafka topic and store in database';

    public function __construct(
        protected AlertProcessorService $alertProcessor,
        protected MeasurementsService $measurementsService
    )
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $topic = $this->argument('topic');

        $this->info("Starting Kafka consumer for topic: {$topic}");

        $consumer = Kafka::consumer()
            ->subscribe($topic)
            ->withHandler(function (ConsumerMessage $message) {
                $this->processMessage($message);
            })
            ->build();

        $consumer->consume();

        return Command::SUCCESS;
    }

    private function processMessage(ConsumerMessage $message): void
    {
        try {
            $body = $message->getBody();

            $this->info("Received message: " . json_encode($body));

            $measurement = $this->measurementsService->storeMeasure($body);
            $this->info("Measurement stored with ID: {$measurement->id}");

            $this->alertProcessor->processMeasurement($measurement);

            $this->info("Alerts processed for measurement ID: {$measurement->id}");
        } catch (\Exception $e) {
            Log::error("Failed to process Kafka message", [
                'error' => $e->getMessage(),
                'message' => $message->getBody(),
            ]);
            $this->error("Error processing message: " . $e->getMessage());
        }
    }
}
