import './bootstrap';

import Alpine from 'alpinejs';

document.addEventListener('alpine:init', () => {
    Alpine.data('taskRunner', () => ({
        items: {},
        timers: {},
        holdOnDoneMs: 2500,
        autoReloadOnDone: true,
        toast: { show: false, msg: '', type: 'info' },
        debug: false,

        run(projectId, type, action, projectTitle = null) {
            if (!action || typeof action !== 'string') {
                this.notice('Action URL missing', 'error');
                return;
            }
            const key = `${projectId}:${type}`;
            const token = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content');

            const init = {
                key,
                projectId,
                type,
                title: projectTitle,
                status: 'queued',
                message: null,
                pollUrl: null,
                finishedAt: null,
            };
            this.items = { ...this.items, [key]: init };

            fetch(action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token || '',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then(async (res) => {
                    if (!res.ok)
                        throw new Error((await res.text()) || 'Request failed');
                    return res.json();
                })
                .then((j) => {
                    const st = j.status || 'queued';
                    const pollUrl =
                        j.poll_url ||
                        (j.task_id
                            ? `${window.location.origin}/projects/${projectId}/tasks/${j.task_id}`
                            : null);
                    if (!pollUrl) throw new Error('Poll URL missing');
                    this.items = {
                        ...this.items,
                        [key]: {
                            ...this.items[key],
                            status: st === 'succeeded' ? 'done' : st,
                            pollUrl,
                        },
                    };
                    this.notice('Queued: ' + type.toUpperCase());
                    this.poll(key);
                    this.timers[key] = setInterval(
                        () => this.poll(key),
                        1200,
                    );
                })
                .catch((e) => {
                    this.items = {
                        ...this.items,
                        [key]: {
                            ...this.items[key],
                            status: 'failed',
                            message: e.message,
                            finishedAt: Date.now(),
                        },
                    };
                    this.notice(e.message, 'error');
                });
        },

        async poll(key) {
            const it = this.items[key];
            if (!it?.pollUrl) return;
            try {
                const r = await fetch(it.pollUrl, {
                    headers: { Accept: 'application/json' },
                });
                if (!r.ok) throw new Error('Polling error');
                const j = await r.json();

                const st = j.status || it.status;
                const upd = {
                    ...it,
                    status: st === 'succeeded' ? 'done' : st,
                    message: j.message || it.message,
                };
                this.items = { ...this.items, [key]: upd };

                if (upd.status === 'done' || upd.status === 'failed') {
                    this.stop(key);
                    this.items = {
                        ...this.items,
                        [key]: {
                            ...this.items[key],
                            finishedAt: Date.now(),
                        },
                    };
                    this.notice(
                        upd.status === 'done'
                            ? 'Completed'
                            : upd.message || 'Failed',
                        upd.status === 'done' ? 'success' : 'error',
                    );

                    setTimeout(() => {
                        const current = this.items[key];
                        if (current && current.finishedAt) {
                            const { [key]: _, ...rest } = this.items;
                            this.items = rest;
                            if (this.autoReloadOnDone) window.location.reload();
                        }
                    }, this.holdOnDoneMs);
                }
            } catch (e) {
                this.items = {
                    ...this.items,
                    [key]: {
                        ...it,
                        status: 'failed',
                        message: e.message,
                        finishedAt: Date.now(),
                    },
                };
                this.stop(key);
                this.notice(e.message, 'error');
            }
        },

        stop(key) {
            if (this.timers[key]) {
                clearInterval(this.timers[key]);
                delete this.timers[key];
            }
        },

        isPending(projectId, type) {
            const k = `${projectId}:${type}`;
            return ['queued', 'running'].includes(this.items[k]?.status);
        },

        status(projectId) {
            const i = Object.values(this.items).find(
                (i) =>
                    i.projectId === projectId &&
                    ['queued', 'running'].includes(i.status),
            );
            return i?.status || null;
        },

        visibleList() {
            const now = Date.now();
            return Object.values(this.items).filter((i) => {
                if (['queued', 'running'].includes(i.status)) return true;
                if (
                    ['done', 'failed'].includes(i.status) &&
                    i.finishedAt &&
                    now - i.finishedAt < this.holdOnDoneMs
                )
                    return true;
                return false;
            });
        },

        badgeClass(st) {
            return {
                'bg-yellow-100 text-yellow-800': st === 'queued',
                'bg-blue-100 text-blue-800': st === 'running',
                'bg-green-100 text-green-800': st === 'done',
                'bg-red-100 text-red-800': st === 'failed',
                'bg-gray-100 text-gray-800': !st,
            };
        },

        notice(msg, type = 'info') {
            this.toast = { show: true, msg, type };
            setTimeout(() => (this.toast.show = false), 1400);
        },
    }));
});

window.Alpine = Alpine;

Alpine.start();
