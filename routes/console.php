<?php

use Illuminate\Support\Facades\Schedule;

// Denní záloha databáze a nahraných souborů ve 3:00 (běží, pokud jede scheduler / cron).
Schedule::command('zaloha:data')->dailyAt('03:00')->withoutOverlapping();
