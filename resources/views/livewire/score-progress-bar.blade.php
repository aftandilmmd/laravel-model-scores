<div>
    <div class="flex items-center gap-3">
        {{-- Badge --}}
        @if ($this->badge)
            <div class="flex-shrink-0">
                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold"
                      style="background-color: {{ $this->badge->color ?? '#6b7280' }}20; color: {{ $this->badge->color ?? '#6b7280' }}">
                    @if ($this->badge->icon)
                        <x-dynamic-component :component="$this->badge->icon" class="w-3.5 h-3.5" />
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
