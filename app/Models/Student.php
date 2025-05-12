<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = ['first_name', 'last_name'];

    public function mentors()
    {
        return $this->belongsToMany(Mentor::class, 'student_mentor');
    }

    public function votedUsers()
    {
        return $this->hasMany(User::class, 'voted_student_id');
    }
}
