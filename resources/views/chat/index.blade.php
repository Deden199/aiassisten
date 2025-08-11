@extends('layouts.app')

@section('title','Chat')

@section('header')
  <h2 class="font-semibold text-xl text-gray-800">Chatbot</h2>
@endsection

@section('content')
<div class="max-w-3xl mx-auto px-6 lg:px-8 py-8">
  <div id="chat" class="rounded-2xl border bg-white shadow-sm h-[70vh] flex flex-col">
    <div id="messages" class="flex-1 overflow-y-auto p-4 space-y-3 text-sm"></div>
    <form id="chatForm" class="border-t p-3 flex gap-2">
      @csrf
      <input type="text" name="message" id="msg" class="flex-1 rounded-xl border px-3 py-2" placeholder="Type a message..." autocomplete="off">
      <button class="px-4 py-2 rounded-xl bg-gray-900 text-white">Send</button>
    </form>
  </div>
</div>
<script>
const box = document.getElementById('messages');
const form = document.getElementById('chatForm');
const input = document.getElementById('msg');
let history = [];

function add(role, text){
  history.push({ role, content: text });
  const item = document.createElement('div');
  item.className = role === 'user' ? 'text-right' : 'text-left';
  item.innerHTML = `<div class="inline-block rounded-2xl px-3 py-2 ${role==='user'?'bg-violet-600 text-white':'bg-gray-100'}">${text.replace(/</g,'&lt;')}</div>`;
  box.appendChild(item); box.scrollTop = box.scrollHeight;
}

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  const text = input.value.trim(); if(!text) return;
  add('user', text); input.value='';
  const res = await fetch('{{ route('chat.send') }}', {
    method:'POST',
    headers:{'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept':'application/json','Content-Type':'application/json'},
    body: JSON.stringify({messages: history})
  });
  const data = await res.json();
  add('assistant', data.reply || 'No reply');
});
</script>
@endsection
