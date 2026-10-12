<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>@yield('title', 'Task Monitor') | {{ config('app.name', 'Task Monitor') }}</title>
    <style>
        :root {
            color-scheme: light;
            --ink: #1e302b;
            --muted: #687771;
            --line: #dce4df;
            --paper: #f4f6f3;
            --white: #ffffff;
            --forest: #214c3d;
            --forest-dark: #17382d;
            --lime: #d7e8aa;
            --amber: #f4dfaa;
            --rose: #f3d3cb;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--paper);
            color: var(--ink);
            font-family: "Segoe UI", Tahoma, sans-serif;
            font-size: 15px;
            line-height: 1.5;
        }

        .topbar {
            background: var(--forest-dark);
            color: #f6f8f4;
            border-bottom: 4px solid #c3d89a;
        }

        .topbar-inner, .page-shell {
            width: min(1180px, calc(100% - 48px));
            margin: 0 auto;
        }

        .topbar-inner {
            min-height: 68px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .brand-mark {
            width: 28px;
            height: 28px;
            border: 1px solid #d7e8aa;
            display: grid;
            place-items: center;
            color: #d7e8aa;
            font-family: Georgia, serif;
            font-size: 17px;
        }

        .topbar-label {
            color: #c9d5cf;
            font-size: 13px;
        }

        .logout-form { display: inline; margin-left: 12px; }
        .logout-button { background: none; border: 1px solid #c3d89a; color: #f6f8f4; padding: 4px 10px; font: inherit; font-size: 12px; cursor: pointer; }
        .logout-button:hover { background: #214c3d; }

        .page-shell { padding: 38px 0 56px; }

        .page-heading {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 24px;
            padding-bottom: 26px;
            border-bottom: 1px solid var(--line);
        }

        .eyebrow {
            margin: 0 0 5px;
            color: var(--forest);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        h1, h2, p { margin-top: 0; }

        h1 {
            margin-bottom: 0;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 34px;
            font-weight: 500;
            line-height: 1.15;
        }

        .heading-note {
            margin: 0 0 2px;
            color: var(--muted);
            font-size: 14px;
        }

        .content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 320px;
            align-items: start;
            gap: 28px;
            padding-top: 26px;
        }

        .content-grid > section { min-width: 0; }

        .section-heading {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 13px;
        }

        h2 {
            margin-bottom: 0;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 21px;
            font-weight: 500;
        }

        .task-count {
            color: var(--muted);
            font-size: 13px;
            font-variant-numeric: tabular-nums;
        }

        .table-wrap {
            overflow-x: auto;
            background: var(--white);
            border: 1px solid var(--line);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            padding: 12px 14px;
            background: #eaf0eb;
            color: #53625b;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .07em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        td {
            padding: 14px;
            border-top: 1px solid #e8ede9;
            vertical-align: middle;
            font-size: 14px;
        }

        tbody tr:hover { background: #fbfcfa; }
        .task-title { min-width: 200px; font-weight: 600; }
        .owner { min-width: 125px; color: #53625b; }
        .date { min-width: 110px; white-space: nowrap; font-variant-numeric: tabular-nums; }

        .tag {
            display: inline-flex;
            align-items: center;
            min-height: 25px;
            padding: 3px 8px;
            border: 1px solid transparent;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .tag.late { background: #f8e4df; border-color: #f0c9bd; color: #813d2c; margin-left: 6px; }
        .priority-high { background: #f8e4df; border-color: #f0c9bd; color: #813d2c; }
        .priority-medium { background: #f7edcf; border-color: #eadba9; color: #705719; }
        .priority-low { background: #e5eedb; border-color: #cfdfbf; color: #3b5a31; }
        .status-not-started { background: #edf0ee; border-color: #dce2de; color: #596760; }
        .status-in-progress { background: #e2edf2; border-color: #c9dce4; color: #31586b; }
        .status-complete { background: #e5eedb; border-color: #cfdfbf; color: #3b5a31; }

        .form-panel {
            padding: 19px;
            background: #e9efea;
            border-top: 3px solid var(--forest);
        }

        .form-panel h2 { margin-bottom: 4px; }
        .form-intro { margin-bottom: 17px; color: var(--muted); font-size: 13px; }
        .field { margin-bottom: 13px; }

        label {
            display: block;
            margin-bottom: 5px;
            color: #34453d;
            font-size: 12px;
            font-weight: 700;
        }

        input, select {
            display: block;
            width: 100%;
            height: 40px;
            padding: 8px 10px;
            border: 1px solid #c7d2ca;
            border-radius: 2px;
            background: var(--white);
            color: var(--ink);
            font: inherit;
            font-size: 14px;
        }

        input:focus, select:focus {
            outline: 2px solid #638d76;
            outline-offset: 1px;
            border-color: var(--forest);
        }

        .error-list {
            margin: 0 0 15px;
            padding: 10px 12px 10px 28px;
            border-left: 3px solid #a9432d;
            background: #fff2ef;
            color: #7a3021;
            font-size: 13px;
        }

        .success-message {
            margin: 18px 0 0;
            padding: 10px 12px;
            border-left: 3px solid #638d43;
            background: #edf4e7;
            color: #35562a;
            font-size: 14px;
        }

        .submit-button {
            width: 100%;
            min-height: 42px;
            margin-top: 3px;
            border: 0;
            border-radius: 2px;
            background: var(--forest);
            color: white;
            font: inherit;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .submit-button:hover { background: #2d634f; }
        .submit-button:focus-visible { outline: 3px solid #9ab77e; outline-offset: 2px; }

        .empty-state {
            padding: 28px 16px;
            color: var(--muted);
            text-align: center;
        }

        @media (max-width: 860px) {
            .content-grid { grid-template-columns: 1fr; }
            .form-panel { max-width: none; }
        }

        @media (max-width: 540px) {
            .topbar-inner, .page-shell { width: min(100% - 30px, 1180px); }
            .topbar-inner { min-height: 60px; }
            .topbar-label { font-size: 12px; text-align: right; }
            .page-shell { padding-top: 27px; }
            .page-heading { display: block; }
            h1 { font-size: 30px; }
            .heading-note { margin-top: 9px; }
            .content-grid { gap: 24px; padding-top: 21px; }
            .table-wrap { margin-right: -1px; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <div class="brand"><span class="brand-mark" aria-hidden="true">T</span> Task Monitor</div>
            <div class="topbar-label">
                @auth
                    {{ auth()->user()->name }}
                    <form action="{{ route('logout') }}" method="POST" class="logout-form">
                        @csrf
                        <button type="submit" class="logout-button">Sign out</button>
                    </form>
                @else
                    Internal operations
                @endauth
            </div>
        </div>
    </header>
    <main class="page-shell">
        @yield('content')
    </main>
</body>
</html>