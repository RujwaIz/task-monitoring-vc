# Plan: Task filters

Branch: `demo-filters`. Each step: tests green, `vendor/bin/pint --dirty`, one commit. No push.

## Spec
- Optional query parameters on the task list page: `status`, `priority`, `overdue` (1), `q` (text in the title).
- Filters can be combined (AND). Only the signed-in user's own tasks are ever shown.
- Overdue = not Complete and the due date is before today (a task due today is not overdue).
- An unknown status or priority is ignored. The form keeps the chosen values.
- The count says "(filtered)"; no match says "No tasks match these filters."
- Out of scope: sorting, pagination, saved filters, new packages, database changes.

## Files
Change: `app/Http/Controllers/TaskController.php`, `app/Models/Task.php` (`scopeOverdue`), `resources/views/tasks/index.blade.php`, `tests/Feature/TaskPageTest.php` (new tests only; or a new `TaskFilterTest.php`), `Plan.md`. `resources/css/app.css` only if the existing classes do not fit.

Do not touch: `.env`, `AGENTS.md`, `CLAUDE.md`, `AGENTS2.md`, `docs/`, `routes/web.php`, migrations, seeders, factories, enums, `SessionController`, existing tests, `composer.*`, `package*.json`.

## Steps
0. Commit this plan.
1. `q`: validate `['nullable','string','max:180']`; `->when(...)` LIKE on title with `%` and `_` escaped; text input in a GET form that keeps its value.
2. `status`: `TaskStatus::tryFrom()`, ignore when null; select built from `TaskStatus::cases()`.
3. `priority`: same with `TaskPriority`.
4. `overdue`: `Task::scopeOverdue()` mirroring `isOverdue()`; applied only when `overdue === '1'`; checkbox.
5. Combined filters, "(filtered)" count, "No tasks match these filters." empty state, "Clear filters" link.

## Existing code followed
- Query chain `$request->user()->tasks()->orderBy('due_date')->orderBy('id')`; only `->when(...)` is appended, never `Task::`.
- Validation style and enums as in `TaskController::store()`.
- `Task::isOverdue()` for the overdue meaning.
- View classes `.field`, `@selected`, `{{ }}`, `route()`.
- Tests follow `TaskPageTest`: `RefreshDatabase`, factories, `actingAs(...)->get(...)`.

## Rules and tests
- Own tasks only: two-user test for every filter and for all combined (Daniel's "Budget" never shows for Amina on `/?q=Budget`).
- Complete is never overdue: Complete task due yesterday is absent from `/?overdue=1`; In progress task due yesterday is present.
- Due today is not overdue.
- Enums only: `/?status=Complete` shows only Amina's 2 Complete tasks (of 3 Not started + 2 Complete); `?status=Blocked` and `?priority=Urgent` are ignored and not "(filtered)".
- Output escaped: `?q=<script>alert(1)</script>` is not echoed raw.
- Validation: `q` over 180 characters and `q[]=x` are tested.
- Also tested: AND combination, the form keeps values, "(filtered)" and the no-match message.
- Policy rule: not applicable (read-only).

## Out of scope
Sorting, pagination, saved filters, new packages, database changes; also edit/delete, owner and date-range filters, JavaScript, new CSS framework, changes to the add-task form, `git push`, `migrate:fresh --seed`.

## Open questions
1. Invalid `q` (array or over 180 characters): validation error (default) or ignore?
2. `overdue`: only `1` activates it; other values ignored (default).
3. `LIKE` is case-insensitive on SQLite; acceptable?
