@php
    use Illuminate\Support\Facades\Auth;
@endphp
@if(auth()->user())
<nav x-data="{ open: false, notificationsOpen: false, notificationsCount: 0 }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="logo-container">
                        <img src="{{ asset('assets/images/arkan.png') }}" alt="Arkan Logo" style="height: 60px; width: auto;" class="arkan-logo" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <!-- Teams Dropdown -->
                @if (Laravel\Jetstream\Jetstream::hasTeamFeatures())
                <div class="ms-3 relative">
                    <x-dropdown align="right" width="60">
                        <x-slot name="trigger">
                            <span class="inline-flex rounded-md">
                                <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none focus:bg-gray-50 active:bg-gray-50 transition ease-in-out duration-150">
                                    {{ Auth::user()->currentTeam ? Auth::user()->currentTeam->name : __('No Team') }}

                                    <svg class="ms-2 -me-0.5 size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                    </svg>
                                </button>
                            </span>
                        </x-slot>

                        <x-slot name="content">
                            <div class="w-60">
                                <!-- Team Management -->
                                <div class="block px-4 py-2 text-xs text-gray-400">
                                    {{ __('Manage Team') }}
                                </div>

                                <!-- Team Settings -->
                                @if(Auth::user()->currentTeam)
                                <x-dropdown-link href="{{ route('teams.show', Auth::user()->currentTeam->id) }}">
                                    {{ __('Team Settings') }}
                                </x-dropdown-link>
                                @endif

                                @if(Auth::user()->hasRole(['team_leader', 'company_manager', 'hr', 'department_manager' , 'project_manager']) )
                                <x-dropdown-link href="{{ route('teams.create') }}">
                                    {{ __('Create New Team') }}
                                </x-dropdown-link>
                                @endif

                                <!-- Team Switcher -->
                                @if (Auth::user()->allTeams()->count() > 0)
                                <div class="border-t border-gray-200"></div>

                                <div class="block px-4 py-2 text-xs text-gray-400">
                                    {{ __('Switch Teams') }}
                                </div>

                                @foreach (Auth::user()->allTeams() as $team)
                                <x-switchable-team :team="$team" />
                                @endforeach
                                @endif
                            </div>
                        </x-slot>
                    </x-dropdown>
                </div>
                @endif

                <!-- Notifications Dropdown -->
                <div class="ms-3 relative" x-data="notificationsComponent()">
                    <x-dropdown align="right" width="80">
                        <x-slot name="trigger">
                            <button @click="markNotificationsAsOpened" class="inline-flex items-center p-2 border border-transparent rounded-full text-gray-500 bg-white hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition" style="position: relative;">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0h-6" />
                                </svg>
                                <span x-show="unreadCount > 0" x-text="unreadCount" class="absolute -top-1 -right-1 flex justify-center items-center rounded-full bg-red-500 text-white text-xs min-w-[18px] h-[18px] px-1">0</span>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="w-80 max-h-96 overflow-y-auto bg-white shadow-lg border border-gray-200" style="opacity: 1 !important;">
                                <div class="flex justify-between items-center px-4 py-2 text-xs text-gray-600 bg-gray-50 border-b border-gray-200 sticky top-0">
                                    <span class="font-semibold">{{ __('الإشعارات') }}</span>
                                    <button x-show="unreadCount > 0" @click="markAllAsRead" class="text-blue-500 hover:text-blue-700 text-xs">
                                        {{ __('تحديد الكل كمقروء') }}
                                    </button>
                                </div>

                                <div x-show="loading" class="text-center py-4">
                                    <svg class="animate-spin h-6 w-6 mx-auto text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>

                                <div x-show="!loading && notifications.length === 0" class="text-center py-8 px-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mx-auto text-gray-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0h-6" />
                                    </svg>
                                    <p class="text-gray-400">{{ __('لا توجد إشعارات') }}</p>
                                </div>

                                <template x-for="notification in notifications" :key="notification.id">
                                    <div @click="markAsRead(notification.id)" class="block px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition cursor-pointer" :class="{'bg-blue-50': !notification.read_at}">
                                        <div class="text-sm text-gray-800 font-medium" x-text="notification.data.message"></div>
                                        <div class="flex justify-between items-center mt-1">
                                            <div class="text-xs text-gray-500" x-text="formatDate(notification.created_at)"></div>
                                            <div x-show="!notification.read_at" class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                        </div>
                                    </div>
                                </template>

                                <div x-show="notifications.length > 0" class="block px-4 py-2 text-center border-t border-gray-100 bg-gray-50 sticky bottom-0">
                                    <a href="{{ route('notifications') }}" class="text-sm text-blue-500 hover:text-blue-700 font-medium">
                                        {{ __('عرض كل الإشعارات') }}
                                    </a>
                                </div>
                            </div>
                        </x-slot>
                    </x-dropdown>
                </div>

                <!-- Settings Dropdown -->
                <div class="ms-3 relative">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                            <button class="flex text-sm border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300 transition">
                                <img class="size-8 rounded-full object-cover" src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" />
                            </button>
                            @else
                            <span class="inline-flex rounded-md">
                                <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none focus:bg-gray-50 active:bg-gray-50 transition ease-in-out duration-150">
                                    {{ Auth::user()->name }}

                                    <svg class="ms-2 -me-0.5 size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </button>
                            </span>
                            @endif
                        </x-slot>

                        <x-slot name="content">
                            <!-- Account Management -->
                            <div class="block px-4 py-2 text-xs text-gray-400">
                                {{ __('Manage Account') }}
                            </div>

                            <x-dropdown-link href="{{ route('profile.show') }}">
                                {{ __('Profile') }}
                            </x-dropdown-link>

                            @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                            <x-dropdown-link href="{{ route('api-tokens.index') }}">
                                {{ __('API Tokens') }}
                            </x-dropdown-link>
                            @endif

                            <div class="border-t border-gray-200"></div>

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}" x-data>
                                @csrf

                                <x-dropdown-link href="{{ route('logout') }}"
                                    @click.prevent="$root.submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="size-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Notifications -->
        <div class="pt-2 pb-3 space-y-1 border-t border-gray-200 bg-white">
            <x-responsive-nav-link href="{{ route('notifications') }}" :active="request()->routeIs('notifications')">
                <div class="flex justify-between items-center">
                    <span>{{ __('الإشعارات') }}</span>
                    <span x-data="{ count: 0 }" x-init="
                        fetch('/notifications/unread-count', {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => {
                            if (!response.ok) throw new Error('Network response was not ok');
                            return response.json();
                        })
                        .then(data => { count = data.count })
                        .catch(error => { console.error(error); count = 0; })
                    " x-show="count > 0" x-text="count" class="flex justify-center items-center rounded-full bg-red-500 text-white text-xs min-w-[20px] h-[20px] px-1"></span>
                </div>
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="flex items-center px-4">
                @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                <div class="shrink-0 me-3">
                    <img class="size-10 rounded-full object-cover" src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" />
                </div>
                @endif

                <div>
                    <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <!-- Account Management -->
                <x-responsive-nav-link href="{{ route('profile.show') }}" :active="request()->routeIs('profile.show')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                <x-responsive-nav-link href="{{ route('api-tokens.index') }}" :active="request()->routeIs('api-tokens.index')">
                    {{ __('API Tokens') }}
                </x-responsive-nav-link>
                @endif

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}" x-data>
                    @csrf

                    <x-responsive-nav-link href="{{ route('logout') }}"
                        @click.prevent="$root.submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>

                <!-- Team Management -->
                @if (Laravel\Jetstream\Jetstream::hasTeamFeatures())
                <div class="border-t border-gray-200"></div>

                <div class="block px-4 py-2 text-xs text-gray-400">
                    {{ __('Manage Team') }}
                </div>

                <!-- Team Settings -->
                @if(Auth::user()->currentTeam)
                <x-responsive-nav-link href="{{ route('teams.show', Auth::user()->currentTeam->id) }}" :active="request()->routeIs('teams.show')">
                    {{ __('Team Settings') }}
                </x-responsive-nav-link>
                @endif

                @if(Auth::user()->hasRole(['team_leader', 'company_manager', 'hr', 'department_manager' , 'project_manager']) )
                <x-responsive-nav-link href="{{ route('teams.create') }}" :active="request()->routeIs('teams.create')">
                    {{ __('Create New Team') }}
                </x-responsive-nav-link>
                @endif

                <!-- Team Switcher -->
                @if (Auth::user()->allTeams()->count() > 1)
                <div class="border-t border-gray-200"></div>

                <div class="block px-4 py-2 text-xs text-gray-400">
                    {{ __('Switch Teams') }}
                </div>

                @foreach (Auth::user()->allTeams() as $team)
                <x-switchable-team :team="$team" component="responsive-nav-link" />
                @endforeach
                @endif
                @endif
            </div>
        </div>
    </div>
</nav>

@push('scripts')
<script>
    function notificationsComponent() {
        return {
            notifications: [],
            unreadCount: 0,
            loading: true,
            init() {
                if (document.querySelector('.user-logged-in')) {
                    this.fetchNotifications();
                    this.fetchUnreadCount();

                    // Refresh unread count every minute
                    setInterval(() => {
                        this.fetchUnreadCount();
                    }, 60000);
                }
            },
            fetchNotifications() {
                this.loading = true;
                fetch('/notifications')
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');

                        // Extract notifications data from HTML
                        const notificationsData = Array.from(doc.querySelectorAll('.notification-item')).map(item => {
                            return {
                                id: item.dataset.id,
                                read_at: item.dataset.readAt ? new Date(item.dataset.readAt) : null,
                                created_at: new Date(item.dataset.createdAt),
                                data: {
                                    message: item.querySelector('.notification-message').textContent.trim()
                                }
                            };
                        });

                        this.notifications = notificationsData;
                        this.loading = false;
                    })
                    .catch(error => {
                        console.error('Error fetching notifications:', error);
                        this.loading = false;
                    });
            },
            fetchUnreadCount() {
                fetch('/notifications/unread-count', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    this.unreadCount = data.count;
                })
                .catch(error => {
                    console.error('Error fetching unread count:', error);
                    // Set to 0 on error to avoid showing NaN or undefined
                    this.unreadCount = 0;
                });
            },
            markAsRead(notificationId) {
                fetch(`/notifications/${notificationId}/mark-as-read`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update notification in the list
                            this.notifications = this.notifications.map(notification => {
                                if (notification.id === notificationId && !notification.read_at) {
                                    notification.read_at = new Date();
                                    this.unreadCount = Math.max(0, this.unreadCount - 1);
                                }
                                return notification;
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error marking notification as read:', error);
                    });
            },
            markAllAsRead() {
                const unreadIds = this.notifications
                    .filter(n => !n.read_at)
                    .map(n => n.id);

                if (unreadIds.length === 0) return;

                Promise.all(unreadIds.map(id =>
                    fetch(`/notifications/${id}/mark-as-read`)
                        .then(response => response.json())
                ))
                .then(() => {
                    // Update all notifications as read
                    this.notifications = this.notifications.map(notification => {
                        if (!notification.read_at) {
                            notification.read_at = new Date();
                        }
                        return notification;
                    });

                    // Reset unread count
                    this.unreadCount = 0;
                })
                .catch(error => {
                    console.error('Error marking all notifications as read:', error);
                });
            },
            markNotificationsAsOpened() {
                // We only update the frontend state here
                // The actual reading happens when user clicks on a specific notification
                this.fetchNotifications();
            },
            formatDate(date) {
                if (!(date instanceof Date)) {
                    date = new Date(date);
                }

                // Check if the date is today
                const today = new Date();
                if (date.toDateString() === today.toDateString()) {
                    const hours = date.getHours().toString().padStart(2, '0');
                    const minutes = date.getMinutes().toString().padStart(2, '0');
                    return `اليوم ${hours}:${minutes}`;
                }

                // Check if the date is yesterday
                const yesterday = new Date();
                yesterday.setDate(yesterday.getDate() - 1);
                if (date.toDateString() === yesterday.toDateString()) {
                    const hours = date.getHours().toString().padStart(2, '0');
                    const minutes = date.getMinutes().toString().padStart(2, '0');
                    return `أمس ${hours}:${minutes}`;
                }

                // Otherwise return the full date
                const day = date.getDate().toString().padStart(2, '0');
                const month = (date.getMonth() + 1).toString().padStart(2, '0');
                const year = date.getFullYear();
                const hours = date.getHours().toString().padStart(2, '0');
                const minutes = date.getMinutes().toString().padStart(2, '0');

                return `${day}/${month}/${year} ${hours}:${minutes}`;
            }
        };
    }
</script>
<style>
    .dropdown-panel, [x-dropdown], [x-dropdown] > div {
        opacity: 1 !important;
        background-color: white !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
    }
</style>
@endpush
@endif
