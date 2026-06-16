<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Client;
use App\Models\ClientSaveRequest;
use Throwable;

class StoreClient implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $saveRequestId,)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $saveRequest = ClientSaveRequest::query()->find($this->saveRequestId);

        if (! $saveRequest || $saveRequest->status === 'completed') {
            return;
        }

        $saveRequest->update(['status' => 'processing']);

        $client = Client::create($saveRequest->payload);

        $saveRequest->update([
            'client_id' => $client->id,
            'status' => 'completed',
        ]);
    }

    public function failed(Throwable $exception): void
    {
        ClientSaveRequest::query()
            ->whereKey($this->saveRequestId)
            ->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
    }

}
