<?php

use Illuminate\Support\Facades\Route;
use Mathieu\Cockpit\Http\Controllers\CockpitController;
use Mathieu\Cockpit\Http\Controllers\CommandController;
use Mathieu\Cockpit\Http\Controllers\EventController;
use Mathieu\Cockpit\Http\Controllers\JobController;
use Mathieu\Cockpit\Http\Controllers\LogController;
use Mathieu\Cockpit\Http\Controllers\RouteController;
use Mathieu\Cockpit\Http\Controllers\ScheduleController;

Route::get('/', [CockpitController::class, 'index'])->name('cockpit.dashboard');

// Routes
Route::get('/routes', [RouteController::class, 'index'])->name('cockpit.routes');

// Commands
Route::get('/commands', [CommandController::class, 'index'])->name('cockpit.commands');
Route::post('/commands/run', [CommandController::class, 'run'])->name('cockpit.commands.run');
Route::delete('/commands/history', [CommandController::class, 'clearHistory'])->name('cockpit.commands.history.clear');

// Jobs / Queues
Route::get('/jobs', [JobController::class, 'index'])->name('cockpit.jobs');
Route::post('/jobs/failed/{id}/retry', [JobController::class, 'retry'])->name('cockpit.jobs.retry');
Route::delete('/jobs/failed/{id}', [JobController::class, 'destroy'])->name('cockpit.jobs.destroy');
Route::delete('/jobs/failed', [JobController::class, 'clearFailed'])->name('cockpit.jobs.clear-failed');

// Logs
Route::get('/logs', [LogController::class, 'index'])->name('cockpit.logs');
Route::delete('/logs', [LogController::class, 'clear'])->name('cockpit.logs.clear');
Route::get('/logs/download', [LogController::class, 'download'])->name('cockpit.logs.download');

// Schedule
Route::get('/schedule', [ScheduleController::class, 'index'])->name('cockpit.schedule');
Route::post('/schedule/run', [ScheduleController::class, 'run'])->name('cockpit.schedule.run');

// Events
Route::get('/events', [EventController::class, 'index'])->name('cockpit.events');
