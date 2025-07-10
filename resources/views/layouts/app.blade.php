<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f3f4f6; }
        .fluent-card { background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.08); }
        .fluent-button { background-color: #0078D4; color: white; padding: 8px 16px; border-radius: 6px; font-weight: 600; transition: background-color 0.2s; }
        .fluent-button:hover { background-color: #0063B1; }
        .fluent-input { border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 12px; transition: border-color 0.2s, box-shadow 0.2s; }
        .fluent-input:focus { outline: none; border-color: #0078D4; box-shadow: 0 0 0 3px rgba(0, 120, 212, 0.2); }
        .alert-success { background-color: #D4EDDA; border-color: #28A745; color: #155724; padding: 12px; border-radius: 6px; border-left: 4px solid; margin-bottom: 1rem;}
        .alert-error { background-color: #F8D7DA; border-color: #DC3545; color: #721C24; padding: 12px; border-radius: 6px; border-left: 4px solid; margin-bottom: 1rem;}
    </style>
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow p-4">
        <div class="container mx-auto flex justify-between items-center">
            <a href="{{ url('/') }}" class="text-xl font-bold text-gray-800">Aplikasi Gabah</a>
            <div>
                @auth
                    <span class="mr-4 text-gray-700">Halo, {{ Auth::user()->nama }} ({{ Auth::user()->role }})</span>
                    <a href="{{ route('grain-types.index') }}" class="fluent-button mr-2">Jenis Gabah</a>
                    <a href="{{ route('drying-processes.index') }}" class="fluent-button mr-2">Proses Pengeringan</a>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="fluent-button bg-red-600 hover:bg-red-700">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="fluent-button">Login</a>
                    <a href="{{ route('register') }}" class="fluent-button ml-2">Register</a>
                @endauth
            </div>
        </div>
    </nav>

    <div class="container mx-auto p-6 mt-8">
        @if (session('success'))
            <div class="alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert-error">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert-error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </div>
</body>
</html>