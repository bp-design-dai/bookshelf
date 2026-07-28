<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            通知一覧
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                @if ($notifications->isEmpty())
                    <div class="px-6 py-16 text-center">
                        <p class="text-sm text-gray-500">
                            通知はありません。
                        </p>
                    </div>
                @else
                    <ul class="divide-y divide-gray-100">
                        @foreach ($notifications as $notification)
                            @php
                                $notificationType = $notification->data['notification_type'] ?? null;
                                $isUnread = $notification->read_at === null;

                                $style = match ($notificationType) {
                                    'three_days_before' => [
                                        'border' => 'bg-blue-500',
                                        'badge' => 'bg-blue-100 text-blue-800',
                                        'title' => '期限3日前のお知らせ',
                                    ],
                                    'due_date' => [
                                        'border' => 'bg-yellow-500',
                                        'badge' => 'bg-yellow-100 text-yellow-800',
                                        'title' => '本日が読書期限です',
                                    ],
                                    'expired' => [
                                        'border' => 'bg-red-500',
                                        'badge' => 'bg-red-100 text-red-800',
                                        'title' => '読書期限切れのお知らせ',
                                    ],
                                    default => [
                                        'border' => 'bg-gray-400',
                                        'badge' => 'bg-gray-100 text-gray-800',
                                        'title' => '通知',
                                    ],
                                };
                            @endphp

                            <li class="relative {{ $isUnread ? 'bg-blue-50/40' : 'bg-white' }}">
                                @if ($isUnread)
                                    <span
                                        class="absolute inset-y-0 left-0 w-1 {{ $style['border'] }}"
                                        aria-hidden="true"
                                    ></span>
                                @endif

                                <div class="px-6 py-4 flex items-start">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            @if ($isUnread)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">
                                                    未読
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                                                    既読
                                                </span>
                                            @endif

                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $style['badge'] }}">
                                                {{ $style['title'] }}
                                            </span>
                                        </div>

                                        <p class="mt-3 text-sm font-semibold text-gray-900">
                                            {{ $notification->data['book_title'] ?? '書籍名なし' }}
                                        </p>

                                        <p class="mt-1 text-sm text-gray-600">
                                            期日：{{ $notification->data['due_date'] ?? '-' }}
                                        </p>

                                        <p class="mt-2 text-sm text-gray-700">
                                            {{ $notification->data['message'] ?? '' }}
                                        </p>

                                        <p class="mt-2 text-xs text-gray-400">
                                            {{ $notification->created_at->diffForHumans() }}
                                        </p>
                                    </div>

                                    @if ($isUnread)
                                        <div class="ml-4 flex-shrink-0">
                                            <form
                                                action="{{ route('notifications.read', $notification->id) }}"
                                                method="POST"
                                                novalidate
                                            >
                                                @csrf

                                                <button
                                                    type="submit"
                                                    class="text-sm font-medium text-blue-600 hover:text-blue-800 hover:underline"
                                                >
                                                    既読にする
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <div class="p-6">
                        {{ $notifications->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>