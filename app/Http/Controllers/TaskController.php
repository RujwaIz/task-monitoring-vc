<?php

namespace App\Http\Controllers;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:180'],
        ]);

        $q = $validated['q'] ?? null;

        $status = $this->enumFromQuery($request, 'status', TaskStatus::class);
        $priority = $this->enumFromQuery($request, 'priority', TaskPriority::class);

        $overdue = $request->query('overdue') === '1';

        $tasks = $request->user()->tasks()
            ->when(filled($q), fn ($query) => $query->whereRaw("title like ? escape '\\'", ['%'.addcslashes($q, '%_\\').'%']))
            ->when($status, fn ($query, $status) => $query->where('status', $status))
            ->when($priority, fn ($query, $priority) => $query->where('priority', $priority))
            ->when($overdue, fn ($query) => $query->overdue())
            ->orderBy('due_date')->orderBy('id')->get();

        $filtered = filled($q) || $status || $priority || $overdue;

        return view('tasks.index', compact('tasks', 'q', 'status', 'priority', 'overdue', 'filtered'));
    }

    /** An unknown or malformed query value is ignored (null). */
    private function enumFromQuery(Request $request, string $key, string $enum): ?\BackedEnum
    {
        $value = $request->query($key);

        return is_string($value) ? $enum::tryFrom($value) : null;
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'owner' => ['required', 'string', 'max:120'],
            'due_date' => ['required', 'date_format:Y-m-d'],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'status' => ['required', Rule::enum(TaskStatus::class)],
        ]);

        $request->user()->tasks()->create($validated);

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Task added.');
    }
}
