<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class ProcessPodcast implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user)
    {
        
        $this->user = $user;
        info($this->user);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $qwe = 3;
        for ($i=0;$i<1000000;$i++) {
            for ($j=0;$j<100;$j++) {
            }
        }
        info($this->user);
        info('proccessPodcast-------++'.$qwe.'   ');
    }
}
