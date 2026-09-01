<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'Music Archive') — Dashboard</title>
    <link rel="preconnect" href="https://cdn.tailwindcss.com">
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="https://cdn.tailwindcss.com">
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        [x-cloak] { display: none !important; }
        .audio-player::-webkit-media-controls-panel { background: #1f2937; }
        .lyrics-scroll { scrollbar-width: none; -ms-overflow-style: none; }
        .lyrics-scroll::-webkit-scrollbar { display: none; }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen" x-data="{
        sidebarOpen: false,
        playerVisible: false,
        playerSrc: '',
        playerTitle: '',
        playerArtist: '',
        playerSongId: 0,
        playerAlbumId: 0,
        playerCover: '',
        playerYear: '',
        playerLyrics: '',
        playerCurrentTime: 0,
        lyricsLines: [],
        lyricsIndex: -1,
        showLyrics: false,
        coverCache: {},
        lyricsCache: {},
        fetchLyrics() {
            if (!this.playerSongId) return;
            if (this.lyricsCache[this.playerSongId]) {
                this.playerLyrics = this.lyricsCache[this.playerSongId];
                this.parseLRC(this.playerLyrics);
                return;
            }
            fetch('/api/songs/' + this.playerSongId + '/lyrics')
                .then(r => r.json())
                .then(d => {
                    this.playerLyrics = d.lyrics || '';
                    this.lyricsCache[this.playerSongId] = this.playerLyrics;
                    this.parseLRC(this.playerLyrics);
                });
        },
        fetchAlbumArt() {
            if (!this.playerAlbumId) return;
            if (this.coverCache[this.playerAlbumId]) {
                this.playerCover = this.coverCache[this.playerAlbumId].url;
                this.playerYear = this.coverCache[this.playerAlbumId].year;
                return;
            }
            this.playerCover = '';
            fetch('/api/albums/' + this.playerAlbumId + '/art')
                .then(r => r.json())
                .then(d => {
                    if (d.artwork_url) this.playerCover = d.artwork_url;
                    if (d.year) this.playerYear = d.year;
                    this.coverCache[this.playerAlbumId] = { url: d.artwork_url || '', year: d.year || '' };
                });
        },
        playSong(src, title, artist, songId, albumId) {
            this.playerTitle = title;
            this.playerArtist = artist;
            this.playerSongId = songId;
            this.playerAlbumId = albumId;
            this.playerCover = '';
            this.playerYear = '';
            this.playerVisible = true;
            this.showLyrics = false;
            this.lyricsLines = [];
            this.lyricsIndex = -1;
            this.fetchAlbumArt();
            this.fetchLyrics();
            this.stopLyricsLoop();
            fetch('/api/songs/' + songId + '/url')
                .then(r => r.json())
                .then(d => {
                    this.playerSrc = d.url;
                    this.$nextTick(() => { this.$refs.audioPlayer.load(); this.$refs.audioPlayer.play(); });
                })
                .catch(() => {
                    this.playerSrc = src;
                    this.$nextTick(() => { this.$refs.audioPlayer.load(); this.$refs.audioPlayer.play(); });
                });
        },
        syncLyrics() {
            if (!this.lyricsLines.length) return;
            const t = this.$refs.audioPlayer ? this.$refs.audioPlayer.currentTime : 0;
            let idx = -1;
            for (let i = this.lyricsLines.length - 1; i >= 0; i--) {
                if (t >= this.lyricsLines[i].time) {
                    idx = i;
                    break;
                }
            }
            if (idx !== -1) {
                this.lyricsIndex = idx;
                this.$nextTick(() => {
                    const el = document.getElementById('lyric-line-' + idx);
                    if (el) {
                        const container = el.closest('.lyrics-scroll');
                        if (container && container.scrollHeight > container.clientHeight) {
                            const offset = el.offsetTop - container.offsetTop - container.clientHeight / 2 + el.clientHeight / 2;
                            container.scrollTo({ top: Math.max(0, offset), behavior: 'auto' });
                        }
                    }
                });
            }
        },
        startLyricsLoop() {
            this.stopLyricsLoop();
            const tick = () => {
                if (this.playerVisible && this.showLyrics && this.lyricsLines.length) {
                    this.syncLyrics();
                    this._rafId = requestAnimationFrame(tick);
                }
            };
            this._rafId = requestAnimationFrame(tick);
        },
        stopLyricsLoop() {
            if (this._rafId) { cancelAnimationFrame(this._rafId); this._rafId = null; }
        },
        seekToLine(time) {
            this.$refs.audioPlayer.currentTime = time;
            this.syncLyrics();
        },
        parseLRC(lrc) {
            if (!lrc) { this.lyricsLines = []; return; }
            const lines = lrc.split('\n');
            const result = [];
            const regex = /\[(\d{2}):(\d{2})\.(\d{2,3})\](.*)/;
            for (const line of lines) {
                const m = line.match(regex);
                if (m) {
                    const time = parseInt(m[1]) * 60 + parseInt(m[2]) + parseInt(m[3]) / (m[3].length === 3 ? 1000 : 100);
                    result.push({ time, text: m[4].trim() });
                }
            }
            result.sort((a, b) => a.time - b.time);
            this.lyricsLines = result;
            this.syncLyrics();
        },
    }">

    {{-- Global audio player --}}
    <div x-show="playerVisible" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
         class="fixed bottom-0 right-0 z-50 bg-gray-900 border-t border-gray-800 shadow-2xl left-0 lg:left-64">
        {{-- Synced lyrics panel --}}
        <div x-show="showLyrics" class="max-w-3xl mx-auto px-3 sm:px-4 pt-4 pb-2 max-h-60 overflow-y-auto lyrics-scroll">
            <div class="flex items-center gap-3 sm:gap-4 mb-4">
                <img x-show="playerCover" :src="playerCover" loading="lazy" decoding="async" class="w-12 h-12 sm:w-16 sm:h-16 rounded-lg object-cover shadow-lg flex-shrink-0">
                <div x-show="!playerCover" class="w-12 h-12 sm:w-16 sm:h-16 rounded-lg bg-gray-800 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 sm:w-8 sm:h-8 text-gray-600" fill="currentColor" viewBox="0 0 20 20"><path d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.82l8-1.6v5.894A4.37 4.37 0 0015 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z"/></svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold truncate" x-text="playerTitle"></p>
                    <p class="text-xs text-gray-400 truncate" x-text="playerArtist"></p>
                    <p x-show="playerYear" class="text-xs text-gray-500 mt-0.5" x-text="playerYear"></p>
                </div>
            </div>
            <div x-show="lyricsLines.length > 0" class="text-center space-y-1">
                <template x-for="(line, i) in lyricsLines" :key="i">
                    <p :id="'lyric-line-' + i"
                       class="text-sm transition-all duration-300 cursor-pointer"
                       :class="i === lyricsIndex
                           ? 'text-indigo-400 font-bold text-lg scale-105'
                           : 'text-gray-500 hover:text-gray-300'"
                       x-text="line.text || '♪'"
                       @click="seekToLine(line.time)">
                    </p>
                </template>
            </div>
            <p x-show="lyricsLines.length === 0" class="text-center text-gray-500 text-sm py-2">No lyrics found for this song.</p>
        </div>

        {{-- Player controls --}}
        <div class="max-w-7xl mx-auto px-3 sm:px-4 py-2 sm:py-3">
            <div class="flex items-center gap-2 sm:gap-4">
                <button @click="playerVisible = false; stopLyricsLoop();" class="text-gray-400 hover:text-white flex-shrink-0">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 00-1 1v4a1 1 0 002 0V8a1 1 0 00-1-1z"/></svg>
                </button>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium truncate" x-text="playerTitle"></p>
                    <p class="text-xs text-gray-400 truncate" x-text="playerArtist"></p>
                </div>
                <button @click="showLyrics = !showLyrics; if (showLyrics) { startLyricsLoop(); syncLyrics(); } else { stopLyricsLoop(); }"
                        class="px-2 py-1 text-xs rounded transition flex-shrink-0"
                        :class="showLyrics ? 'bg-indigo-600 text-white' : 'bg-gray-800 text-gray-400 hover:text-white'">
                    Lyrics
                </button>
            </div>
            <div class="mt-2">
                <audio x-ref="audioPlayer" :src="playerSrc" controls preload="metadata"
                       class="w-full h-10 audio-player bg-gray-800 rounded-lg"
                       @timeupdate="syncLyrics()"
                       @seeked="syncLyrics()"
                       @playing="syncLyrics(); if (showLyrics) startLyricsLoop();">
                </audio>
            </div>
        </div>
    </div>

    {{-- Mobile sidebar overlay --}}
    <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-40 bg-black/60 lg:hidden" @click="sidebarOpen = false"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    </div>

    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside class="w-64 bg-gray-900 border-r border-gray-800 flex flex-col flex-shrink-0 fixed inset-y-0 left-0 z-50 transform transition-transform duration-200 lg:static lg:translate-x-0"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
            <div class="p-4 border-b border-gray-800 flex items-center justify-between">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <svg class="w-8 h-8 text-indigo-500" fill="currentColor" viewBox="0 0 20 20"><path d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.82l8-1.6v5.894A4.37 4.37 0 0015 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z"/></svg>
                    <span class="text-lg font-bold">Music Archive</span>
                </a>
                <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <nav class="flex-1 p-4 space-y-1">
                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0h4"/></svg>
                    Dashboard
                </a>
                <a href="{{ route('genres.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('genres.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/></svg>
                    Genres
                </a>
                <a href="{{ route('artists.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('artists.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Artists
                </a>
                <a href="{{ route('albums.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('albums.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    Albums
                </a>
                <a href="{{ route('songs.index') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('songs.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
                    Songs
                </a>
            </nav>
            <div class="p-4 border-t border-gray-800 text-xs text-gray-500">
                Music Archive v1.0
            </div>
        </aside>

        {{-- Main content --}}
        <div class="flex-1 flex flex-col min-w-0">
            {{-- Top bar --}}
            <header class="h-14 bg-gray-900 border-b border-gray-800 flex items-center px-3 sm:px-4 gap-3 sm:gap-4 flex-shrink-0">
                <button @click="sidebarOpen = !sidebarOpen" class="text-gray-400 hover:text-white lg:hidden">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="hidden lg:block">
                    @yield('header')
                </div>
                <div class="flex-1 lg:ml-0">
                    <form action="{{ route('dashboard.search') }}" method="GET" class="relative">
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search..."
                               class="bg-gray-800 border border-gray-700 rounded-lg px-4 py-1.5 text-sm w-full lg:w-64 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-white placeholder-gray-400">
                    </form>
                </div>
            </header>

            {{-- Page content --}}
            <main class="flex-1 p-3 sm:p-4 lg:p-6 overflow-auto pb-24 lg:pb-6">
                <div class="lg:block">
                    @yield('header')
                </div>
                @if(session('success'))
                    <div class="mb-4 bg-green-900/50 border border-green-700 text-green-200 px-4 py-3 rounded-lg text-sm">
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-4 bg-red-900/50 border border-red-700 text-red-200 px-4 py-3 rounded-lg text-sm">
                        {{ session('error') }}
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
