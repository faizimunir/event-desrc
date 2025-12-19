<div 
    x-data="{ 
        darkMode: localStorage.getItem('darkMode') === 'true' || (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches),
        init() {
            this.$watch('darkMode', value => {
                localStorage.setItem('darkMode', value);
                if (value) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            });
            // Apply initial state
            if (this.darkMode) {
                document.documentElement.classList.add('dark');
            }
        }
    }"
    class="inline-flex items-center"
>
    <button
        @click="darkMode = !darkMode"
        class="p-2 rounded-lg text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
        :aria-label="darkMode ? 'Switch to light mode' : 'Switch to dark mode'"
    >
        <!-- Sun Icon (Light Mode) -->
        <svg 
            x-show="!darkMode"
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
        
        <!-- Moon Icon (Dark Mode) -->
        <svg 
            x-show="darkMode"
            x-cloak
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
        </svg>
    </button>
</div>

