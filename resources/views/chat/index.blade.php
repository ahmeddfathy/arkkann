@extends('layouts.app')

@section('content')
<div class="chat-container"
     data-chat-config="{{ json_encode([
         'currentUserId' => Auth::id(),
         'csrfToken' => csrf_token(),
         'managerData' => $manager ?? null
     ]) }}">
    <div class="chat-sidebar">
        <div class="chat-sidebar-header">
            <div class="user-profile">
                <div class="user-avatar">
                    <img src="https://th.bing.com/th/id/OIP.7dl-g74HDQZsmchtLCsMvQHaHa?rs=1&pid=ImgDetMain" alt="Profile">
                </div>
                <div class="user-info">
                    <h4>{{ Auth::user()->name }}</h4>
                    <span class="status-text">{{ Auth::user()->role }}</span>
                </div>
            </div>
        </div>

        <div class="chat-list">
            @foreach($chats as $chat)
            <div class="chat-item @if($chat['unread_count'] > 0) unread @endif"
                 data-user-id="{{ $chat['user']->id }}"
                 data-user-name="{{ $chat['user']->name }}">
                <div class="chat-item-avatar">
                    <img src="{{ $chat['user']->avatar ?? asset('images/default-avatar.png') }}" alt="Avatar">
                    <span class="status-dot {{ $chat['user']->is_online ? 'online' : 'offline' }}"></span>
                </div>
                <div class="chat-item-content">
                    <div class="chat-item-header">
                        <h5>{{ $chat['user']->name }}</h5>
                        <span class="chat-time">
                            {{ $chat['last_message']->created_at->diffForHumans(null, true) }}
                        </span>
                    </div>
                    <div class="chat-item-message">
                        <p>{{ Str::limit($chat['last_message']->content, 50) }}</p>
                        @if($chat['unread_count'] > 0)
                            <span class="unread-badge">{{ $chat['unread_count'] }}</span>
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
                <img src="https://th.bing.com/th/id/OIP.snSDnMg9wU5TwatvtkEuCQHaHa?rs=1&pid=ImgDetMain" alt="Select a chat">
                <h3>Select a chat to start messaging</h3>
            </div>
        </div>

        <div id="chat-area" class="chat-area d-none">
            <div class="chat-header" >
                <div class="chat-contact-info">
                    <div class="contact-avatar">
                        <img src="https://th.bing.com/th/id/OIP.7dl-g74HDQZsmchtLCsMvQHaHa?rs=1&pid=ImgDetMain" alt="Contact" id="contact-avatar">
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
                <form id="message-form">
                    <div class="input-wrapper">
                        <input type="text"
                               id="message-input"
                               placeholder="Type a message"
                               autocomplete="off">
                        <button type="submit">
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
<link rel="stylesheet" href="{{ asset('css/chat.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/chat.js') }}"></script>
@endpush
