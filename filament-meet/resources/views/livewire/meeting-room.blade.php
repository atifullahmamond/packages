<div
    class="flex flex-col h-full"
    wire:poll.5s="refreshMeetingWhenWaiting"
    x-data="meetingRoom(@js([
        'roomId'      => $roomId,
        'domain'      => $jitsiDomain,
        'displayName' => $displayName,
        'email'       => $userEmail,
        'jwt'         => $jwtToken,
        'isHost'      => $isHost,
        'meetingUuid' => $meeting->uuid,
    ]))"
    x-init="init()"
    x-cloak
    @meeting-ended.window="onMeetingEnded()"
    @meeting-started.window="onMeetingStartedFromLivewire()"
>
    {{-- ================================================================== --}}
    {{-- TOP BAR                                                             --}}
    {{-- ================================================================== --}}
    <header class="flex items-center justify-between px-4 py-2 bg-zinc-900/95 backdrop-blur border-b border-zinc-800 shrink-0 z-20">
        <div class="flex items-center gap-3 min-w-0">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 10l4.553-2.069A1 1 0 0121 8.882v6.236a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                <span class="font-semibold text-white truncate max-w-[200px] sm:max-w-xs">
                    {{ $meeting->title }}
                </span>
            </div>

            @if($meeting->isActive())
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 text-xs font-medium border border-emerald-500/30">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    LIVE
                </span>
            @elseif($meeting->isScheduled())
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-sky-500/20 text-sky-400 text-xs font-medium border border-sky-500/30">
                    Scheduled
                </span>
            @endif
        </div>

        <div class="flex items-center gap-3">
            {{-- Participant count --}}
            <div class="flex items-center gap-1.5 text-zinc-400 text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span wire:poll.5s="refreshParticipants">{{ count($activeParticipants) }}</span>
            </div>

            {{-- Sidebar toggle --}}
            <button
                @click="sidebarOpen = !sidebarOpen"
                class="p-1.5 rounded-lg text-zinc-400 hover:text-white hover:bg-zinc-700 transition-colors"
                title="Toggle sidebar"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>
    </header>

    {{-- ================================================================== --}}
    {{-- MAIN CONTENT AREA                                                   --}}
    {{-- ================================================================== --}}
    <div class="flex flex-1 overflow-hidden relative">

        {{-- JITSI: only the iframe mount is wire:ignore so overlays & Livewire can still update --}}
        <div class="flex-1 relative bg-zinc-950 overflow-hidden">

            <div class="absolute inset-0 z-0" wire:ignore>
                <div id="jitsi-container" class="w-full h-full"></div>
            </div>

            {{-- Waiting / loading state --}}
            <div
                x-show="!jitsiReady && !isMeetingEnded"
                x-transition:leave="transition-opacity duration-500"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="pointer-events-auto absolute inset-0 flex flex-col items-center justify-center gap-6 bg-zinc-950 z-10"
            >
                <div class="relative">
                    <div class="w-20 h-20 rounded-2xl bg-violet-600/20 border border-violet-500/30 flex items-center justify-center">
                        <svg class="w-10 h-10 text-violet-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M15 10l4.553-2.069A1 1 0 0121 8.882v6.236a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
                <div class="text-center">
                    <h3 class="text-lg font-semibold text-white">Preparing your room…</h3>
                    <p class="text-sm text-zinc-400 mt-1">Loading Jitsi Meet</p>
                </div>

                @if($isHost && $meeting->isScheduled())
                    <button
                        wire:click="startMeeting"
                        class="px-6 py-2.5 bg-violet-600 hover:bg-violet-500 text-white font-medium rounded-xl transition-colors flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Start Meeting
                    </button>
                @elseif(!$isHost && $meeting->isScheduled())
                    <p class="text-sm text-zinc-500">Waiting for the host to start the meeting…</p>
                @endif
            </div>

            {{-- Meeting ended overlay --}}
            <div
                x-show="isMeetingEnded"
                x-transition
                class="absolute inset-0 flex flex-col items-center justify-center gap-4 bg-zinc-950/95 z-20"
            >
                <div class="w-16 h-16 rounded-2xl bg-zinc-800 flex items-center justify-center">
                    <svg class="w-8 h-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="text-center">
                    <h3 class="text-lg font-semibold text-white">Meeting Ended</h3>
                    <p class="text-sm text-zinc-400 mt-1">This meeting has concluded.</p>
                </div>
                <a
                    href="{{ filament()->getHomeUrl() }}"
                    class="px-5 py-2 bg-zinc-700 hover:bg-zinc-600 text-white text-sm font-medium rounded-xl transition-colors"
                >
                    Back to Dashboard
                </a>
            </div>
        </div>

        {{-- ============================================================== --}}
        {{-- RIGHT SIDEBAR: Participants + Chat                              --}}
        {{-- ============================================================== --}}
        <aside
            x-show="sidebarOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-x-full opacity-0"
            x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0 opacity-100"
            x-transition:leave-end="translate-x-full opacity-0"
            class="w-72 bg-zinc-900 border-l border-zinc-800 flex flex-col shrink-0 absolute right-0 inset-y-0 sm:relative z-10"
        >
            {{-- Sidebar tabs --}}
            <div class="flex border-b border-zinc-800 shrink-0">
                <button
                    @click="sidebarTab = 'participants'"
                    class="flex-1 px-4 py-2.5 text-xs font-medium transition-colors"
                    :class="sidebarTab === 'participants' ? 'text-violet-400 border-b-2 border-violet-400' : 'text-zinc-500 hover:text-zinc-300'"
                >
                    Participants ({{ count($activeParticipants) }})
                </button>
                <button
                    @click="sidebarTab = 'chat'"
                    class="flex-1 px-4 py-2.5 text-xs font-medium transition-colors"
                    :class="sidebarTab === 'chat' ? 'text-violet-400 border-b-2 border-violet-400' : 'text-zinc-500 hover:text-zinc-300'"
                >
                    Chat
                </button>
            </div>

            {{-- Participants tab --}}
            <div x-show="sidebarTab === 'participants'" class="flex-1 overflow-y-auto participant-scroll p-3 space-y-1.5">
                @forelse($activeParticipants as $participant)
                    <div class="flex items-center gap-2.5 p-2 rounded-lg hover:bg-zinc-800 transition-colors">
                        <div class="w-8 h-8 rounded-full bg-violet-600/30 border border-violet-500/40 flex items-center justify-center text-violet-300 text-xs font-bold shrink-0">
                            {{ strtoupper(substr($participant['name'], 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm text-white truncate font-medium">{{ $participant['name'] }}</p>
                            @if($participant['joined_at'])
                                <p class="text-[10px] text-zinc-500">Joined {{ $participant['joined_at'] }}</p>
                            @endif
                        </div>
                        @if((int) $meeting->host_id === $participant['id'])
                            <span class="ml-auto text-[10px] px-1.5 py-0.5 rounded bg-violet-600/20 text-violet-400 border border-violet-600/30 shrink-0">Host</span>
                        @endif
                    </div>
                @empty
                    <p class="text-xs text-zinc-600 text-center py-8">No active participants yet.</p>
                @endforelse
            </div>

            {{-- Chat tab --}}
            <div x-show="sidebarTab === 'chat'" class="flex-1 flex flex-col overflow-hidden">
                <div class="flex-1 overflow-y-auto participant-scroll p-3 space-y-3" id="chat-messages">
                    <div class="text-center">
                        <p class="text-xs text-zinc-600 py-4">
                            Use Jitsi's built-in chat for messaging.<br>
                            <button @click="toggleJitsiChat()" class="text-violet-400 hover:text-violet-300 underline underline-offset-2">Open chat panel</button>
                        </p>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    {{-- ================================================================== --}}
    {{-- BOTTOM CONTROL BAR                                                  --}}
    {{-- ================================================================== --}}
    <footer
        x-show="jitsiReady && !isMeetingEnded"
        x-transition
        class="flex items-center justify-between px-4 py-2.5 bg-zinc-900/95 backdrop-blur border-t border-zinc-800 shrink-0 z-20"
    >
        {{-- Left: meeting info --}}
        <div class="hidden sm:flex items-center gap-2 text-zinc-500 text-xs min-w-0">
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span x-text="elapsedTime" class="font-mono tabular-nums">00:00</span>
        </div>

        {{-- Center: media controls --}}
        <div class="flex items-center gap-2 mx-auto sm:mx-0">
            {{-- Mute/Unmute --}}
            <button
                @click="toggleAudio()"
                :class="audioMuted ? 'bg-red-600 hover:bg-red-500 text-white' : 'bg-zinc-700 hover:bg-zinc-600 text-zinc-200'"
                class="flex flex-col items-center gap-0.5 px-3 py-2 rounded-xl transition-colors text-xs"
                title="Toggle microphone"
            >
                <svg x-show="!audioMuted" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                </svg>
                <svg x-show="audioMuted" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
                </svg>
                <span x-text="audioMuted ? 'Unmute' : 'Mute'" class="hidden sm:block"></span>
            </button>

            {{-- Camera toggle --}}
            <button
                @click="toggleVideo()"
                :class="videoMuted ? 'bg-red-600 hover:bg-red-500 text-white' : 'bg-zinc-700 hover:bg-zinc-600 text-zinc-200'"
                class="flex flex-col items-center gap-0.5 px-3 py-2 rounded-xl transition-colors text-xs"
                title="Toggle camera"
            >
                <svg x-show="!videoMuted" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.069A1 1 0 0121 8.882v6.236a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                <svg x-show="videoMuted" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
                <span x-text="videoMuted ? 'Start Video' : 'Stop Video'" class="hidden sm:block"></span>
            </button>

            {{-- Screen share --}}
            <button
                @click="toggleScreenShare()"
                :class="isSharing ? 'bg-violet-600 hover:bg-violet-500 text-white' : 'bg-zinc-700 hover:bg-zinc-600 text-zinc-200'"
                class="flex flex-col items-center gap-0.5 px-3 py-2 rounded-xl transition-colors text-xs"
                title="Share screen"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <span x-text="isSharing ? 'Stop Share' : 'Share'" class="hidden sm:block"></span>
            </button>

            {{-- Chat --}}
            <button
                @click="toggleJitsiChat()"
                class="flex flex-col items-center gap-0.5 px-3 py-2 rounded-xl bg-zinc-700 hover:bg-zinc-600 text-zinc-200 transition-colors text-xs"
                title="Toggle chat"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <span class="hidden sm:block">Chat</span>
            </button>

            @if($isHost)
                {{-- End meeting (host only) --}}
                <button
                    wire:click="endMeeting"
                    wire:confirm="Are you sure you want to end this meeting for everyone?"
                    class="flex flex-col items-center gap-0.5 px-3 py-2 rounded-xl bg-red-700 hover:bg-red-600 text-white transition-colors text-xs ml-2"
                    title="End meeting for all"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                    <span class="hidden sm:block">End</span>
                </button>
            @else
                {{-- Leave meeting (participant) --}}
                <button
                    wire:click="leaveMeeting"
                    class="flex flex-col items-center gap-0.5 px-3 py-2 rounded-xl bg-red-700 hover:bg-red-600 text-white transition-colors text-xs ml-2"
                    title="Leave meeting"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span class="hidden sm:block">Leave</span>
                </button>
            @endif
        </div>

        {{-- Right spacer --}}
        <div class="hidden sm:block w-24"></div>
    </footer>
</div>

@push('scripts')
<script>
function meetingRoom(config) {
    return {
        // Config
        roomId:      config.roomId,
        domain:      config.domain,
        displayName: config.displayName,
        email:       config.email,
        jwt:         config.jwt,
        isHost:      config.isHost,
        meetingUuid: config.meetingUuid,

        // State
        api:               null,
        jitsiLoadStarted: false,
        jitsiReady:       false,
        isMeetingEnded: false,
        audioMuted:   false,
        videoMuted:   false,
        isSharing:    false,
        sidebarOpen:  window.innerWidth >= 640,
        sidebarTab:   'participants',
        elapsedTime:  '00:00',
        startedAt:    null,
        timerInterval: null,

        // ----------------------------------------------------------------
        // Bootstrap
        // ----------------------------------------------------------------
        init() {
            // If meeting is active, load Jitsi immediately
            @if($meeting->isActive())
                this.loadJitsi();
            @elseif($meeting->isScheduled() && $isHost)
                // Host: after "Start meeting", Livewire dispatches meeting-started → loadJitsi()
            @elseif($meeting->isScheduled())
                // Participant: Echo meeting.started or wire:poll refresh → meeting-started → loadJitsi()
            @endif
        },

        onMeetingStartedFromLivewire() {
            if (this.isMeetingEnded || this.api) {
                return;
            }
            this.loadJitsi();
        },

        // ----------------------------------------------------------------
        // Jitsi Loading
        // ----------------------------------------------------------------
        loadJitsi() {
            if (this.jitsiLoadStarted || this.api) {
                return;
            }
            this.jitsiLoadStarted = true;

            if (document.getElementById('jitsi-api-script')) {
                this.initJitsiApi();
                return;
            }

            const script = document.createElement('script');
            script.id  = 'jitsi-api-script';
            script.src = `https://${this.domain}/external_api.js`;
            script.onload  = () => this.initJitsiApi();
            script.onerror = () => {
                console.error('Failed to load Jitsi external API script.');
                this.jitsiLoadStarted = false;
            };
            document.head.appendChild(script);
        },

        initJitsiApi() {
            if (typeof JitsiMeetExternalAPI === 'undefined') {
                console.error('JitsiMeetExternalAPI not available.');
                this.jitsiLoadStarted = false;
                return;
            }

            const parentNode = document.getElementById('jitsi-container');
            if (! parentNode) {
                console.error('Jitsi container node missing.');
                this.jitsiLoadStarted = false;
                return;
            }

            const options = {
                roomName:     this.roomId,
                parentNode,
                userInfo: {
                    displayName: this.displayName,
                    email:       this.email,
                },
                configOverwrite: {
                    startWithAudioMuted: false,
                    startWithVideoMuted: false,
                    disableDeepLinking: true,
                    prejoinPageEnabled: false,
                    // Participant pane / invite / security / mute-all need toolbar entries.
                    toolbarButtons: [
                        'microphone',
                        'camera',
                        'desktop',
                        'chat',
                        'raisehand',
                        'participants-pane',
                        'invite',
                        'tileview',
                        'hangup',
                        'settings',
                        'fullscreen',
                        'filmstrip',
                        'mute-everyone',
                        'security',
                    ],
                    participantsPane: {
                        hideModeratorSettingsTab: false,
                        hideMoreActionsButton: false,
                        hideMuteAllButton: false,
                    },
                },
                interfaceConfigOverwrite: {
                    SHOW_JITSI_WATERMARK: false,
                    SHOW_WATERMARK_FOR_GUESTS: false,
                    SHOW_BRAND_WATERMARK: false,
                    BRAND_WATERMARK_LINK: '',
                    MOBILE_APP_PROMO: false,
                    DEFAULT_REMOTE_DISPLAY_NAME: 'Participant',
                },
                width:  '100%',
                height: '100%',
            };

            if (this.jwt) {
                options.jwt = this.jwt;
            }

            this.api = new JitsiMeetExternalAPI(this.domain, options);

            this.bindJitsiEvents();
            this.startTimer();
            // Iframe + API accept executeCommand once constructed (before full conference join).
            this.jitsiReady = true;
        },

        // ----------------------------------------------------------------
        // Jitsi Events
        // ----------------------------------------------------------------
        bindJitsiEvents() {
            this.api.addEventListeners({
                videoConferenceJoined: (event) => {
                    console.log('Jitsi: joined', event);
                    this.jitsiReady = true;
                },

                videoConferenceLeft: (event) => {
                    console.log('Jitsi: left', event);
                    @this.call('leaveMeeting');
                },

                participantJoined: (event) => {
                    console.log('Jitsi: participant joined', event);
                    // Livewire handles participant list via Echo
                },

                participantLeft: (event) => {
                    console.log('Jitsi: participant left', event);
                },

                audioMuteStatusChanged: ({ muted }) => {
                    this.audioMuted = muted;
                },

                videoMuteStatusChanged: ({ muted }) => {
                    this.videoMuted = muted;
                },

                screenSharingStatusChanged: ({ on }) => {
                    this.isSharing = on;
                },
            });
        },

        // ----------------------------------------------------------------
        // Controls
        // ----------------------------------------------------------------
        toggleAudio() {
            if (this.api) {
                this.api.executeCommand('toggleAudio');
            }
        },

        toggleVideo() {
            if (this.api) {
                this.api.executeCommand('toggleVideo');
            }
        },

        toggleScreenShare() {
            if (this.api) {
                this.api.executeCommand('toggleShareScreen');
            }
        },

        toggleJitsiChat() {
            if (this.api) {
                this.api.executeCommand('toggleChat');
            }
        },

        // ----------------------------------------------------------------
        // Meeting-ended handler
        // ----------------------------------------------------------------
        onMeetingEnded() {
            this.isMeetingEnded = true;
            this.stopTimer();
            if (this.api) {
                this.api.dispose();
                this.api = null;
            }
            this.jitsiLoadStarted = false;
            this.jitsiReady = false;
        },

        // ----------------------------------------------------------------
        // Timer
        // ----------------------------------------------------------------
        startTimer() {
            this.startedAt = Date.now();
            this.timerInterval = setInterval(() => {
                const diff = Math.floor((Date.now() - this.startedAt) / 1000);
                const h    = Math.floor(diff / 3600);
                const m    = Math.floor((diff % 3600) / 60);
                const s    = diff % 60;
                this.elapsedTime = h > 0
                    ? `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`
                    : `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
            }, 1000);
        },

        stopTimer() {
            if (this.timerInterval) {
                clearInterval(this.timerInterval);
                this.timerInterval = null;
            }
        },
    };
}
</script>
@endpush
