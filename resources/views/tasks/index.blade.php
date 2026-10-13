@extends('layouts.app')

@section('title', 'Task register')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">Operations / Task register</p>
            <h1>Task monitoring</h1>
        </div>
        <p class="heading-note">A shared view of current work and upcoming deadlines.</p>
    </div>

    <div class="content-grid">
        <section aria-labelledby="task-list-heading">
            <div class="section-heading">
                <h2 id="task-list-heading">Current tasks</h2>
                <span class="task-count">{{ count($tasks) }} {{ count($tasks) === 1 ? 'task' : 'tasks' }}</span>
            </div>
            <form action="{{ route('tasks.index') }}" method="GET">
                <div class="field">
                    <label for="q">Search title</label>
                    <input id="q" name="q" type="text" maxlength="180" value="{{ $q }}">
                </div>
                <button class="submit-button" type="submit">Filter</button>
            </form>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Task</th>
                            <th scope="col">Owner</th>
                            <th scope="col">Due date</th>
                            <th scope="col">Priority</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tasks as $task)
                            <tr>
                                <td class="task-title">{{ $task->title }}</td>
                                <td class="owner">{{ $task->owner }}</td>
                                <td class="date">{{ $task->due_date->format('M j, Y') }}</td>
                                <td><span class="tag priority-{{ strtolower($task->priority->value) }}">{{ $task->priority->value }}</span></td>
                                <td>
                                    <span class="tag status-{{ str_replace(' ', '-', strtolower($task->status->value)) }}">{{ $task->status->value }}</span>
                                    @if ($task->isOverdue()) <span class="tag late">Overdue</span> @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td class="empty-state" colspan="5">No tasks have been added.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (session('success'))
                <p class="success-message" role="status">{{ session('success') }}</p>
            @endif
        </section>

        <section class="form-panel" aria-labelledby="add-task-heading">
            <h2 id="add-task-heading">Add a task</h2>
            <p class="form-intro">Enter the assignment details below.</p>

            @if ($errors->any())
                <ul class="error-list" role="alert">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            <form action="{{ route('tasks.store') }}" method="POST">
                @csrf
                <div class="field">
                    <label for="title">Task title</label>
                    <input id="title" name="title" type="text" maxlength="180" value="{{ old('title') }}" required>
                </div>
                <div class="field">
                    <label for="owner">Owner</label>
                    <input id="owner" name="owner" type="text" maxlength="120" value="{{ old('owner') }}" required>
                </div>
                <div class="field">
                    <label for="due_date">Due date</label>
                    <input id="due_date" name="due_date" type="date" value="{{ old('due_date') }}" required>
                </div>
                <div class="field">
                    <label for="priority">Priority</label>
                    <select id="priority" name="priority" required>
                        <option value="">Select priority</option>
                        @foreach (['Low', 'Medium', 'High'] as $priority)
                            <option value="{{ $priority }}" @selected(old('priority') === $priority)>{{ $priority }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="">Select status</option>
                        @foreach (['Not started', 'In progress', 'Complete'] as $status)
                            <option value="{{ $status }}" @selected(old('status') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="submit-button" type="submit">Add task</button>
            </form>
        </section>
    </div>
@endsection