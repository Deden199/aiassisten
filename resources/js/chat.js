import hljs from 'highlight.js';
import 'highlight.js/styles/github.css';

document.addEventListener('DOMContentLoaded', () => {
  const box = document.getElementById('messages');
  const form = document.getElementById('chatForm');
  const input = document.getElementById('msg');
  const sendBtn = form?.querySelector('button[type="submit"]');
  const dataEl = document.getElementById('chat-data');
  const history = dataEl ? JSON.parse(dataEl.dataset.messages || '[]') : [];

  function renderContent(container, text) {
    const fence = /```(\w+)?\n([\s\S]*?)```/g;
    let lastIndex = 0;
    let match;
    while ((match = fence.exec(text)) !== null) {
      const [full, lang, code] = match;
      const before = text.slice(lastIndex, match.index);
      if (before.trim()) {
        const p = document.createElement('p');
        p.textContent = before.trim();
        container.appendChild(p);
      }
      const pre = document.createElement('pre');
      const codeEl = document.createElement('code');
      if (lang) codeEl.className = lang;
      codeEl.textContent = code.trim();
      pre.appendChild(codeEl);
      container.appendChild(pre);
      hljs.highlightElement(codeEl);
      lastIndex = match.index + full.length;
    }
    const after = text.slice(lastIndex);
    if (after.trim()) {
      const p = document.createElement('p');
      p.textContent = after.trim();
      container.appendChild(p);
    }
  }

  function add(role, text) {
    const item = document.createElement('div');
    item.className = role === 'user' ? 'text-right' : 'text-left';
    const bubble = document.createElement('div');
    bubble.className = `inline-block rounded-2xl px-3 py-2 whitespace-pre-wrap ${role === 'user' ? 'bg-violet-600 text-white' : 'bg-gray-100'}`;
    renderContent(bubble, text);
    item.appendChild(bubble);
    box.appendChild(item);
    box.scrollTop = box.scrollHeight;
  }

  history.forEach(m => add(m.role, m.content));

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const text = input.value.trim();
    if (!text) return;
    add('user', text);
    input.value = '';
    if (sendBtn) sendBtn.disabled = true;
    try {
      const res = await fetch(form.getAttribute('action'), {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute('content'),
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ message: text }),
      });
      if (!res.ok) {
        const errText = await res.text();
        add('bot', errText || 'Server error');
        return;
      }
      const data = await res.json();
      add('bot', data.reply || 'No reply');
    } catch (err) {
      add('bot', 'Error: ' + err.message);
    } finally {
      if (sendBtn) sendBtn.disabled = false;
    }
  });
});
