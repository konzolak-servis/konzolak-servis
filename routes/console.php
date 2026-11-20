<?php

use Illuminate\Support\Facades\Schedule;

// Denní záloha databáze a nahraných souborů ve 3:00 (běží, pokud jede scheduler / cron).
Schedule::command('zaloha:data')->dailyAt('03:00')->withoutOverlapping();

// EET 2.0: dodatečné odeslání tržeb, které neodešly hned (zákonná lhůta 48 h).
Schedule::command('eet:odeslat')->everyMinute()->withoutOverlapping();
