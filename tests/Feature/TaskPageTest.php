<?php

namespace Tests\Feature;

use Tests\TestCase;

class TaskPageTest extends TestCase
{
    public function test_task_page_shows_sample_tasks_and_a_create_form(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Task monitoring');
        $response->assertSee('Review quarterly service report');
        $response->assertSee('name="_token"', false);
        $response->assertDontSee('Let\'s get started');
    }

    public function test_submitting_a_task_adds_it_to_the_session_task_list(): void
    {
        $title = 'Quarterly <script>alert(1)</script>';

        $response = $this->post('/tasks', [
            'title' => $title,
            'owner' => 'Jordan Lee',
            'due_date' => '2026-11-05',
            'priority' => 'High',
            'status' => 'In progress',
        ]);

        $response->assertRedirect('/');
        $this->get('/')->assertSee($title)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_all_task_fields_are_validated(): void
    {
        $response = $this->from('/')->post('/tasks', [
            'title' => '',
            'owner' => '',
            'due_date' => 'not-a-date',
            'priority' => 'Urgent',
            'status' => 'Blocked',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors(['title', 'owner', 'due_date', 'priority', 'status']);
    }
}
