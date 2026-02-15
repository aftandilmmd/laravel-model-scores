<div>
    <div class="flex items-center gap-3">
        {{-- Badge --}}
        @if ($this->badge)
            <div class="flex-shrink-0">
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold"
                      style="background-color: {{ $this->badge->color ?? '#6b7280' }}20; color: {{ $this->badge->color ?? '#6b7280' }}">
                    @if ($this->badge->icon)
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                        </svg>
                    @endif
                    {{ $this->badge->name }}
                </span>
            </div>
        @endif

        {{-- Progress Bar --}}
        <div class="flex-1">
            <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all duration-500"
                     style="width: {{ $this->progressPercentage }}%; background-color: {{ $this->badge?->color ?? '#3b82f6' }}"
                ></div>
            </div>
        </div>

        {{-- Score --}}
        <div class="flex-shrink-0 text-sm font-semibold text-gray-700">
            {{ $this->totalScore }}
        </div>
    </div>
</div>
