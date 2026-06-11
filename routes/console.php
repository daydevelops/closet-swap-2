<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('donations:expire-stale')->daily();
