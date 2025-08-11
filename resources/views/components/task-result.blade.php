@props(['project', 'task'])

<div x-data="{
    url: '{{ route('tasks.show', [$project, $task]) }}',
    status: null,
    message: null,
    version: null,
    result: {},
    downloadUrl: null,
    // Poll until task finishes. Consider caching trimmed payload for faster access.
    poll() {
        fetch(this.url)
            .then(r => r.json())
            .then(d => {
                this.status = d.status;
                this.message = d.message;
                this.downloadUrl = d.download_url;
                if (d.status === 'done') {
                    if (d.versions.length > 0) {
                        this.version = d.versions[0];
                        let content = this.version.payload?.content || '';
                        let raw = this.version.payload?.chunks?.[0]?.raw;
                        if (!content && raw) {
                            content = raw?.choices?.[0]?.message?.content || raw?.content?.[0]?.text || '';
                        }
                        try {
                            this.result = content ? JSON.parse(content) : {};
                        } catch (e) {
                            this.result = { summary: content };
                        }
                    }
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
