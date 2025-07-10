<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Estimasi Pengeringan Gabah</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Gaya dasar untuk Fluent-like */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f3f4f6;
        }
        .fluent-card {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.08);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        .fluent-button {
            background-color: #0078D4;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            transition: background-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            box-shadow: 0 2px 4px rgba(0, 120, 212, 0.2);
        }
        .fluent-button:hover {
            background-color: #0063B1;
            box-shadow: 0 4px 8px rgba(0, 120, 212, 0.3);
        }
        .fluent-input {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 12px;
            transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .fluent-input:focus {
            outline: none;
            border-color: #0078D4;
            box-shadow: 0 0 0 3px rgba(0, 120, 212, 0.2);
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
        <h2 class="text-3xl font-semibold text-center text-gray-800">Daftar Akun Baru</h2>
        <p class="text-center text-gray-600">Estimasi Durasi Pengeringan Gabah</p>

        @if ($errors->any())
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register.post') }}" class="space-y-4">
            @csrf

            <div>
                <label for="nama" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                <input type="text" id="nama" name="nama" value="{{ old('nama') }}" required autofocus
                       class="mt-1 block w-full fluent-input @error('nama') border-red-500 @enderror">
                @error('nama')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                <input type="text" id="username" name="username" value="{{ old('username') }}" required
                       class="mt-1 block w-full fluent-input @error('username') border-red-500 @enderror">
                @error('username')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required
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

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Konfirmasi Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required
                       class="mt-1 block w-full fluent-input @error('password_confirmation') border-red-500 @enderror">
                @error('password_confirmation')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="role" class="block text-sm font-medium text-gray-700">Daftar Sebagai</label>
                <select id="role" name="role"
                        class="mt-1 block w-full fluent-input @error('role') border-red-500 @enderror">
                    <option value="petani" {{ old('role') == 'petani' ? 'selected' : '' }}>Petani</option>
                    <option value="operator" {{ old('role') == 'operator' ? 'selected' : '' }}>Operator</option>
                    {{-- Role admin sebaiknya hanya bisa diatur secara manual oleh admin yang sudah ada --}}
                    {{-- <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option> --}}
                </select>
                @error('role')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white fluent-button">
                    Daftar
                </button>
            </div>
        </form>

        <div class="text-center text-sm text-gray-600">
            Sudah punya akun? <a href="{{ route('login') }}" class="link-fluent font-medium hover:underline">Masuk</a>
        </div>
    </div>
</body>
</html>