<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_page_shows_the_users_tasks_and_a_create_form(): void
    {
        $user = User::factory()->create();
        Task::factory()->create(['user_id' => $user->id, 'title' => 'Review quarterly service report']);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertSee('Task monitoring');
        $response->assertSee('Review quarterly service report');
        $response->assertSee('name="_token"', false);
    }

    public function test_task_list_shows_only_the_signed_in_users_tasks(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        Task::factory()->create(['user_id' => $me->id, 'title' => 'My own task']);
        Task::factory()->create(['user_id' => $other->id, 'title' => 'Somebody else task']);

        $this->actingAs($me)->get('/')->assertSee('My own task')->assertDontSee('Somebody else task');
    }

    public function test_submitting_a_task_saves_it_for_the_signed_in_user_and_escapes_output(): void
    {
        $user = User::factory()->create();
        $title = 'Quarterly <script>alert(1)</script>';

        $this->actingAs($user)->post('/tasks', [
            'title' => $title,
            'owner' => 'Jordan Lee',
            'due_date' => '2026-11-05',
            'priority' => 'High',
            'status' => 'In progress',
        ])->assertRedirect('/');

        $this->assertDatabaseHas('tasks', ['user_id' => $user->id, 'title' => $title]);
        $this->actingAs($user)->get('/')->assertSee($title)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_all_task_fields_are_validated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from('/')->post('/tasks', [
            'title' => '',
            'owner' => '',
            'due_date' => 'not-a-date',
            'priority' => 'Urgent',
            'status' => 'Blocked',
        ])->assertRedirect('/')
            ->assertSessionHasErrors(['title', 'owner', 'due_date', 'priority', 'status']);

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_overdue_rule_ignores_complete_tasks(): void
    {
        $user = User::factory()->create();
        $late = Task::factory()->create(['user_id' => $user->id, 'due_date' => today()->subDays(2)->toDateString(), 'status' => 'In progress']);
        $done = Task::factory()->create(['user_id' => $user->id, 'due_date' => today()->subDays(2)->toDateString(), 'status' => 'Complete']);
        $future = Task::factory()->create(['user_id' => $user->id, 'due_date' => today()->addDays(2)->toDateString(), 'status' => 'Not started']);

        $this->assertTrue($late->isOverdue());
        $this->assertFalse($done->isOverdue());
        $this->assertFalse($future->isOverdue());
    }
}
