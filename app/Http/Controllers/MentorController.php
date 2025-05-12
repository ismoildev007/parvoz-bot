<?php

namespace App\Http\Controllers;

use App\Models\Mentor;
use App\Models\Student;
use Illuminate\Http\Request;

class MentorController extends Controller
{
    public function index()
    {
        $mentors = Mentor::with('students')->get();
        return view('mentors.index', compact('mentors'));
    }

    public function create()
    {
        $students = Student::all();
        return view('mentors.create', compact('students'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'students' => 'array',
            'students.*' => 'exists:students,id'
        ]);

        $mentor = Mentor::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name']
        ]);

        if (!empty($validated['students'])) {
            $mentor->students()->attach($validated['students']);
        }

        return redirect()->route('mentors.index')->with('success', 'Mentor created successfully');
    }

    public function show(Mentor $mentor)
    {
        $mentor->load('students');
        return view('mentors.show', compact('mentor'));
    }

    public function edit(Mentor $mentor)
    {
        $students = Student::all();
        $mentor->load('students');
        return view('mentors.edit', compact('mentor', 'students'));
    }

    public function update(Request $request, Mentor $mentor)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'students' => 'array',
            'students.*' => 'exists:students,id'
        ]);

        $mentor->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name']
        ]);

        $mentor->students()->sync($validated['students'] ?? []);

        return redirect()->route('mentors.index')->with('success', 'Mentor updated successfully');
    }

    public function destroy(Mentor $mentor)
    {
        $mentor->students()->detach();
        $mentor->delete();
        return redirect()->route('mentors.index')->with('success', 'Mentor deleted successfully');
    }
}
