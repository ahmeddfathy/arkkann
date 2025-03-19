@extends('layouts.app')

@section('content')
<div class="chat-container"
     data-chat-config="{{ e(json_encode([
         'currentUserId' => Auth::id(),
         'csrfToken' => csrf_token(),
         'managerData' => $manager ?? null
     ])) }}">
    <div class="chat-sidebar">
        <div class="chat-sidebar-header">
            <div class="user-profile">
                <div class="user-avatar">
                    <img src="{{ e(asset('images/default-avatar.png')) }}" alt="Profile" class="img-fluid">
                </div>
                <div class="user-info">
                    <h4>{{ e(Auth::user()->name) }}</h4>
                    <span class="status-text">{{ e(Auth::user()->role) }}</span>
                </div>
            </div>
        </div>

        <div class="chat-list">
            @foreach($chats as $chat)
            <div class="chat-item @if($chat['unread_count'] > 0) unread @endif"
                 data-user-id="{{ e($chat['user']->id) }}"
                 data-user-name="{{ e($chat['user']->name) }}">
                <div class="chat-item-avatar">
                    <img src="{{ e($chat['user']->avatar ?? asset('images/default-avatar.png')) }}"
                         alt="Avatar"
                         class="img-fluid">
                    <span class="status-dot {{ $chat['user']->is_online ? 'online' : 'offline' }}"></span>
                </div>
                <div class="chat-item-content">
                    <div class="chat-item-header">
                        <h5>{{ e($chat['user']->name) }}</h5>
                        <span class="chat-time">
                            {{ e($chat['last_message']->created_at->diffForHumans(null, true)) }}
                        </span>
                    </div>
                    <div class="chat-item-message">
                        <p>{{ e(Str::limit($chat['last_message']->content, 50)) }}</p>
                        @if($chat['unread_count'] > 0)
                            <span class="unread-badge">{{ e($chat['unread_count']) }}</span>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="chat-main">
        <div id="no-chat-selected" class="no-chat-selected">
            <div class="no-chat-content">
                <img src="{{ e(asset('images/select-chat.png')) }}" alt="Select a chat" class="img-fluid">
                <h3>Select a chat to start messaging</h3>
            </div>
        </div>

        <div id="chat-area" class="chat-area d-none">
            <div class="chat-header">
                <div class="chat-contact-info">
                    <div class="contact-avatar">
                        <img src="{{ e(asset('images/default-avatar.png')) }}"
                             alt="Contact"
                             id="contact-avatar"
                             class="img-fluid">
                    </div>
                    <div class="contact-details">
                        <h4 id="contact-name"></h4>
                        <span class="status-text" id="contact-status"></span>
                    </div>
                </div>
            </div>

            <div class="chat-content">
                <div class="messages-container" id="messages-container"></div>
            </div>

            <div class="chat-input-area">
                <form id="message-form" class="message-form">
                    <div class="input-wrapper">
                        <input type="text"
                               id="message-input"
                               class="form-control"
                               placeholder="Type a message"
                               autocomplete="off"
                               maxlength="1000">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ e(asset('css/chat.css')) }}">
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.0.6/purify.min.js"
        integrity="sha512-H+rglffZ6f5gF7UJgvH4Naa+fGCgjrHKMgoFOGmcPTRwR6oILo5R+gtzNrpDp7iMV3udbymBVjkeZGNz1Em4Q=="
        crossorigin="anonymous"
        referrerpolicy="no-referrer"></script>
<script src="{{ e(asset('js/chat.js')) }}"></script>
@endpush
