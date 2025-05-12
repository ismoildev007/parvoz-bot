<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Student</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">Create New Student</h1>
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form action="{{ route('students.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label for="first_name" class="form-label">First Name</label>
            <input type="text" name="first_name" id="first_name" class="form-control" value="{{ old('first_name') }}" required>
        </div>
        <div class="mb-3">
            <label for="last_name" class="form-label">Last Name</label>
            <input type="text" name="last_name" id="last_name" class="form-control" value="{{ old('last_name') }}" required>
        </div>
        <div id="mentors-container">
            <h5>Mentors</h5>
        </div>
        <button type="button" class="btn btn-secondary mb-3" onclick="addMentor()">Add Mentor</button>
        <div class="mb-3">
            <button type="submit" class="btn btn-primary">Create Student</button>
            <a href="{{ route('students.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function addMentor() {
        const container = document.getElementById('mentors-container');
        const index = container.querySelectorAll('.mentor-select').length;
        const mentorHtml = `
                <div class="input-group mb-2 mentor-select">
                    <select name="mentors[]" class="form-select">
                        <option value="">Select a mentor</option>
                        @foreach ($mentors as $mentor)
        <option value="{{ $mentor->id }}">{{ $mentor->first_name }} {{ $mentor->last_name }}</option>
                        @endforeach
        </select>
        <button type="button" class="btn btn-danger" onclick="this.parentElement.remove()">Remove</button>
    </div>
`;
        container.insertAdjacentHTML('beforeend', mentorHtml);
    }
</script>
</body>
</html>
