<?php
use Illuminate\Support\Facades\Route;

foreach (glob(__DIR__ . '/Api/*.php') as $filename) {
    $this->loadRoutesFrom($filename);
}
