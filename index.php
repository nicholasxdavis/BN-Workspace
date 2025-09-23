<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workspace by Blacnova</title>
    <link rel="icon" href="https://www.blacnova.net/img/bn_orange.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #d4611c;
            --primary-light: #e67a35;
            --dark-bg: #101010;
            --card-bg: #1a1a1a;
            --surface: rgba(26, 26, 26, 0.8);
            --text-primary: #EEEEEE;
            --text-secondary: #a1a1aa;
        }

        /* Prevent horizontal scroll */
        html, body {
            overflow-x: hidden;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--dark-bg);
            color: var(--text-primary);
            background-image: radial-gradient(circle at 50% 0%, rgba(212, 97, 28, 0.1), transparent 40%);
        }

        #particle-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.7;
        }

        .header {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            background-color: rgba(16, 16, 16, 0.8);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .header.scrolled {
            background-color: rgba(16, 16, 16, 0.95);
        }

        .header-content, .logo {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #ffffff 0%, #e0e0e0 100%);
            color: #000;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 255, 255, 0.1);
        }
        
        .app-card {
            position: relative;
            background: linear-gradient(135deg, rgba(26,26,26,0.8) 0%, rgba(10,10,10,0.9) 100%);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 1.5rem;
            padding: 30px 25px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            height: 100%;
            display: flex;
            flex-direction: column;
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        
        .app-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(212, 97, 28, 0.2);
            border-color: rgba(212, 97, 28, 0.3);
        }

        .app-card.border-dashed:hover {
             background-color: rgba(212, 97, 28, 0.1);
             border-color: var(--primary);
        }
        
        .app-card .content {
            position: relative;
            z-index: 20;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .app-card .circle-before {
            position: absolute;
            top: 0;
            right: 0px;
            transform: translate(40%, -40%);
            width: 150px;
            height: 150px;
            background-color: #1a1a1a;
            border: 6px solid #1a1a1a;
            border-radius: 50%;
            opacity: 0.8;
            z-index: 10;
            transition: all .6s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        
        .app-card:hover .circle-before {
            width: 100%;
            height: 100%;
            transform: none;
            border: 0;
            border-radius: 0;
            opacity: 1;
            background-color: rgba(212, 97, 28, 0.05);
        }
        
        .app-card .app-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            width: 60px;
            height: 60px;
            background: #1a1a1a;
            border-radius: 12px;
            color: #ffffff;
            font-size: 28px;
            transition: all 0.3s;
        }
        
        .app-card:hover .app-icon {
            transform: rotate(5deg) scale(1.1);
            box-shadow: 0 8px 20px rgba(212, 97, 28, 0.4);
        }
        
        .modal-overlay {
            background-color: rgba(0,0,0,0.7);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .modal-overlay .app-card {
            overflow: visible;
        }
        
        .text-gradient {
            background: linear-gradient(to right, #ffffff, #e67a35);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-fill-color: transparent;
        }

        /* Custom Scrollbar for Modal */
        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }

    </style>
</head>
<body class="min-h-screen" x-data="{ 
    showAccountPage: false,
    activeTab: 'profile',
    showModal: false,
    showDeleteModal: false,
    appToDelete: null,
    allIntegrations: [
        { name: 'BN Website', provider: 'bn_website', icon: 'fas fa-globe', color: '#df722d', enabled: false },
        { name: 'GitHub', provider: 'github', icon: 'fab fa-github', color: '#ffffff', enabled: false },
        { name: 'Reddit', provider: 'reddit', icon: 'fab fa-reddit-alien', color: '#FF4500', enabled: true },
        { name: 'Discord', provider: 'discord', icon: 'fab fa-discord', color: '#5865F2', enabled: false },
        { name: 'LinkedIn', provider: 'linkedin', icon: 'fab fa-linkedin-in', color: '#0A66C2', enabled: false },
        { name: 'Facebook', provider: 'facebook', icon: 'fab fa-facebook', color: '#1877F2', enabled: false },
        { name: 'Instagram', provider: 'instagram', icon: 'fab fa-instagram', color: '#E4405F', enabled: false },
        { name: 'Gmail', provider: 'gmail', icon: 'fas fa-envelope', color: '#EA4335', enabled: false },
        { name: 'Google Docs', provider: 'google_docs', icon: 'fas fa-file-word', color: '#4285F4', enabled: false },
        { name: 'Google Sheets', provider: 'google_sheets', icon: 'fas fa-file-excel', color: '#34A853', enabled: false },
        { name: 'Google Forms', provider: 'google_forms', icon: 'fas fa-file-alt', color: '#7B1FA2', enabled: false },
        { name: 'Notion', provider: 'notion', img: 'https://upload.wikimedia.org/wikipedia/commons/thumb/e/e9/Notion-logo.svg/1200px-Notion-logo.svg.png', color: '#ffffff', enabled: false },
        { name: 'Google Calendar', provider: 'google_calendar', icon: 'fas fa-calendar-alt', color: '#0F9D58', enabled: false },
        { name: 'Dropbox', provider: 'dropbox', icon: 'fab fa-dropbox', color: '#0061FF', enabled: false },
        { name: 'Google Drive', provider: 'google_drive', icon: 'fab fa-google-drive', color: '#1DA462', enabled: false }
    ],
    connectedApps: [],
    toastMessage: '',
    toastType: 'success',
    toastVisible: false,

    init() {
        document.addEventListener('auth-success', () => {
            this.loadConnectedApps();
        });
        this.checkIntegrationStatus();
    },
    async loadConnectedApps() {
        try {
            const response = await fetch('./api/integrations/get_integrations.php');
            if (response.status === 401) {
                console.error('Not authorized to fetch integrations. User might be logged out.');
                return;
            }
            if (!response.ok) throw new Error('Network response was not ok');
            const data = await response.json();
            if (data.success) {
                this.connectedApps = data.integrations.map(integration => {
                    return this.allIntegrations.find(app => app.provider === integration.provider);
                }).filter(Boolean);
            }
        } catch (error) {
            console.error('Error loading integrations:', error);
            this.showToast('Could not load connected apps.', 'error');
        }
    },
    connectApp(app) {
        if (!app.enabled) {
            this.showToast(`${app.name} integration is not yet available.`, 'info');
            return;
        }
        if (this.connectedApps.some(a => a.provider === app.provider)) {
            this.showToast(`${app.name} is already connected.`, 'info');
            return;
        }
        window.location.href = `./api/integrations/connect.php?provider=${app.provider}`;
    },
    prepareToRemove(app) {
        this.appToDelete = app;
        this.showDeleteModal = true;
    },
    async confirmRemoveIntegration() {
        if (!this.appToDelete) return;
        try {
            const response = await fetch('./api/integrations/remove_integration.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ provider: this.appToDelete.provider })
            });
            const data = await response.json();
            if (data.success) {
                this.showToast(`${this.appToDelete.name} was removed.`);
                this.loadConnectedApps();
            } else {
                this.showToast(data.message || 'Failed to remove integration.', 'error');
            }
        } catch (error) {
            this.showToast('An error occurred while removing the integration.', 'error');
        } finally {
            this.showDeleteModal = false;
            this.appToDelete = null;
        }
    },
    showToast(message, type = 'success') {
        this.toastMessage = message;
        this.toastType = type;
        this.toastVisible = true;
        setTimeout(() => this.toastVisible = false, 4000);
    },
    checkIntegrationStatus() {
        const urlParams = new URLSearchParams(window.location.search);
        const successProvider = urlParams.get('integration_success');
        const error = urlParams.get('integration_error');
        if (successProvider) {
            const appName = this.allIntegrations.find(app => app.provider === successProvider)?.name || 'App';
            this.showToast(`${appName} connected successfully!`);
            window.history.replaceState({}, document.title, window.location.pathname);
        } else if (error) {
            let message = 'An unknown error occurred.';
            if (error === 'state_mismatch') message = 'Security token mismatch. Please try again.';
            if (error === 'access_denied') message = 'You denied the connection request.';
            if (error === 'token_exchange_failed') message = 'Could not verify connection with provider.';
            this.showToast(`Connection failed: ${message}`, 'error');
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    }
}" :class="{ 'bg-white bg-image-none': showAccountPage }">
    <canvas id="particle-bg" x-show="!showAccountPage"></canvas>

    <header class="header fixed top-0 left-0 w-full z-50" :style="showAccountPage ? { 'background-color': 'rgba(255, 255, 255, 0.95)', 'border-bottom': '1px solid #e5e7eb' } : {}">
        <div class="container mx-auto px-4">
            <div class="header-content flex justify-between items-center h-24">
                <div class="flex items-center">
                    <a href="https://www.blacnova.net/" class="flex items-center">
                        <img :src="showAccountPage ? 'https://www.blacnova.net/img/logo.png' : 'https://www.blacnova.net/img/logo_white.png'" alt="Blacnova Logo" class="logo h-20">
                    </a>
                </div>
                <div class="flex items-center">
                    <a href="#" @click.prevent="showAccountPage = true" class="btn-primary px-6 py-2 rounded-3xl font-medium" x-show="!showAccountPage">My Account</a>
                    <a href="#" @click.prevent="showAccountPage = false" class="px-6 py-2 rounded-3xl font-medium bg-gray-100 text-gray-800 hover:bg-gray-200 transition-colors" x-show="showAccountPage" style="display: none;">Back to Workspace</a>
                </div>
            </div>
        </div>
    </header>

    <main class="pt-32 relative z-10 pb-24" x-show="!showAccountPage">
        <div class="container mx-auto px-4">
            
            <section id="workspace">
                <div class="text-center mb-16">
                    <i class="fas fa-cubes-stacked text-6xl text-[#efad83] mb-4 mt-6"></i>
                    <h1 class="text-4xl md:text-5xl font-medium mb-4 text-gradient font-['Space_Grotesk']">Your Workspace</h1>
                    <p class="text-lg text-gray-300 max-w-2xl mx-auto">Connect your favorite apps and bring your workflow together in one place.</p>
                </div>

                <div x-data="{ open: true }">
                    <div @click="open = !open" class="flex justify-between items-center cursor-pointer mb-6 border-b border-white/10 pb-4">
                        <h2 class="text-2xl font-medium text-white">Your Integrations</h2>
                        <i class="fas fa-chevron-down text-gray-400 transition-transform" :class="{ 'rotate-180': !open }"></i>
                    </div>
                    <div x-show="open" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-4" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <template x-for="app in connectedApps" :key="app.name">
                            <div class="app-card">
                                <div class="content">
                                    <div class="flex items-start mb-4">
                                        <span class="app-icon !w-12 !h-12 !text-2xl !mb-0 mr-4 flex-shrink-0" style="background: #1a1a1a;">
                                            <template x-if="app.img">
                                                <img :src="app.img" :alt="app.name" class="w-6 h-6 object-contain">
                                            </template>
                                            <template x-if="app.icon">
                                                <i :class="app.icon" :style="{ color: app.color }"></i>
                                            </template>
                                        </span>
                                        <div>
                                            <h3 class="text-xl font-bold text-white" x-text="app.name"></h3>
                                            <p class="text-sm text-[var(--primary)] font-medium">Connected</p>
                                        </div>
                                    </div>
                                    <p class="text-gray-400 text-sm flex-grow">Your <span x-text="app.name"></span> integration is active and ready to use.</p>
                                    <div class="flex justify-end items-center mt-4">
                                        <button @click="prepareToRemove(app)" class="text-sm text-gray-400 hover:text-white transition-colors duration-200">
                                            <i class="fas fa-times-circle mr-1"></i> Remove
                                        </button>
                                    </div>
                                </div>
                                <div class="circle-before"></div>
                            </div>
                        </template>
                        <div @click="showModal = true" 
                              class="app-card border-dashed border-2 border-white/20 hover:border-primary/50 flex flex-col items-center justify-center text-center cursor-pointer">
                            <div class="content items-center">
                                <div class="app-icon border-2 border-dashed border-primary/50 bg-transparent mb-4">
                                    <i class="fas fa-plus text-primary"></i>
                                </div>
                                <h3 class="text-xl font-bold text-white mb-2">Add Integration</h3>
                                <p class="text-gray-400 text-sm flex-grow">Connect a new app to your workspace.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-16" x-data="{ open: true }">
                    <div @click="open = !open" class="flex justify-between items-center cursor-pointer mb-6 border-b border-white/10 pb-4">
                        <h2 class="text-2xl font-medium text-white">Our Tools</h2>
                        <i class="fas fa-chevron-down text-gray-400 transition-transform" :class="{ 'rotate-180': !open }"></i>
                    </div>
                    <div x-show="open" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-4" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                        <div class="app-card">
                            <div class="content">
                                <div class="flex items-start mb-4">
                                    <span class="app-icon !w-12 !h-12 !text-2xl !mb-0 mr-4 flex-shrink-0" style="background: #1a1a1a;">
                                        <i class="fas fa-sticky-note" style="color: var(--primary)"></i>
                                    </span>
                                    <div>
                                        <h3 class="text-xl font-bold text-white">Notes</h3>
                                        <p class="text-sm text-[var(--primary)] font-medium">Active</p>
                                    </div>
                                </div>
                                <p class="text-gray-400 text-sm flex-grow">Jot down ideas and keep track of your thoughts.</p>
                            </div>
                            <div class="circle-before"></div>
                        </div>

                        <div class="app-card">
                            <div class="content">
                                <div class="flex items-start mb-4">
                                    <span class="app-icon !w-12 !h-12 !text-2xl !mb-0 mr-4 flex-shrink-0" style="background: #1a1a1a;">
                                        <i class="fas fa-calendar-alt" style="color: var(--primary)"></i>
                                    </span>
                                    <div>
                                        <h3 class="text-xl font-bold text-white">Calendar</h3>
                                        <p class="text-sm text-[var(--primary)] font-medium">Active</p>
                                    </div>
                                </div>
                                <p class="text-gray-400 text-sm flex-grow">Manage your schedule and appointments seamlessly.</p>
                            </div>
                            <div class="circle-before"></div>
                        </div>

                        <div class="app-card">
                            <div class="content">
                                <div class="flex items-start mb-4">
                                    <span class="app-icon !w-12 !h-12 !text-2xl !mb-0 mr-4 flex-shrink-0" style="background: #1a1a1a;">
                                        <i class="fas fa-share-square" style="color: var(--primary)"></i>
                                    </span>
                                    <div>
                                        <h3 class="text-xl font-bold text-white leading-tight">All-At-Once Poster</h3>
                                        <p class="text-sm text-[var(--primary)] font-medium">Active</p>
                                    </div>
                                </div>
                                <p class="text-gray-400 text-sm flex-grow">Schedule and post content across multiple platforms at once.</p>
                            </div>
                            <div class="circle-before"></div>
                        </div>

                        <div class="app-card">
                            <div class="content">
                                <div class="flex items-start mb-4">
                                    <span class="app-icon !w-12 !h-12 !text-2xl !mb-0 mr-4 flex-shrink-0" style="background: #1a1a1a;">
                                        <i class="fas fa-chart-line" style="color: var(--primary)"></i>
                                    </span>
                                    <div>
                                        <h3 class="text-xl font-bold text-white">Real-Time Trends</h3>
                                        <p class="text-sm text-[var(--primary)] font-medium">Active</p>
                                    </div>
                                </div>
                                <p class="text-gray-400 text-sm flex-grow">Monitor and analyze real-time trends to stay ahead.</p>
                            </div>
                            <div class="circle-before"></div>
                        </div>
                        
                        <div class="app-card">
                            <div class="content">
                                <div class="flex items-start mb-4">
                                    <span class="app-icon !w-12 !h-12 !text-2xl !mb-0 mr-4 flex-shrink-0" style="background: #1a1a1a;">
                                        <i class="fas fa-file-invoice-dollar" style="color: var(--primary)"></i>
                                    </span>
                                    <div>
                                        <h3 class="text-xl font-bold text-white">Invoice Generation</h3>
                                        <p class="text-sm text-[var(--primary)] font-medium">Active</p>
                                    </div>
                                </div>
                                <p class="text-gray-400 text-sm flex-grow">Create and send professional invoices with ease.</p>
                            </div>
                            <div class="circle-before"></div>
                        </div>

                        <div class="app-card">
                            <div class="content">
                                <div class="flex items-start mb-4">
                                    <span class="app-icon !w-12 !h-12 !text-2xl !mb-0 mr-4 flex-shrink-0" style="background: #1a1a1a;">
                                        <i class="fas fa-chart-pie" style="color: var(--primary)"></i>
                                    </span>
                                    <div>
                                        <h3 class="text-xl font-bold text-white">Analytics Overview</h3>
                                        <p class="text-sm text-[var(--primary)] font-medium">Active</p>
                                    </div>
                                </div>
                                <p class="text-gray-400 text-sm flex-grow">View analytics from all connected apps that allow for analytics.</p>
                            </div>
                            <div class="circle-before"></div>
                        </div>

                        <div class="app-card">
                            <div class="content">
                                <div class="flex items-start mb-4">
                                    <span class="app-icon !w-12 !h-12 !text-2xl !mb-0 mr-4 flex-shrink-0" style="background: #1a1a1a;">
                                        <i class="fas fa-clock" style="color: var(--primary)"></i>
                                    </span>
                                    <div>
                                        <h3 class="text-xl font-bold text-white leading-tight">BN Cronos</h3>
                                        <p class="text-sm text-[var(--primary)] font-medium">Active</p>
                                    </div>
                                </div>
                                <p class="text-gray-400 text-sm flex-grow">Our cron time-based job scheduler for automations.</p>
                            </div>
                            <div class="circle-before"></div>
                        </div>

                        <div class="app-card">
                            <div class="content">
                                <div class="flex items-start mb-4">
                                    <span class="app-icon !w-12 !h-12 !text-2xl !mb-0 mr-4 flex-shrink-0" style="background: #1a1a1a;">
                                        <i class="fas fa-tasks" style="color: var(--primary)"></i>
                                    </span>
                                    <div>
                                        <h3 class="text-xl font-bold text-white">BN Tasks</h3>
                                        <p class="text-sm text-[var(--primary)] font-medium">Active</p>
                                    </div>
                                </div>
                                <p class="text-gray-400 text-sm flex-grow">A single hub to plan, track, manage, and optimize all work.</p>
                            </div>
                            <div class="circle-before"></div>
                        </div>

                    </div>
                </div>

            </section>
        </div>
    </main>
    
    <div x-show="showAccountPage" style="display: none;" class="pt-32 pb-16 text-gray-900">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row -mx-4">
                <aside class="w-full md:w-1/4 px-4 mb-8 md:mb-0">
                    <div class="sticky top-32 bg-white p-4 rounded-lg border border-gray-200">
                        <div class="p-2 mb-4 border-b border-gray-200">
                           <div class="flex items-center">
                               <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center text-xl font-bold text-gray-500 mr-4">
                                   <i class="fas fa-user"></i>
                               </div>
                               <div>
                                   <h4 id="desktop-user-name" class="font-bold text-gray-800">User</h4>
                                   <p id="desktop-user-email" class="text-sm text-gray-500">user@example.com</p>
                                   <span id="mobile-user-name" class="hidden"></span>
                                   <span id="mobile-user-email" class="hidden"></span>
                               </div>
                           </div>
                           <button id="logout-btn" class="w-full mt-4 text-left flex items-center px-3 py-2 text-sm rounded-md transition-colors text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                               <i class="fas fa-sign-out-alt w-5 mr-3 text-gray-500"></i>
                               <span>Logout</span>
                           </button>
                        </div>
                        <h3 class="font-bold text-lg mb-4 text-gray-800 px-2">Settings</h3>
                        <nav class="space-y-1">
                            <a href="#" @click.prevent="activeTab = 'profile'" class="flex items-center px-3 py-2 text-sm rounded-md transition-colors" :class="{ 'bg-gray-100 text-gray-900 font-semibold': activeTab === 'profile', 'text-gray-600 hover:bg-gray-50 hover:text-gray-900': activeTab !== 'profile' }">
                                <i class="fas fa-user-circle w-5 mr-3 text-gray-500"></i>
                                <span>Profile</span>
                            </a>
                            <a href="#" @click.prevent="activeTab = 'billing'" class="flex items-center px-3 py-2 text-sm rounded-md transition-colors" :class="{ 'bg-gray-100 text-gray-900 font-semibold': activeTab === 'billing', 'text-gray-600 hover:bg-gray-50 hover:text-gray-900': activeTab !== 'billing' }">
                                <i class="fas fa-credit-card w-5 mr-3 text-gray-500"></i>
                                <span>Billing</span>
                            </a>
                            <a href="#" @click.prevent="activeTab = 'security'" class="flex items-center px-3 py-2 text-sm rounded-md transition-colors" :class="{ 'bg-gray-100 text-gray-900 font-semibold': activeTab === 'security', 'text-gray-600 hover:bg-gray-50 hover:text-gray-900': activeTab !== 'security' }">
                                <i class="fas fa-shield-alt w-5 mr-3 text-gray-500"></i>
                                <span>Security</span>
                            </a>
                            <a href="#" @click.prevent="activeTab = 'integrations'" class="flex items-center px-3 py-2 text-sm rounded-md transition-colors" :class="{ 'bg-gray-100 text-gray-900 font-semibold': activeTab === 'integrations', 'text-gray-600 hover:bg-gray-50 hover:text-gray-900': activeTab !== 'integrations' }">
                                <i class="fas fa-cubes w-5 mr-3 text-gray-500"></i>
                                <span>Integrations</span>
                            </a>
                        </nav>
                    </div>
                </aside>

                <main class="w-full md:w-3/4 px-4">
                    <div class="bg-white p-6 md:p-8 rounded-lg border border-gray-200 min-h-[60vh]">
                        <div x-show="activeTab === 'profile'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                            <h1 class="text-2xl md:text-3xl font-bold mb-1 text-gray-800">Account Profile</h1>
                            <p class="text-gray-500 mb-6">Manage your public profile and account details.</p>
                            <div class="space-y-4">
                                <p>Content for profile settings goes here...</p>
                            </div>
                        </div>
                        <div x-show="activeTab === 'billing'" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                            <h1 class="text-2xl md:text-3xl font-bold mb-1 text-gray-800">Billing</h1>
                            <p class="text-gray-500 mb-6">Manage your subscription and view payment history.</p>
                            <div class="space-y-4">
                                <p>Content for billing settings goes here...</p>
                            </div>
                        </div>
                        <div x-show="activeTab === 'security'" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                            <h1 class="text-2xl md:text-3xl font-bold mb-1 text-gray-800">Security</h1>
                            <p class="text-gray-500 mb-6">Manage your password, two-factor authentication, and active sessions.</p>
                             <div class="space-y-4">
                                <p>Content for security settings goes here...</p>
                            </div>
                        </div>
                         <div x-show="activeTab === 'integrations'" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                            <h1 class="text-2xl md:text-3xl font-bold mb-1 text-gray-800">My Integrations</h1>
                            <p class="text-gray-500 mb-6">Manage your connected applications and services.</p>
                             <div class="space-y-4">
                                <p>Content for managing integrations goes here...</p>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>


    <footer class="bg-transparent border-t mt-16 py-8 text-center text-sm transition-colors duration-300" :class="{ 'text-gray-500 border-gray-200': showAccountPage, 'text-gray-400 border-white/10': !showAccountPage }">
        <a href="https://www.blacnova.net/" target="_blank" rel="noopener noreferrer">
            <img :src="showAccountPage ? 'https://www.blacnova.net/img/bn_black.png' : 'https://www.blacnova.net/img/bn.png'" alt="Blacnova Icon" class="h-12 mx-auto mb-4">
        </a>

        <p>&copy; 2025 Blacnova Development</p>
        <div class="mt-2 space-x-4">
            <a href="https://www.blacnova.net/legal#policy" class="transition-colors duration-200" :class="{ 'hover:text-black': showAccountPage, 'hover:text-white': !showAccountPage }">Privacy Policy</a>
            <span class="text-gray-500">|</span>
            <a href="https://www.blacnova.net/legal#terms" class="transition-colors duration-200" :class="{ 'hover:text-black': showAccountPage, 'hover:text-white': !showAccountPage }">Terms of Service</a>
        </div>
    </footer>

    <div x-show="showModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 modal-overlay"
         style="display: none;">
        <div @click.away="showModal = false"
             x-show="showModal"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative w-full max-w-3xl p-8 overflow-hidden rounded-3xl border border-white/10"
             style="background: var(--card-bg);">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-medium text-white font-['Space_Grotesk']">Connect a New App</h2>
                <button @click="showModal = false" class="text-gray-400 hover:text-white transition-colors text-2xl font-bold">&times;</button>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 max-h-[60vh] overflow-y-auto pr-2 pt-4 custom-scrollbar">
                <template x-for="app in allIntegrations" :key="app.provider">
                     <div @click="connectApp(app)" 
                         class="app-card !p-4 !rounded-xl items-center text-center" 
                         :class="app.enabled ? 'cursor-pointer' : 'cursor-not-allowed opacity-50'"
                         :title="app.enabled ? `Click to connect ${app.name}` : `${app.name} integration coming soon`">
                        <div class="content items-center">
                            <span class="app-icon !w-16 !h-16 !text-3xl" style="background: #1a1a1a;">
                                <template x-if="app.img"><img :src="app.img" :alt="app.name" class="w-9 h-9 object-contain"></template>
                                <template x-if="app.icon"><i :class="app.icon" :style="{ color: app.color }"></i></template>
                            </span>
                            <h3 class="text-md font-bold mt-2 text-white" x-text="app.name"></h3>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
    
    <div x-show="showDeleteModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 modal-overlay"
         style="display: none;">
        <div @click.away="showDeleteModal = false"
             x-show="showDeleteModal"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative w-full max-w-md p-8 overflow-hidden rounded-3xl bg-[var(--card-bg)] text-white shadow-2xl border border-white/10">
            
            <button @click="showDeleteModal = false" class="absolute top-4 right-4 text-gray-400 hover:text-white transition-colors text-2xl">&times;</button>

            <div>
                <h2 class="text-2xl text-white mb-2">Remove Integration</h2>
                <p class="text-gray-400 mt-4">Are you sure you want to remove the <strong x-text="appToDelete ? appToDelete.name : ''"></strong> integration? This action cannot be undone.</p>
                <div class="flex justify-end gap-4 mt-8">
                    <button @click="showDeleteModal = false" class="px-8 py-3 rounded-xl font-semibold bg-white/10 hover:bg-white/20 text-white transition-all duration-200">
                        Cancel
                    </button>
                    <button @click="confirmRemoveIntegration()" class="px-8 py-3 rounded-xl font-semibold bg-[var(--primary)] hover:bg-[var(--primary-light)] text-white transition-all duration-200 shadow-lg shadow-orange-500/30">
                        Yes, Remove
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="toastVisible"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform translate-y-2"
         style="display: none;"
         class="fixed bottom-5 right-5 md:bottom-10 md:right-10 z-[100] px-6 py-3 rounded-xl shadow-lg font-medium text-white"
         :class="{ 'bg-green-600': toastType === 'success', 'bg-red-600': toastType === 'error', 'bg-blue-600': toastType === 'info' }">
        <p x-text="toastMessage"></p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Particle Background
            const canvas = document.getElementById('particle-bg');
            if (canvas) { // Check if canvas element exists
                const ctx = canvas.getContext('2d');
                let particles = [];
                const particleCount = 70;

                function resizeCanvas() {
                    canvas.width = window.innerWidth;
                    canvas.height = window.innerHeight;
                }

                class Particle {
                    constructor() {
                        const colors = [`rgba(255, 255, 255, ${Math.random() * 0.2 + 0.1})`, `rgba(212, 97, 28, ${Math.random() * 0.3 + 0.1})`];
                        this.color = colors[Math.floor(Math.random() * colors.length)];
                        this.radius = Math.random() * 2 + 1;
                        this.reset();
                    }

                    reset() {
                        this.x = Math.random() * canvas.width;
                        this.y = Math.random() * canvas.height;
                        this.vx = (Math.random() - 0.5) * 0.4;
                        this.vy = (Math.random() - 0.5) * 0.4;
                    }

                    draw() {
                        ctx.beginPath();
                        ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
                        ctx.fillStyle = this.color;
                        ctx.fill();
                    }

                    update() {
                        this.x += this.vx;
                        this.y += this.vy;
                        if (this.x < 0 || this.x > canvas.width) this.vx *= -1;
                        if (this.y < 0 || this.y > canvas.height) this.vy *= -1;
                    }
                }

                function initParticles() {
                    particles = [];
                    for (let i = 0; i < particleCount; i++) {
                        particles.push(new Particle());
                    }
                }

                function animate() {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    for (const particle of particles) {
                        particle.update();
                        particle.draw();
                    }
                    requestAnimationFrame(animate);
                }
                
                window.addEventListener('resize', resizeCanvas);
                resizeCanvas();
                initParticles();
                animate();
            }

            // Header Scroll Effect
            const header = document.querySelector('.header');
            const headerContent = document.querySelector('.header-content');
            const logo = document.querySelector('.logo');
            
            const handleScroll = () => {
                const isScrolled = window.scrollY > 50;
                header.classList.toggle('scrolled', isScrolled);
                headerContent.classList.toggle('h-16', isScrolled);
                headerContent.classList.toggle('h-24', !isScrolled);
                logo.classList.toggle('h-12', isScrolled);
                logo.classList.toggle('h-20', !isScrolled);
            };

            window.addEventListener('scroll', handleScroll, { passive: true });
            handleScroll();

            // Auth Logic
            (function() {
                const authOverlay = document.createElement('div');
                authOverlay.id = 'auth-overlay';
                authOverlay.style.cssText = `position:fixed;top:0;left:0;width:100%;height:100%;background:#000;display:flex;justify-content:center;align-items:center;z-index:9999;font-family:'Poppins',sans-serif;transition:opacity .3s ease-in-out;`;
                function showLogin() {
                    document.documentElement.style.overflow = 'hidden';
                    document.body.style.overflow = 'hidden';
                    authOverlay.innerHTML = `<div style="background:#111;padding:2rem;border-radius:20px;width:90%;max-width:400px;box-shadow:0 10px 30px rgba(0,0,0,.5)"><div style="text-align:center;margin-bottom:2rem"><img src="https://www.blacnova.net/img/bn_orange.png" alt="Blacnova Logo" style="width:60px;height:60px;margin:0 auto 1rem"><h2 style="color:#fff;margin:0;font-weight:600">Workspace Access</h2><p style="color:#888;margin:.5rem 0 0">Sign in to your workspace</p></div><form id="login-form"><div style="margin-bottom:1.5rem"><label style="display:block;color:#ddd;margin-bottom:.5rem;font-size:.9rem">Email</label><input type="email" id="login-email-main" required style="width:100%;padding:12px 16px;border-radius:12px;border:1px solid #333;background:#222;color:#fff;font-size:1rem;box-sizing:border-box"></div><div style="margin-bottom:1rem"><label style="display:block;color:#ddd;margin-bottom:.5rem;font-size:.9rem">Password</label><input type="password" id="login-password-main" required style="width:100%;padding:12px 16px;border-radius:12px;border:1px solid #333;background:#222;color:#fff;font-size:1rem;box-sizing:border-box"></div><div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem"><label style="display:flex;align-items:center;color:#ddd;font-size:.9rem"><input type="checkbox" id="remember-me-main" style="margin-right:.5rem">Remember Me</label><a href="#" id="forgot-password-link-main" style="color:#d4611c;text-decoration:none;font-size:.9rem">Forgot password?</a></div><button type="submit" style="width:100%;padding:12px;border-radius:12px;border:none;background:#d4611c;color:#fff;font-weight:600;font-size:1rem;cursor:pointer;transition:background .2s">Sign In</button><p id="login-error" style="color:#ff4444;text-align:center;margin:1rem 0 0;display:none">Invalid credentials</p></form></div>`;
                    authOverlay.querySelector('#login-form').addEventListener('submit', handleLogin);
                    authOverlay.querySelector('#forgot-password-link-main').addEventListener('click', showForgotPassword);
                }
                function showForgotPassword(e) { e.preventDefault(); /* ... logic ... */ }
                function showResetConfirmation() { /* ... logic ... */ }
                async function handleLogin(e) {
                    e.preventDefault();
                    const email = document.getElementById('login-email-main').value;
                    const password = document.getElementById('login-password-main').value;
                    const rememberMe = document.getElementById('remember-me-main').checked;
                    const errorElement = document.getElementById('login-error');
                    try {
                        const response = await fetch('./api/auth/login.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ email, password }) });
                        const data = await response.json();
                        if (data.success) {
                            const storage = rememberMe ? localStorage : sessionStorage;
                            storage.setItem('blacnova_auth', 'authenticated');
                            storage.setItem('blacnova_user', JSON.stringify(data.user));
                            updateUserInfo(data.user);
                            document.dispatchEvent(new CustomEvent('auth-success'));
                            authOverlay.style.opacity = '0';
                            setTimeout(() => {
                                authOverlay.style.display = 'none';
                                document.documentElement.style.overflow = '';
                                document.body.style.overflow = '';
                            }, 300);
                        } else {
                            errorElement.textContent = data.message || 'Invalid credentials';
                            errorElement.style.display = 'block';
                        }
                    } catch (error) {
                        errorElement.textContent = 'Connection error. Please try again.';
                        errorElement.style.display = 'block';
                    }
                }
                async function handlePasswordReset(e) { /* ... logic ... */ }
                function updateUserInfo(user) {
                    const userNameElements = [document.getElementById('desktop-user-name'), document.getElementById('mobile-user-name')];
                    const userEmailElements = [document.getElementById('desktop-user-email'), document.getElementById('mobile-user-email')];
                    userNameElements.forEach(el => { if(el) el.textContent = user.full_name || 'User'; });
                    userEmailElements.forEach(el => { if(el) el.textContent = user.email; });
                }
                function handleLogout() {
                    fetch('./api/auth/logout.php').finally(() => {
                        localStorage.clear();
                        sessionStorage.clear();
                        window.location.reload();
                    });
                }
                const logoutBtn = document.getElementById('logout-btn');
                if(logoutBtn) logoutBtn.addEventListener('click', handleLogout);
                const user = JSON.parse(localStorage.getItem('blacnova_user') || sessionStorage.getItem('blacnova_user'));
                const isAuthenticated = localStorage.getItem('blacnova_auth') === 'authenticated' || sessionStorage.getItem('blacnova_auth') === 'authenticated';
                document.body.appendChild(authOverlay);
                if (isAuthenticated && user) {
                    updateUserInfo(user);
                    authOverlay.style.display = 'none';
                    document.documentElement.style.overflow = '';
                    document.body.style.overflow = '';
                    document.dispatchEvent(new CustomEvent('auth-success'));
                } else {
                    showLogin();
                }
            })();
        });
    </script>

</body>
</html>
