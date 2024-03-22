<?php

namespace App\Services;

class TestScheduleService {
    public function __invoke() {
        info(1);
        info(2);
        sleep(5);
        info(3);
        info(4);
    }
}