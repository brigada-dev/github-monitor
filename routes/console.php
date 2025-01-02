<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('repositories:send-daily-notifications')->dailyAt('17:00');
