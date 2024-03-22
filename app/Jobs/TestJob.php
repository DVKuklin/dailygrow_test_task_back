<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
    /**
     * Create a new job instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
        info('задание отправлено в очередь');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        for ($i=0;$i<1000000;$i++) {
            for ($j=0;$j<1000;$j++) {
            }
        }

        info('Задание выполнено');
        info($this->data);
    }
}
