@props(['project', 'task'])

<div x-data="{
    url: '{{ route('tasks.show', [$project, $task]) }}',
    status: null,
    version: null,
    result: {},
    // Poll until task finishes. Consider caching trimmed payload for faster access.
    poll() {
        fetch(this.url)
            .then(r => r.json())
            .then(d => {
                this.status = d.status;
                if (d.status === 'succeeded' && d.versions.length > 0) {
                    this.version = d.versions[0];
                    let raw = this.version.payload?.[0]?.raw;
                    let content = raw?.choices?.[0]?.message?.content || raw?.content?.[0]?.text || '';
                    try {
                        this.result = JSON.parse(content);
                    } catch (e) {
                        this.result = { summary: content };
                    }
                } else if (d.status !== 'failed') {
                    setTimeout(() => this.poll(), 2000);
                }
            });
    }
}" x-init="poll" class="space-y-2">
    <template x-if="status === 'succeeded' && result.summary">
        <pre class="whitespace-pre-wrap text-sm bg-gray-50 p-2 rounded" x-text="result.summary"></pre>
    </template>
    <template x-if="status === 'succeeded' && result.mindmap">
        <ul class="list-disc ml-5 text-sm">
            <template x-for="(item, i) in result.mindmap" :key="i">
                <li x-text="item"></li>
            </template>
        </ul>
    </template>
    <template x-if="status === 'succeeded' && version && version.file_path">
        <a :href="'/versions/' + version.id + '/download'" class="text-sm text-violet-600 underline">Download slides</a>
    </template>
    <template x-if="status && status !== 'succeeded'">
        <div class="text-sm text-gray-500" x-text="status + '...'" />
    </template>
</div>
