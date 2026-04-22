<x-filament-panels::page>
    <style>
        .inbox-thread-img img { max-width: 100%; height: auto; border-radius: 0.5rem; }
        .inbox-msg-header:hover { background-color: rgba(0, 0, 0, 0.02); }
        .dark .inbox-msg-header:hover { background-color: rgba(255, 255, 255, 0.03); }
    </style>

    <div style="border-radius: 0.75rem; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid rgba(0,0,0,0.06);" class="bg-white dark:bg-gray-900 dark:!border-white/10">
        @foreach ($this->threadMessages as $index => $threadMessage)
            @php
                $isCurrentMessage = $threadMessage->id === $this->message->id;
                $isSentByMe = $threadMessage->sender_id === auth()->id();
                $shouldExpand = $isCurrentMessage || $loop->last;

                $bgColors = ['#dbeafe','#fef3c7','#d1fae5','#ede9fe','#ffe4e6'];
                $fgColors = ['#1d4ed8','#b45309','#047857','#6d28d9','#be123c'];
                $senderIds = $this->threadMessages->pluck('sender_id')->unique()->values();
                $ci = $senderIds->search($threadMessage->sender_id) % count($bgColors);
            @endphp

            @if (!$loop->first)
                <div style="height: 1px; background-color: rgba(0,0,0,0.06);" class="dark:!bg-white/10"></div>
            @endif

            <div x-data="{ expanded: {{ $shouldExpand ? 'true' : 'false' }} }"
                @if($isCurrentMessage) style="background-color: rgba(59,130,246,0.03);" @endif
            >
                {{-- Header --}}
                <div
                    x-on:click="expanded = !expanded"
                    class="inbox-msg-header"
                    style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.75rem 1rem; cursor: pointer; transition: background-color 0.1s;"
                >
                    {{-- Avatar --}}
                    <div style="
                        width: 2rem; height: 2rem; border-radius: 9999px;
                        display: flex; align-items: center; justify-content: center;
                        font-weight: 600; font-size: 0.75rem; flex-shrink: 0;
                        margin-top: 0.125rem;
                        background-color: {{ $bgColors[$ci] }}; color: {{ $fgColors[$ci] }};
                    ">{{ strtoupper(mb_substr($threadMessage->sender->name, 0, 1)) }}</div>

                    {{-- Name + preview/recipients --}}
                    <div style="min-width: 0; flex: 1;">
                        <div style="display: flex; align-items: baseline; gap: 0.375rem;">
                            <span style="font-size: 0.8125rem; font-weight: 600; color: #111827;">
                                {{ $threadMessage->sender->name }}
                            </span>
                            @if ($isSentByMe)
                                <span style="font-size: 0.5625rem; padding: 0.0625rem 0.375rem; border-radius: 9999px; font-weight: 500; background-color: #f3f4f6; color: #6b7280; letter-spacing: 0.025em;">
                                    You
                                </span>
                            @endif
                        </div>
                        <p x-show="!expanded" style="font-size: 0.75rem; margin: 0.125rem 0 0; color: #9ca3af; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            {{ Str::limit(strip_tags($threadMessage->body), 100) }}
                        </p>
                        <p x-show="expanded" style="font-size: 0.75rem; margin: 0.125rem 0 0; color: #9ca3af;">
                            To: {{ $threadMessage->recipients->pluck('name')->join(', ') }}
                        </p>
                    </div>

                    {{-- Time + chevron --}}
                    <div style="display: flex; align-items: center; gap: 0.25rem; flex-shrink: 0; padding-top: 0.1875rem;">
                        <time style="font-size: 0.6875rem; color: #9ca3af; white-space: nowrap; font-variant-numeric: tabular-nums;">
                            {{ $threadMessage->created_at->format('M j, g:i A') }}
                        </time>
                        <svg x-bind:style="expanded ? 'transform: rotate(180deg)' : ''" width="14" height="14" style="width: 14px; height: 14px; min-width: 14px; color: #9ca3af; transition: transform 0.15s ease;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>

                {{-- Body --}}
                <div x-show="expanded" x-transition.opacity.duration.150ms>
                    <div style="padding: 0 1rem 1rem 3.75rem;">
                        <div class="fi-prose inbox-thread-img" style="font-size: 0.875rem; max-width: none; overflow-wrap: break-word;">
                            {!! \Filament\Forms\Components\RichEditor\RichContentRenderer::make($threadMessage->body)->fileAttachmentsDisk('public')->toHtml() !!}
                        </div>

                        {{-- Read receipts (only for messages sent by current user) --}}
                        @if ($isSentByMe && $threadMessage->messageRecipients && $threadMessage->messageRecipients->isNotEmpty())
                            <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid rgba(0,0,0,0.06);">
                                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
                                    @foreach ($threadMessage->messageRecipients as $mr)
                                        <div style="
                                            display: inline-flex; align-items: center; gap: 0.375rem;
                                            font-size: 0.6875rem; padding: 0.25rem 0.625rem;
                                            border-radius: 9999px; border: 1px solid rgba(0,0,0,0.08);
                                            {{ $mr->read_at ? 'background-color: #f0fdf4; color: #15803d; border-color: #bbf7d0;' : 'background-color: #f9fafb; color: #9ca3af; border-color: #e5e7eb;' }}
                                        ">
                                            {{-- Check or clock icon --}}
                                            @if ($mr->read_at)
                                                <svg width="12" height="12" style="width: 12px; height: 12px; min-width: 12px;" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                                                </svg>
                                            @else
                                                <svg width="12" height="12" style="width: 12px; height: 12px; min-width: 12px;" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-13a.75.75 0 0 0-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 0 0 0-1.5h-3.25V5Z" clip-rule="evenodd" />
                                                </svg>
                                            @endif
                                            <span>{{ $mr->recipient->name }}</span>
                                            @if ($mr->read_at)
                                                <span style="opacity: 0.7;">{{ $mr->read_at->format('M j, g:i A') }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
