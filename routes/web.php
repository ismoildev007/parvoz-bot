<?php

use App\Http\Controllers\MentorController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('students.index');
});

Route::resource('mentors', MentorController::class);
Route::resource('students', StudentController::class);
