<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Two fake training users (password: "password"), each with their own tasks.
     * Dates are relative to today, so there is always something overdue and something upcoming.
     */
    public function run(): void
    {
        $amina = User::factory()->create(['name' => 'Amina Rahman', 'email' => 'amina@example.test']);
        $daniel = User::factory()->create(['name' => 'Daniel Lim', 'email' => 'daniel@example.test']);

        $rows = [
            [$amina, 'Review quarterly service report', 'Amina Rahman', 4, 'High', 'In progress'],
            [$amina, 'Update regional contact directory', 'Amina Rahman', 7, 'Medium', 'Not started'],
            [$amina, 'Prepare procurement briefing', 'Farah Ismail', 10, 'High', 'Not started'],
            [$amina, 'Archive completed case files', 'Marcus Tan', -3, 'Low', 'Complete'],
            [$amina, 'Reply to enquiry backlog', 'Amina Rahman', -2, 'High', 'In progress'],
            [$amina, 'Book venue for training day', 'Farah Ismail', 14, 'Low', 'Not started'],
            [$daniel, 'Draft vendor payment schedule', 'Daniel Lim', 3, 'High', 'In progress'],
            [$daniel, 'Check network switch inventory', 'Daniel Lim', -5, 'Medium', 'Not started'],
            [$daniel, 'Send monthly attendance summary', 'Marcus Tan', 6, 'Low', 'Not started'],
            [$daniel, 'Close audit follow-up items', 'Daniel Lim', -1, 'High', 'Complete'],
        ];

        foreach ($rows as [$user, $title, $owner, $days, $priority, $status]) {
            Task::create([
                'user_id' => $user->id,
                'title' => $title,
                'owner' => $owner,
                'due_date' => today()->addDays($days)->toDateString(),
                'priority' => $priority,
                'status' => $status,
            ]);
        }
    }
}
