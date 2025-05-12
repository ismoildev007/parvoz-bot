<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Mentor;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::with('mentors')->get();
        return view('students.index', compact('students'));
    }

    public function create()
    {
        $mentors = Mentor::all();
        return view('students.create', compact('mentors'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'mentors' => 'nullable|array',
            'mentors.*.first_name' => 'required|string|max:255',
            'mentors.*.last_name' => 'required|string|max:255',
        ]);

        $student = Student::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name']
        ]);

        if (!empty($validated['mentors'])) {
            $mentorIds = [];
            foreach ($validated['mentors'] as $mentorData) {
                $mentor = Mentor::firstOrCreate(
                    [
                        'first_name' => $mentorData['first_name'],
                        'last_name' => $mentorData['last_name']
                    ]
                );
                $mentorIds[] = $mentor->id;
            }
            $student->mentors()->attach($mentorIds);
        }

        return redirect()->route('students.index')->with('success', 'Student created successfully');
    }

    public function show(Student $student)
    {
        $student->load('mentors', 'votedUsers');
        return view('students.show', compact('student'));
    }

    public function edit(Student $student)
    {
        $mentors = Mentor::all();
        $student->load('mentors');
        return view('students.edit', compact('student', 'mentors'));
    }

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'mentors' => 'nullable|array',
            'mentors.*.first_name' => 'required|string|max:255',
            'mentors.*.last_name' => 'required|string|max:255',
        ]);

        $student->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name']
        ]);

        if (!empty($validated['mentors'])) {
            $mentorIds = [];
            foreach ($validated['mentors'] as $mentorData) {
                $mentor = Mentor::firstOrCreate(
                    [
                        'first_name' => $mentorData['first_name'],
                        'last_name' => $mentorData['last_name']
                    ]
                );
                $mentorIds[] = $mentor->id;
            }
            $student->mentors()->sync($mentorIds);
        } else {
            $student->mentors()->sync([]);
        }

        return redirect()->route('students.index')->with('success', 'Student updated successfully');
    }


    public function destroy(Student $student)
    {
        $student->mentors()->detach();
        $student->delete();
        return redirect()->route('students.index')->with('success', 'Student deleted successfully');
    }
}
