<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">Edit Student</h1>
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form action="{{ route('students.update', $student) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label for="first_name" class="form-label">First Name</label>
            <input type="text" name="first_name" id="first_name" class="form-control" value="{{ old('first_name', $student->first_name) }}" required>
        </div>
        <div class="mb-3">
            <label for="last_name" class="form-label">Last Name</label>
            <input type="text" name="last_name" id="last_name" class="form-control" value="{{ old('last_name', $student->last_name) }}" required>
        </div>
        <div id="mentors-container">
            <h5>Mentors</h5>
            @foreach ($student->mentors as $index => $mentor)
                <div class="mentor-input-group mb-2">
                    <div class="input-group">
                        <input type="text" name="mentors[{{ $index }}][first_name]" class="form-control" placeholder="Mentor First Name" value="{{ $mentor->first_name }}" required>
                        <input type="text" name="mentors[{{ $index }}][last_name]" class="form-control" placeholder="Mentor Last Name" value="{{ $mentor->last_name }}" required>
                        <button type="button" class="btn btn-danger" onclick="this.parentElement.parentElement.remove()">Remove</button>
                    </div>
                </div>
            @endforeach
        </div>
        <button type="button" class="btn btn-secondary mb-3" onclick="addMentor()">Add Mentor</button>
        <div class="mb-3">
            <button type="submit" class="btn btn-primary">Update Student</button>
            <a href="{{ route('students.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function addMentor() {
        const container = document.getElementById('mentors-container');
        const index = container.querySelectorAll('.mentor-input-group').length;
        const mentorHtml = `
               <div class="mentor-input-group mb-2">
                   <div class="input-group">
                       <input type="text" name="mentors[${index}][first_name]" class="form-control" placeholder="Mentor First Name" required>
                       <input type="text" name="mentors[${index}][last_name]" class="form-control" placeholder="Mentor Last Name" required>
                       <button type="button" class="btn btn-danger" onclick="this.parentElement.parentElement.remove()">Remove</button>
                   </div>
               </div>
           `;
        container.insertAdjacentHTML('beforeend', mentorHtml);
    }
</script>
</body>
</html>
