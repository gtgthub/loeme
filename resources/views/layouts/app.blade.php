<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Loeme Trading') }} - @yield('title', 'Dashboard')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    <!-- Styles and Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        @include('partials.sidebar')

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Navbar -->
            @include('partials.navbar')

            <!-- Page Content -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 dark:bg-gray-900 p-6" data-vue-app>
                @if (session('success'))
                    <div class="mb-4">
                        <error-message
                            message="{{ session('success') }}"
                            type="success"
                            :auto-dismiss="5000"
                        ></error-message>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4">
                        <error-message
                            message="{{ session('error') }}"
                            type="danger"
                            :auto-dismiss="5000"
                        ></error-message>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
