<!DOCTYPE html>
<html lang="sr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Mini Order Admin')</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: system-ui, sans-serif;
            margin: 0;
            background: #f4f5f7;
            color: #1a1a1a;
        }

        .container {
            max-width: 960px;
            margin: 0 auto;
            padding: 24px;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 16px;
        }

        a {
            color: #2563eb;
            text-decoration: none;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
        }

        th,
        td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        th {
            background: #f9fafb;
        }

        .btn {
            display: inline-block;
            padding: 8px 14px;
            background: #2563eb;
            color: #fff;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-danger {
            background: #dc2626;
        }

        .btn-secondary {
            background: #6b7280;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }

        label {
            display: block;
            margin: 12px 0 4px;
            font-weight: 500;
            font-size: 14px;
        }

        .error {
            color: #dc2626;
            font-size: 13px;
            margin-top: 4px;
        }

        .alert {
            background: #dcfce7;
            color: #166534;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 16px;
        }

        .card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        /* pagination */
        .pagination {
            display: flex;
            gap: 6px;
            list-style: none;
            padding: 0;
            margin: 16px 0;
            flex-wrap: wrap;
        }

        .pagination li {
            list-style: none;
        }

        .pagination a,
        .pagination span {
            display: inline-block;
            padding: 6px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: #fff;
            color: #2563eb;
            font-size: 14px;
            text-decoration: none;
        }

        .pagination .active span {
            background: #2563eb;
            color: #fff;
            border-color: #2563eb;
        }

        .pagination .disabled span {
            color: #9ca3af;
        }
    </style>
    @livewireStyles
</head>

<body>
    <div class="container">
        @if(session('success'))
            <div class="alert">{{ session('success') }}</div>
        @endif
        @yield('content')
    </div>
    @livewireScripts
</body>

</html>