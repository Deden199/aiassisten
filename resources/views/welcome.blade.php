@extends('layouts.app')

@section('title', 'AI Assistant — Study & Presentation SaaS')

@section('content')
 {{-- HERO --}}
<section class="relative overflow-hidden bg-gradient-to-br from-violet-600 via-fuchsia-500 to-rose-600 text-white">
  {{-- dekorasi (tanpa z-index negatif) --}}
  <div class="pointer-events-none absolute -top-28 -left-16 h-72 w-72 rounded-full blur-3xl opacity-30 bg-white/30"></div>
  <div class="pointer-events-none absolute -bottom-24 -right-10 h-96 w-96 rounded-full blur-3xl opacity-30 bg-white/20"></div>

  <div class="max-w-7xl mx-auto px-6 lg:px-8 py-20 text-center">
    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 ring-1 ring-white/20 text-sm">
      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 3v18m9-9H3"/></svg>
      New: Auto-slides, Mindmap JSON & Multilingual Output
    </span>

    <h1 class="mt-6 text-4xl sm:text-6xl font-extrabold leading-tight">
      Turn Papers into Slides <span class="text-yellow-300">in Minutes</span>
    </h1>

    <p class="mt-4 text-lg sm:text-xl opacity-95 max-w-3xl mx-auto">
      Upload your course materials, get clean summaries, structured bullet points, mindmaps, and export-ready PPT —
      all powered by an AI tuned for academics.
    </p>

    <div class="mt-8 flex flex-wrap justify-center gap-4">
      <a href="{{ route('register') }}" class="px-7 py-3 rounded-xl bg-white text-gray-900 font-semibold shadow-lg hover:shadow-xl transition">
        Get Started Free
      </a>
      <a href="{{ route('login') }}" class="px-7 py-3 rounded-xl bg-white/10 ring-1 ring-white/30 hover:bg-white/15 transition font-semibold">
        I already have an account
      </a>
    </div>
  </div>
</section>

  <!-- Key Benefits -->
  <section class="py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
      <h2 class="text-3xl sm:text-4xl font-bold text-center">Built for Students, Teams, and Busy Professionals</h2>
      <p class="mt-3 text-gray-600 text-center max-w-2xl mx-auto">
        Opinionated design. Academic tone. Real outcomes. Stop formatting slides all night.
      </p>

      <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        <div class="bg-white rounded-2xl p-6 shadow hover:shadow-lg transition">
          <div class="w-11 h-11 rounded-xl bg-violet-100 flex items-center justify-center">
            <svg class="w-6 h-6 text-violet-600" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path d="M4 7h16M4 12h16M4 17h10"/>
            </svg>
          </div>
          <h3 class="mt-4 font-semibold text-lg">Smart Summaries</h3>
          <p class="mt-2 text-gray-600">Concise abstracts, key bullets, and a mini glossary tailored for your topic.</p>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow hover:shadow-lg transition">
          <div class="w-11 h-11 rounded-xl bg-fuchsia-100 flex items-center justify-center">
            <svg class="w-6 h-6 text-fuchsia-600" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path d="M12 6v12M6 12h12"/>
            </svg>
          </div>
          <h3 class="mt-4 font-semibold text-lg">Auto Mindmaps</h3>
          <p class="mt-2 text-gray-600">Clear JSON structure rendered into a beautiful, exportable mindmap.</p>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow hover:shadow-lg transition">
          <div class="w-11 h-11 rounded-xl bg-rose-100 flex items-center justify-center">
            <svg class="w-6 h-6 text-rose-600" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path d="M6 4h12v6H6zM8 14h8v6H8z"/>
            </svg>
          </div>
          <h3 class="mt-4 font-semibold text-lg">Slides in One Click</h3>
          <p class="mt-2 text-gray-600">Academic slide templates with speaker notes. RTL and multilingual supported.</p>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow hover:shadow-lg transition">
          <div class="w-11 h-11 rounded-xl bg-amber-100 flex items-center justify-center">
            <svg class="w-6 h-6 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path d="M4 7h16M7 4v16"/>
            </svg>
          </div>
          <h3 class="mt-4 font-semibold text-lg">Citation-friendly</h3>
          <p class="mt-2 text-gray-600">Keep meaning intact. Paraphrase cleanly and format citations your way.</p>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow hover:shadow-lg transition">
          <div class="w-11 h-11 rounded-xl bg-emerald-100 flex items-center justify-center">
            <svg class="w-6 h-6 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path d="M5 12l5 5L20 7"/>
            </svg>
          </div>
          <h3 class="mt-4 font-semibold text-lg">Export-Ready</h3>
          <p class="mt-2 text-gray-600">Download PPTX, PDF, or PNG. Share instantly with your team.</p>
        </div>
        <div class="bg-white rounded-2xl p-6 shadow hover:shadow-lg transition">
          <div class="w-11 h-11 rounded-xl bg-sky-100 flex items-center justify-center">
            <svg class="w-6 h-6 text-sky-600" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path d="M12 3v18m9-9H3"/>
            </svg>
          </div>
          <h3 class="mt-4 font-semibold text-lg">Global by Default</h3>
          <p class="mt-2 text-gray-600">12+ locales, RTL layouts, and multi-currency pricing out-of-the-box.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- How It Works -->
  <section class="py-20">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
      <h2 class="text-3xl sm:text-4xl font-bold text-center">How it works</h2>
      <div class="mt-12 grid gap-6 md:grid-cols-3">
        <div class="rounded-2xl p-6 bg-gradient-to-br from-slate-50 to-white border">
          <div class="text-sm font-semibold text-slate-500">Step 1</div>
          <h3 class="mt-1 font-semibold text-lg">Upload</h3>
          <p class="mt-2 text-slate-600">Add your PDF, DOCX, or PPT. We parse safely on server queues.</p>
        </div>
        <div class="rounded-2xl p-6 bg-gradient-to-br from-slate-50 to-white border">
          <div class="text-sm font-semibold text-slate-500">Step 2</div>
          <h3 class="mt-1 font-semibold text-lg">Choose Output</h3>
          <p class="mt-2 text-slate-600">Summary, bullets, mindmap, or slides. Pick target language and tone.</p>
        </div>
        <div class="rounded-2xl p-6 bg-gradient-to-br from-slate-50 to-white border">
          <div class="text-sm font-semibold text-slate-500">Step 3</div>
          <h3 class="mt-1 font-semibold text-lg">Download</h3>
          <p class="mt-2 text-slate-600">Get clean results with export-ready files and speaker notes.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Showcase -->
  <section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
      <div class="grid lg:grid-cols-2 gap-10 items-center">
        <div>
          <h2 class="text-3xl font-bold">Slides you’d actually present</h2>
          <p class="mt-3 text-gray-600">
            Our PPT engine maps content into well-structured decks. No weird line breaks. No chaotic fonts.
            Just clean academic slides with optional RTL and multilingual templates.
          </p>
          <div class="mt-6 flex gap-3">
            <a href="{{ route('register') }}" class="px-6 py-3 rounded-xl bg-violet-600 text-white font-semibold hover:bg-violet-700 transition">
              Try the Demo
            </a>
            <a href="#pricing" class="px-6 py-3 rounded-xl border font-semibold hover:bg-gray-50 transition">
              See Pricing
            </a>
          </div>
        </div>
        <div class="relative">
          <div class="aspect-[16/10] rounded-2xl bg-gradient-to-br from-violet-100 via-fuchsia-100 to-rose-100 border shadow-inner"></div>
          <div class="absolute -bottom-6 -left-6 w-32 h-32 rounded-2xl bg-white shadow-lg border flex items-center justify-center">
            <span class="text-sm font-semibold">Mindmap JSON</span>
          </div>
          <div class="absolute -top-6 -right-6 w-32 h-32 rounded-2xl bg-white shadow-lg border flex items-center justify-center">
            <span class="text-sm font-semibold">PPTX Export</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Pricing -->
  <section id="pricing" class="py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 text-center">
      <h2 class="text-3xl sm:text-4xl font-bold">Simple, student-friendly pricing</h2>
      <p class="mt-2 text-gray-600">Start free. Upgrade when you need more credits and exports.</p>

      <div class="mt-12 grid gap-6 md:grid-cols-3">
        <div class="bg-white rounded-2xl p-6 shadow border">
          <h3 class="font-semibold">Free</h3>
          <p class="mt-2 text-3xl font-extrabold">$0</p>
          <ul class="mt-4 space-y-2 text-gray-600 text-left">
            <li>• 50 monthly credits</li>
            <li>• Summary & bullets</li>
            <li>• Basic mindmap</li>
          </ul>
          <a href="{{ route('register') }}" class="mt-6 inline-block px-5 py-2 rounded-xl bg-gray-900 text-white font-semibold hover:bg-black transition">
            Get Free
          </a>
        </div>

        <div class="bg-gradient-to-br from-violet-600 to-fuchsia-600 text-white rounded-2xl p-6 shadow-lg ring-1 ring-violet-400/30">
          <span class="inline-block text-xs uppercase tracking-wider bg-white/20 px-3 py-1 rounded-full">Most Popular</span>
          <h3 class="mt-2 font-semibold">Pro</h3>
          <p class="mt-2 text-3xl font-extrabold">$9.90</p>
          <ul class="mt-4 space-y-2 text-left">
            <li>• 1,000 monthly credits</li>
            <li>• Slides with speaker notes</li>
            <li>• Multilingual outputs</li>
          </ul>
          <a href="{{ route('register') }}" class="mt-6 inline-block px-5 py-2 rounded-xl bg-white text-gray-900 font-semibold hover:bg-white/90 transition">
            Upgrade Now
          </a>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow border">
          <h3 class="font-semibold">Team</h3>
          <p class="mt-2 text-3xl font-extrabold">$29</p>
          <ul class="mt-4 space-y-2 text-gray-600 text-left">
            <li>• 5 seats included</li>
            <li>• Shared projects</li>
            <li>• Priority processing</li>
          </ul>
          <a href="{{ route('register') }}" class="mt-6 inline-block px-5 py-2 rounded-xl bg-gray-900 text-white font-semibold hover:bg-black transition">
            Start Team
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Testimonials -->
  <section class="py-20">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
      <div class="grid md:grid-cols-3 gap-6">
        <div class="rounded-2xl p-6 bg-white shadow border">
          <p class="text-gray-700">“It turned my 40-page reading into ten clean slides with notes. Saved my group.”</p>
          <div class="mt-4 text-sm text-gray-500">— Hana, Economics Student</div>
        </div>
        <div class="rounded-2xl p-6 bg-white shadow border">
          <p class="text-gray-700">“Mindmap JSON + PPT export is a killer combo for client workshops.”</p>
          <div class="mt-4 text-sm text-gray-500">— Rob, Agency Owner</div>
        </div>
        <div class="rounded-2xl p-6 bg-white shadow border">
          <p class="text-gray-700">“Multilingual output helped me present in Arabic and English seamlessly.”</p>
          <div class="mt-4 text-sm text-gray-500">— Layla, MBA</div>
        </div>
      </div>
    </div>
  </section>

  <!-- FAQ -->
  <section class="py-20 bg-gray-50">
    <div class="max-w-5xl mx-auto px-6 lg:px-8">
      <h2 class="text-3xl font-bold text-center">Frequently Asked Questions</h2>
      <div class="mt-10 space-y-4" x-data="{open:null}">
        <div class="bg-white rounded-xl border overflow-hidden">
          <button class="w-full text-left px-5 py-4 font-semibold flex justify-between items-center" @click="open = open===1 ? null : 1">
            How do credits work?
            <span x-show="open!==1">＋</span><span x-show="open===1">－</span>
          </button>
          <div class="px-5 pb-5 text-gray-600" x-show="open===1">Each AI action consumes credits; Pro includes 1,000 per month.</div>
        </div>
        <div class="bg-white rounded-xl border overflow-hidden">
          <button class="w-full text-left px-5 py-4 font-semibold flex justify-between items-center" @click="open = open===2 ? null : 2">
            Can I export PPT with speaker notes?
            <span x-show="open!==2">＋</span><span x-show="open===2">－</span>
          </button>
          <div class="px-5 pb-5 text-gray-600" x-show="open===2">Yes — notes are generated slide-by-slide with academic tone.</div>
        </div>
        <div class="bg-white rounded-xl border overflow-hidden">
          <button class="w-full text-left px-5 py-4 font-semibold flex justify-between items-center" @click="open = open===3 ? null : 3">
            Is RTL supported?
            <span x-show="open!==3">＋</span><span x-show="open===3">－</span>
          </button>
          <div class="px-5 pb-5 text-gray-600" x-show="open===3">Fully. The UI flips and slide templates switch to RTL automatically.</div>
        </div>
      </div>
    </div>
  </section>

  <!-- Final CTA -->
  <section class="py-16 text-center relative overflow-hidden">
    <div class="absolute inset-0 -z-10 bg-gradient-to-r from-violet-600 via-fuchsia-600 to-rose-600 opacity-90"></div>
    <div class="max-w-3xl mx-auto px-6 text-white">
      <h2 class="text-3xl sm:text-4xl font-extrabold">Ready to ship better presentations?</h2>
      <p class="mt-3 opacity-95">Join thousands of students and teams who automate the boring parts.</p>
      <div class="mt-8 flex justify-center gap-4">
        <a href="{{ route('register') }}" class="px-7 py-3 rounded-xl bg-white text-gray-900 font-semibold shadow-lg hover:shadow-xl transition">
          Create Free Account
        </a>
        <a href="#pricing" class="px-7 py-3 rounded-xl bg-white/10 ring-1 ring-white/30 hover:bg-white/15 transition font-semibold">
          See Pricing
        </a>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="py-10 bg-white border-t">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-gray-500">
      <div>© {{ date('Y') }} AI Assistant. All rights reserved.</div>
      <div class="flex items-center gap-4">
        <a href="#" class="hover:underline">Privacy</a>
        <a href="#" class="hover:underline">Terms</a>
        <a href="{{ route('login') }}" class="hover:underline">Sign in</a>
      </div>
    </div>
  </footer>
@endsection
