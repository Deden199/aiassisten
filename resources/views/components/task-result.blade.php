@props(['project', 'task'])

<div x-data="{
    url: '{{ route('tasks.show', [$project, $task]) }}',
    status: null,
    message: null,
    version: null,
    versions: [],
    result: {},
    downloadUrl: null,
    openPreview: null,
    // Poll until task finishes. Consider caching trimmed payload for faster access.
    poll() {
        fetch(this.url)
            .then(r => r.json())
            .then(d => {
                this.status = d.status;
                this.message = d.message;
                if (d.status === 'done') {
                    this.versions = d.versions.map(v => {
                        let content = v.payload?.content || '';
                        let raw = v.payload?.chunks?.[0]?.raw;
                        if (!content && raw) {
                            content = raw?.choices?.[0]?.message?.content || raw?.content?.[0]?.text || '';
                        }
                        let parsed = {};
                        try {
                            parsed = content ? JSON.parse(content) : {};
                        } catch (e) {
                            parsed = { title: content };
                        }
                        return { ...v, parsed };
                    });
                    if (this.versions.length > 0) {
                        this.version = this.versions[0];
                        this.result = this.versions[0].parsed;
                        this.openPreview = `preview-version-${this.versions[0].id}`;
                    }
                    this.downloadUrl = d.download_url;
                    return;
                }
                if (d.status === 'failed') return;
                setTimeout(() => this.poll(), 2000);
            });
    }
}" x-init="poll" class="space-y-2">
    <template x-if="status === 'done' && result.summary">
        <pre class="whitespace-pre-wrap text-sm bg-gray-50 dark:bg-gray-800 dark:text-gray-200 p-2 rounded" x-text="result.summary"></pre>
    </template>
    <template x-if="status === 'done' && result.mindmap">
        <ul class="list-disc ml-5 text-sm text-gray-700 dark:text-gray-300">
            <template x-for="(item, i) in result.mindmap" :key="i">
                <li x-text="item"></li>
            </template>
        </ul>
    </template>
    <template x-if="status === 'done' && downloadUrl">
        <a :href="downloadUrl" class="text-sm text-violet-600 dark:text-violet-400 underline">Download slides</a>
    </template>
    <template x-if="status === 'done' && versions.length">
        <div class="space-y-2">
            <template x-for="v in versions" :key="v.id">
                <div class="border rounded p-2">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium" x-text="v.name || ('Version ' + v.id)"></span>
                        <div class="flex items-center gap-2">
                            <button
                                class="text-xs text-blue-600 dark:text-blue-400 underline"
                                @click="openPreview = openPreview === `preview-version-${v.id}` ? null : `preview-version-${v.id}`"
                            >Preview</button>
                            <template x-if="v.download_url">
                                <a :href="v.download_url" class="text-xs text-violet-600 dark:text-violet-400 underline">Download PPTX</a>
                            </template>
                        </div>
                    </div>
                    <div x-show="openPreview === `preview-version-${v.id}`" :id="`preview-version-${v.id}`" class="mt-2">
                        <h4 class="text-sm font-semibold" x-text="v.parsed.title"></h4>
                        <ul class="list-disc ml-4 text-sm text-gray-700 dark:text-gray-300">
                            <template x-for="(b, i) in v.parsed.bullets" :key="i">
                                <li x-text="b"></li>
                            </template>
                        </ul>
                    </div>
                </div>
            </template>
        </div>
    </template>
    <template x-if="status && status !== 'done'">
        <div class="flex flex-col gap-1">
            <div class="flex items-center gap-2">
                <svg x-show="['queued','running'].includes(status)" class="h-4 w-4 animate-spin text-gray-400 dark:text-gray-500" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" fill="none"/></svg>
                <span class="text-sm" :class="status === 'failed' ? 'text-rose-600 dark:text-rose-400' : 'text-gray-600 dark:text-gray-300'" x-text="status + (message ? ': ' + message : '')"></span>
            </div>
            <div class="w-full h-2 bg-gray-100 dark:bg-gray-700 rounded overflow-hidden" x-show="['queued','running'].includes(status)">
                <div class="h-full w-1/2 bg-blue-400 dark:bg-blue-500 animate-[indeterminate_1.2s_ease_infinite]"></div>
            </div>
        </div>
    </template>
</div>
