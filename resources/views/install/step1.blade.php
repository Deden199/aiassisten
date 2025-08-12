<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Installer — AI Assistant</title>
  <script defer src="https://cdn.tailwindcss.com"></script>
  <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-gray-50 text-gray-800">
<div class="max-w-2xl mx-auto py-10">
  <h1 class="text-2xl font-bold mb-6">Installer — Requirements</h1>
  <div class="bg-white shadow rounded p-6 space-y-4">
    <h2 class="font-semibold">Environment checks</h2>
    <ul class="list-disc pl-6">
      <li>PHP: {{ $checks['php'] }}</li>
      @foreach($checks['ext'] as $name => $ok)
        <li>{{ strtoupper($name) }}: <strong>{{ $ok ? 'OK' : 'Missing' }}</strong></li>
      @endforeach
      @foreach($checks['writable'] as $name => $ok)
        <li>{{ $name }} writable: <strong>{{ $ok ? 'OK' : 'Not writable' }}</strong></li>
      @endforeach
    </ul>
  </div>

  <div class="bg-white shadow rounded p-6 mt-6">
    <h2 class="font-semibold mb-4">Step 1 — Environment</h2>
    <form id="envForm" class="space-y-3">
      <input class="border p-2 w-full" placeholder="App Name" name="app_name" required>
      <input class="border p-2 w-full" placeholder="App URL (https://example.com)" name="app_url" required>
      <div class="grid grid-cols-2 gap-3">
        <input class="border p-2 w-full" placeholder="DB Host" name="db_host" required>
        <input class="border p-2 w-full" placeholder="DB Port" name="db_port" value="3306" required>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <input class="border p-2 w-full" placeholder="DB Database" name="db_database" required>
        <input class="border p-2 w-full" placeholder="DB Username" name="db_username" required>
      </div>
      <input class="border p-2 w-full" placeholder="DB Password" name="db_password">
      <input class="border p-2 w-full" placeholder="Envato Personal Token (optional)" name="envato_token">
      <button class="bg-black text-white px-4 py-2 rounded">Save & Continue</button>
    </form>
  </div>

  <div class="bg-white shadow rounded p-6 mt-6 hidden" id="step2">
    <h2 class="font-semibold mb-4">Step 2 — Migrate Database</h2>
    <button id="migrateBtn" class="bg-black text-white px-4 py-2 rounded">Run Migrations</button>
    <pre class="mt-3 p-3 bg-gray-100" id="migOut"></pre>
  </div>

  <div class="bg-white shadow rounded p-6 mt-6 hidden" id="step3">
    <h2 class="font-semibold mb-4">Step 3 — Create Admin & (Optional) Activate</h2>
    <form id="adminForm" class="space-y-3">
      <input class="border p-2 w-full" placeholder="Tenant Name" name="tenant_name" required>
      <input class="border p-2 w-full" placeholder="Admin Name" name="admin_name" required>
      <input class="border p-2 w-full" placeholder="Admin Email" type="email" name="admin_email" required>
      <input class="border p-2 w-full" placeholder="Admin Password" type="password" name="admin_password" required>
      <div class="grid grid-cols-2 gap-3">
        <input class="border p-2 w-full" placeholder="Purchase Code (optional)" name="purchase_code">
        <input class="border p-2 w-full" placeholder="Domain (example.com)" name="domain">
      </div>
      <button class="bg-black text-white px-4 py-2 rounded">Finish</button>
    </form>
  </div>
</div>

<script>
const jsonHeaders = {
  'Accept': 'application/json',
  'X-Requested-With': 'XMLHttpRequest'
  // CSRF tidak wajib untuk /install/* karena sudah di-exempt di routes.
};

async function postForm(url, formData) {
  const res = await fetch(url, { method: 'POST', body: formData, headers: jsonHeaders });
  const ct = res.headers.get('content-type') || '';
  // Jika server balas HTML (error page), tampilkan potongan teks agar kebaca
  if (!ct.includes('application/json')) {
    const txt = await res.text();
    throw new Error(`HTTP ${res.status} — ${txt.slice(0, 400)}`);
  }
  const j = await res.json();
  if (!res.ok || (j && j.ok === false)) {
    throw new Error(j?.error || (j?.errors ? JSON.stringify(j.errors) : 'Request failed'));
  }
  return j;
}

document.getElementById('envForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  try {
    const fd = new FormData(e.target);
    const j = await postForm('/install/environment', fd);
    if (j.ok) {
      document.getElementById('step2').classList.remove('hidden');
      window.scrollTo(0, document.getElementById('step2').offsetTop);
    }
  } catch (err) { alert(err.message); }
});

document.getElementById('migrateBtn').addEventListener('click', async () => {
  try {
    const j = await postForm('/install/migrate', new FormData());
    document.getElementById('migOut').textContent = (j.output || '').toString();
    if (j.ok) {
      document.getElementById('step3').classList.remove('hidden');
      window.scrollTo(0, document.getElementById('step3').offsetTop);
    }
  } catch (err) { alert(err.message); }
});

document.getElementById('adminForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  try {
    const fd = new FormData(e.target);
    const j = await postForm('/install/admin', fd);
    if (j.ok) {
      window.location.href = '/install/done';
    }
  } catch (err) { alert(err.message); }
});
</script>
</body>
</html>
