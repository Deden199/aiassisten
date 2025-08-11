@extends('layouts.app')

@section('title','Chat')
@section('meta_description','Interact with our AI assistant in real time.')
@section('canonical', route('chat'))
@push('meta')
  <meta property="og:image" content="{{ asset('images/chat-og.png') }}">
  <meta name="twitter:image" content="{{ asset('images/chat-og.png') }}">
@endpush

@section('header')
  <h2 class="font-semibold text-xl text-gray-800">Chatbot</h2>
@endsection

@section('content')
<div class="max-w-3xl mx-auto px-6 lg:px-8 py-8">
  <div id="chat" class="rounded-2xl border bg-white shadow-sm h-[70vh] flex flex-col">
    <div id="messages" class="flex-1 overflow-y-auto p-4 space-y-3 text-sm"></div>
    <form id="chatForm" action="{{ route('chat.send') }}" method="POST" class="border-t p-3 flex gap-2">
      @csrf
      <input type="text" name="message" id="msg" class="flex-1 rounded-xl border px-3 py-2" placeholder="Type a message..." autocomplete="off">
      <button class="px-4 py-2 rounded-xl bg-gray-900 text-white">Send</button>
    </form>
  </div>
  <div id="chat-data" data-messages='@json($messages)' class="hidden"></div>
</div>
@endsection

@push('scripts')
  @vite('resources/js/chat.js')
@endpush
