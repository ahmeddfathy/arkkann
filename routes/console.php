<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Schedule;

Schedule::command('inspire')->hourly();

// تسجيل المهام المجدولة
Schedule::command('attendance:create-daily')
    ->everyMinute()
    ->name('attendance:create-daily');

Schedule::command('check:birthdays')
    ->everyMinute()
    ->name('check:birthdays');

Schedule::command('check:contracts')
    ->everyMinute()
    ->name('check:contracts');
