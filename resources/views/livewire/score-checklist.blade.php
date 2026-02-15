<div>
    {{-- Progress --}}
    <div class="mb-4">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-700">
                {{ $this->completedCount }}/{{ $this->totalCount }} {{ __('model-scores::messages.completed') }}
            </span>
            <span class="text-sm font-medium text-gray-700">
                {{ $this->progressPercentage }}%
            </span>
        </div>
        <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
            <div class="h-full bg-primary-500 rounded-full transition-all duration-500"
                 style="width: {{ $this->progressPercentage }}%"></div>
        </div>
    </div>

    {{-- Grouped Checklist --}}
    <div class="space-y-4">
        @foreach ($this->groupedChecklist as $groupName => $items)
            <div>
                <h3 class="text-sm font-semibold text-gray-900 mb-2">{{ $groupName }}</h3>
                <div class="space-y-1">
                    @foreach ($items as $item)
                        <div class="flex items-center gap-3 p-2 rounded-lg {{ $item->is_complete ? 'bg-green-50' : 'bg-gray-50' }}">
                            {{-- Status Icon --}}
                            <div class="flex-shrink-0">
                                @if ($item->is_complete)
                                    <div class="w-5 h-5 bg-green-500 rounded-full flex items-center justify-center">
                                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                @else
                                    <div class="w-5 h-5 border-2 border-gray-300 rounded-full"></div>
                                @endif
                            </div>

                            {{-- Task Info --}}
                            <div class="flex-1 min-w-0">
                                @if ($item->route && ! $item->is_complete)
                                    <a href="{{ $item->route }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">
                                        {{ $item->task->name }}
                                    </a>
                                @else
                                    <span class="text-sm font-medium {{ $item->is_complete ? 'text-gray-500 line-through' : 'text-gray-900' }}">
                                        {{ $item->task->name }}
                                    </span>
                                @endif

                                @if ($item->task->description)
                                    <p class="text-xs text-gray-500 truncate">{{ $item->task->description }}</p>
                                @endif
                            </div>

                            {{-- Score --}}
                            <div class="flex-shrink-0 text-xs text-gray-500">
                                {{ $item->score }}/{{ $item->max_score }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
