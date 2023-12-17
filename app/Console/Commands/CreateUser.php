<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-user {--login=} {--password=} {--b24link=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = User::where('name',$this->option('login'))->first();
        if ($user) {
            $this->info('Пользователь с таким именем уже существует.');
            return;
        }

        $res = User::create([
            'name'=>$this->option('login'),
            'password'=>Hash::make($this->option('password')),
            'b24_link'=>$this->option('b24link')
        ]);
        if($res) {
            $this->info("Пользователь успешно создан");
            return 0;
        }

        $this->info("Что то пошло не так. Пользователь не создан.");
    }
}
