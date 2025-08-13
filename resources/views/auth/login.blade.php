<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Estimasi Pengeringan Gabah</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Gaya dasar untuk Fluent-like */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f3f4f6; /* Warna abu-abu terang */
        }
        .fluent-card {
            background-color: #ffffff;
            border-radius: 12px; /* Rounded corners */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.08); /* Soft shadow */
            backdrop-filter: blur(10px); /* Efek blur ringan */
            border: 1px solid rgba(0, 0, 0, 0.05); /* Border sangat tipis */
        }
        .fluent-button {
            background-color: #0078D4; /* Warna biru Fluent */
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            transition: background-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            box-shadow: 0 2px 4px rgba(0, 120, 212, 0.2); /* Shadow biru */
        }
        .fluent-button:hover {
            background-color: #0063B1; /* Sedikit lebih gelap saat hover */
            box-shadow: 0 4px 8px rgba(0, 120, 212, 0.3);
        }
        .fluent-input {
            border: 1px solid #e2e8f0; /* Border abu-abu */
            border-radius: 6px;
            padding: 10px 12px;
            transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .fluent-input:focus {
            outline: none;
            border-color: #0078D4; /* Border biru saat focus */
            box-shadow: 0 0 0 3px rgba(0, 120, 212, 0.2); /* Ring biru saat focus */
        }
        .link-fluent {
            color: #0078D4;
            transition: color 0.2s ease-in-out;
        }
        .link-fluent:hover {
            color: #0063B1;
            text-decoration: underline;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="w-full max-w-md p-8 space-y-6 fluent-card">
        <h2 class="text-3xl font-semibold text-center text-gray-800">Masuk</h2>
        <p class="text-center text-gray-600">Aplikasi Estimasi Durasi Pengeringan Gabah</p>

        @if (session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded" role="alert">
                <p>{{ session('success') }}</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="text" id="email" name="email" value="{{ old('email') }}" required autofocus
                       class="mt-1 block w-full fluent-input @error('email') border-red-500 @enderror">
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" id="password" name="password" required
                       class="mt-1 block w-full fluent-input @error('password') border-red-500 @enderror">
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input id="remember_me" name="remember" type="checkbox"
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="remember_me" class="ml-2 block text-sm text-gray-900">
                        Ingat Saya
                    </label>
                </div>
                {{-- <a href="#" class="text-sm link-fluent hover:underline">Lupa Password?</a> --}}
            </div>

            <div>
                <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white fluent-button">
                    Masuk
                </button>
            </div>
        </form>

        <div class="text-center text-sm text-gray-600">
            Belum punya akun? <a href="{{ route('register') }}" class="link-fluent font-medium hover:underline">Daftar sekarang</a>
        </div>
    </div>
</body>
</html>