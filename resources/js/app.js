import './bootstrap';

import Alpine from 'alpinejs';
import axios from 'axios';

export function pollTaskStatus(taskId, callback, interval = 2000) {
    const poll = async () => {
        try {
            const { data } = await axios.get(`/tasks/${taskId}`);
            callback(data);
            if (["queued", "running"].includes(data.status)) {
                setTimeout(poll, interval);
            }
        } catch (e) {
            callback({ status: 'failed' });
        }
    };
    poll();
}

document.addEventListener('alpine:init', () => {
    Alpine.data('taskRunner', () => ({
        statuses: {},
        isPending(projectId, type) {
            const s = this.statuses[`${projectId}_${type}`];
            return ["loading", "queued", "running"].includes(s);
        },
        async run(projectId, type, url) {
            const key = `${projectId}_${type}`;
            this.statuses[key] = 'loading';
            try {
                const { data } = await axios.post(url);
                const taskId = data.id;
                pollTaskStatus(taskId, (res) => {
                    this.statuses[key] = res.status;
                });
            } catch (e) {
                this.statuses[key] = 'failed';
            }
        }
    }));
});

window.Alpine = Alpine;

Alpine.start();
