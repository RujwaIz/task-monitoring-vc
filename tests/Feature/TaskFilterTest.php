<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_q_filters_by_title_and_never_shows_another_users_tasks(): void
    {
        $amina = User::factory()->create();
        $daniel = User::factory()->create();
        Task::factory()->create(['user_id' => $amina->id, 'title' => 'Budget review']);
        Task::factory()->create(['user_id' => $amina->id, 'title' => 'Site visit']);
        Task::factory()->create(['user_id' => $daniel->id, 'title' => 'Budget']);

        $response = $this->actingAs($amina)->get('/?q=Budget');

        $response->assertOk()->assertSee('Budget review')->assertDontSee('Site visit');
        $this->assertCount(1, $response->viewData('tasks'));
        $this->assertSame($amina->id, $response->viewData('tasks')->first()->user_id);
    }

    public function test_q_treats_like_wildcards_literally(): void
    {
        $user = User::factory()->create();
        Task::factory()->create(['user_id' => $user->id, 'title' => '100% done']);
        Task::factory()->create(['user_id' => $user->id, 'title' => 'Something else']);

        $response = $this->actingAs($user)->get('/?q='.urlencode('%'));

        $this->assertCount(1, $response->viewData('tasks'));
    }

    public function test_q_is_escaped_in_the_page_and_kept_in_the_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/?q='.urlencode('<script>alert(1)</script>'))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_status_filter_shows_only_matching_tasks_of_the_signed_in_user(): void
    {
        $amina = User::factory()->create();
        $daniel = User::factory()->create();
        Task::factory()->count(3)->create(['user_id' => $amina->id, 'status' => 'Not started']);
        Task::factory()->count(2)->create(['user_id' => $amina->id, 'status' => 'Complete']);
        Task::factory()->count(4)->create(['user_id' => $daniel->id, 'status' => 'Complete']);

        $tasks = $this->actingAs($amina)->get('/?status=Complete')->assertOk()->viewData('tasks');

        $this->assertCount(2, $tasks);
        $this->assertTrue($tasks->every(fn ($t) => $t->user_id === $amina->id && $t->status->value === 'Complete'));
    }

    public function test_unknown_or_malformed_status_is_ignored(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(3)->create(['user_id' => $user->id]);

        foreach (['/?status=Blocked', '/?status[]=Complete', '/?status='] as $url) {
            $this->actingAs($user)->get($url)->assertOk();
            $this->assertCount(3, $this->actingAs($user)->get($url)->viewData('tasks'));
        }
    }

    public function test_status_is_kept_in_the_form_and_combines_with_q(): void
    {
        $user = User::factory()->create();
        Task::factory()->create(['user_id' => $user->id, 'title' => 'Budget one', 'status' => 'Complete']);
        Task::factory()->create(['user_id' => $user->id, 'title' => 'Budget two', 'status' => 'Not started']);
        Task::factory()->create(['user_id' => $user->id, 'title' => 'Other', 'status' => 'Complete']);

        $response = $this->actingAs($user)->get('/?status=Complete&q=Budget');

        $response->assertSee('Budget one')->assertDontSee('Budget two')->assertDontSee('Other');
        $response->assertSee('<option value="Complete" selected>Complete</option>', false);
    }

    public function test_priority_filter_shows_only_matching_tasks_of_the_signed_in_user(): void
    {
        $amina = User::factory()->create();
        $daniel = User::factory()->create();
        Task::factory()->count(2)->create(['user_id' => $amina->id, 'priority' => 'High']);
        Task::factory()->count(3)->create(['user_id' => $amina->id, 'priority' => 'Low']);
        Task::factory()->count(4)->create(['user_id' => $daniel->id, 'priority' => 'High']);

        $response = $this->actingAs($amina)->get('/?priority=High')->assertOk();

        $tasks = $response->viewData('tasks');
        $this->assertCount(2, $tasks);
        $this->assertTrue($tasks->every(fn ($t) => $t->user_id === $amina->id && $t->priority->value === 'High'));
        $response->assertSee('<option value="High" selected>High</option>', false);
    }

    public function test_unknown_or_malformed_priority_is_ignored(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(3)->create(['user_id' => $user->id]);

        foreach (['/?priority=Urgent', '/?priority[]=High', '/?priority='] as $url) {
            $this->assertCount(3, $this->actingAs($user)->get($url)->assertOk()->viewData('tasks'));
        }
    }

    public function test_priority_and_status_combine(): void
    {
        $user = User::factory()->create();
        Task::factory()->create(['user_id' => $user->id, 'title' => 'Match', 'priority' => 'High', 'status' => 'Complete']);
        Task::factory()->create(['user_id' => $user->id, 'title' => 'Wrong status', 'priority' => 'High', 'status' => 'Not started']);
        Task::factory()->create(['user_id' => $user->id, 'title' => 'Wrong priority', 'priority' => 'Low', 'status' => 'Complete']);

        $this->actingAs($user)->get('/?priority=High&status=Complete')
            ->assertSee('Match')->assertDontSee('Wrong status')->assertDontSee('Wrong priority');
    }

    public function test_overdue_filter_excludes_complete_tasks(): void
    {
        $user = User::factory()->create();
        $yesterday = today()->subDay()->toDateString();
        Task::factory()->create(['user_id' => $user->id, 'title' => 'Late open', 'due_date' => $yesterday, 'status' => 'In progress']);
        Task::factory()->create(['user_id' => $user->id, 'title' => 'Late not started', 'due_date' => today()->subDays(5)->toDateString(), 'status' => 'Not started']);
        Task::factory()->create(['user_id' => $user->id, 'title' => 'Late but done', 'due_date' => $yesterday, 'status' => 'Complete']);

        $tasks = $this->actingAs($user)->get('/?overdue=1')->assertOk()->viewData('tasks');

        $this->assertEqualsCanonicalizing(['Late open', 'Late not started'], $tasks->pluck('title')->all());
    }

    public function test_a_task_due_today_is_not_overdue(): void
    {
        $user = User::factory()->create();
        Task::factory()->create(['user_id' => $user->id, 'title' => 'Due today', 'due_date' => today()->toDateString(), 'status' => 'Not started']);
        Task::factory()->create(['user_id' => $user->id, 'title' => 'Due tomorrow', 'due_date' => today()->addDay()->toDateString(), 'status' => 'Not started']);

        $this->assertCount(0, $this->actingAs($user)->get('/?overdue=1')->assertOk()->viewData('tasks'));
    }

    public function test_overdue_filter_agrees_with_task_is_overdue(): void
    {
        $user = User::factory()->create();
        $dates = [today()->subDays(3), today()->subDay(), today(), today()->addDay()];
        foreach ($dates as $date) {
            foreach (['Not started', 'In progress', 'Complete'] as $status) {
                Task::factory()->create(['user_id' => $user->id, 'due_date' => $date->toDateString(), 'status' => $status]);
            }
        }

        $shown = $this->actingAs($user)->get('/?overdue=1')->assertOk()->viewData('tasks')->pluck('id')->all();
        $expected = $user->tasks()->get()->filter(fn (Task $task) => $task->isOverdue())->pluck('id')->all();

        $this->assertNotEmpty($expected);
        $this->assertEqualsCanonicalizing($expected, $shown);
    }

    public function test_overdue_is_only_applied_for_the_value_1(): void
    {
        $user = User::factory()->create();
        Task::factory()->create(['user_id' => $user->id, 'due_date' => today()->subDay()->toDateString(), 'status' => 'Not started']);
        Task::factory()->create(['user_id' => $user->id, 'due_date' => today()->addDay()->toDateString(), 'status' => 'Not started']);

        foreach (['/?overdue=0', '/?overdue=abc', '/?overdue[]=1', '/?overdue='] as $url) {
            $this->assertCount(2, $this->actingAs($user)->get($url)->assertOk()->viewData('tasks'));
        }
    }

    public function test_no_filter_ever_shows_another_users_tasks(): void
    {
        $amina = User::factory()->create();
        $daniel = User::factory()->create();
        $yesterday = today()->subDay()->toDateString();
        Task::factory()->create(['user_id' => $amina->id, 'title' => 'Budget mine', 'due_date' => $yesterday, 'status' => 'Not started', 'priority' => 'High']);
        Task::factory()->create(['user_id' => $daniel->id, 'title' => 'Budget theirs', 'due_date' => $yesterday, 'status' => 'Not started', 'priority' => 'High']);

        foreach ([
            '/?q=Budget',
            '/?status=Not+started',
            '/?priority=High',
            '/?overdue=1',
            '/?q=Budget&status=Not+started&priority=High&overdue=1',
            '/?status=Blocked',
            '/?priority=Urgent',
        ] as $url) {
            $tasks = $this->actingAs($amina)->get($url)->assertOk()->viewData('tasks');

            $this->assertCount(1, $tasks, $url);
            $this->assertSame($amina->id, $tasks->first()->user_id, $url);
        }
    }

    public function test_array_values_do_not_break_the_page(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(2)->create(['user_id' => $user->id]);

        foreach (['status', 'priority', 'overdue'] as $key) {
            $this->assertCount(2, $this->actingAs($user)->get("/?{$key}[]=x&{$key}[a][b]=y")->assertOk()->viewData('tasks'));
        }

        $this->actingAs($user)->get('/?q[a][b]=x')->assertStatus(302);
    }

    public function test_the_form_keeps_the_chosen_values(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/?q='.urlencode('Budget "Q4"').'&status=In+progress&priority=Medium&overdue=1')->assertOk();

        $response->assertSee('value="Budget &quot;Q4&quot;"', false);
        $response->assertSee('<option value="In progress" selected>In progress</option>', false);
        $response->assertSee('<option value="Medium" selected>Medium</option>', false);
        $response->assertSeeInOrder(['name="overdue"', 'checked'], false);
    }

    public function test_q_is_validated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from('/')->get('/?q='.str_repeat('a', 181))
            ->assertRedirect('/')->assertSessionHasErrors('q');
        $this->actingAs($user)->from('/')->get('/?q[]=x')
            ->assertRedirect('/')->assertSessionHasErrors('q');
    }
}
