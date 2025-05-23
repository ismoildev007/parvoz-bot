<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mentor extends Model
{
    use HasFactory;

    protected $fillable = ['first_name', 'last_name'];

    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_mentor');
    }
}
