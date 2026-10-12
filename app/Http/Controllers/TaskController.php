<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $sampleTasks = [
            [
                'title' => 'Review quarterly service report',
                'owner' => 'Amina Rahman',
                'due_date' => '2026-10-16',
                'priority' => 'High',
                'status' => 'In progress',
            ],
            [
                'title' => 'Update regional contact directory',
                'owner' => 'Daniel Lim',
                'due_date' => '2026-10-19',
                'priority' => 'Medium',
                'status' => 'Not started',
            ],
            [
                'title' => 'Prepare procurement briefing',
                'owner' => 'Farah Ismail',
                'due_date' => '2026-10-22',
                'priority' => 'High',
                'status' => 'Not started',
            ],
            [
                'title' => 'Archive completed case files',
                'owner' => 'Marcus Tan',
                'due_date' => '2026-10-28',
                'priority' => 'Low',
                'status' => 'Complete',
            ],
        ];

        $tasks = array_merge($sampleTasks, $request->session()->get('tasks.created', []));

        return view('tasks.index', compact('tasks'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'owner' => ['required', 'string', 'max:120'],
            'due_date' => ['required', 'date_format:Y-m-d'],
            'priority' => ['required', 'in:Low,Medium,High'],
            'status' => ['required', 'in:Not started,In progress,Complete'],
        ]);

        $tasks = $request->session()->get('tasks.created', []);
        $tasks[] = $validated;
        $request->session()->put('tasks.created', $tasks);

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Task added to this session.');
    }
}
