<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>School Manager Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100">
    <div id="app" class="min-h-screen flex flex-col">
        <!-- Navbar -->
        <nav class="bg-white border-b border-gray-200 fixed z-30 w-full">
            <div class="px-3 py-3 lg:px-5 lg:pl-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center justify-start">
                        <a href="#" class="text-xl font-bold flex items-center lg:ml-2.5">
                            <img src="https://flowbite.com/docs/images/logo.svg" class="h-6 mr-3" alt="FlowBite Logo" />
                            <span class="self-center whitespace-nowrap">School Manager</span>
                        </a>
                    </div>
                    <div class="flex items-center">
                        <!-- User dropdown (placeholder) -->
                        <button type="button" class="flex text-sm bg-gray-800 rounded-full focus:ring-4 focus:ring-gray-300" aria-expanded="false" data-dropdown-toggle="dropdown-user">
                            <span class="sr-only">Open user menu</span>
                            <img class="w-8 h-8 rounded-full" src="https://flowbite.com/docs/images/people/profile-picture-5.jpg" alt="user photo">
                        </button>
                        <!-- Dropdown menu (placeholder) -->
                    </div>
                </div>
            </div>
        </nav>

        <div class="flex overflow-hidden bg-gray-100 pt-16">
            <!-- Sidebar -->
            <aside id="sidebar" class="fixed top-0 left-0 z-20 flex flex-col flex-shrink-0 pt-16 h-full duration-75 lg:flex transition-width" aria-label="Sidebar">
                <div class="relative flex-1 flex flex-col min-h-0 border-r border-gray-200 bg-white pt-0">
                    <div class="flex-1 flex flex-col pt-3 pb-4 overflow-y-auto">
                        <div class="flex-1 px-3 bg-white divide-y space-y-1">
                            <ul class="space-y-2 pb-2">
                                <li>
                                    <router-link to="/dashboard" class="text-base font-normal rounded-lg flex items-center p-2 text-gray-900 hover:bg-gray-100 group">
                                        <svg class="w-6 h-6 text-gray-500 group-hover:text-gray-900 transition duration-75" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M2 10a8 8 0 018-8v8h8a8 8 0 01-8 8v-8H2z"></path><path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"></path></svg>
                                        <span class="ml-3">Dashboard</span>
                                    </router-link>
                                </li>
                                <li>
                                    <router-link to="/customers" class="text-base font-normal rounded-lg flex items-center p-2 text-gray-900 hover:bg-gray-100 group">
                                        <svg class="flex-shrink-0 w-6 h-6 text-gray-500 group-hover:text-gray-900 transition duration-75" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM11 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2h-2zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2h-2z"></path></svg>
                                        <span class="ml-3">Customers</span>
                                    </router-link>
                                </li>
                                <!-- Add more sidebar items here -->
                            </ul>
                        </div>
                    </div>
                </div>
            </aside>

            <div id="main-content" class="h-full w-full bg-gray-50 relative overflow-y-auto lg:ml-64">
                <main>
                    <router-view></router-view>
                </main>
                <p class="text-center text-sm text-gray-500 my-4">© 2025 School Manager. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
