# Queue Workers

The application offloads long-running work to the queue. `ProcessAiTask` and PPTX export jobs run asynchronously and require a Redis-backed queue.

## Redis

A sample `docker-compose.yml` is provided to start Redis locally:

```bash
docker compose up -d redis
```

Set `QUEUE_CONNECTION=redis` in your `.env` file and ensure Redis is reachable at `REDIS_HOST`.

## Supervisor

Use Supervisor (or systemd) to keep workers running:

```ini
[program:aiassisten-worker]
process_name=%(program_name)s_%(process_num)02d
command=php artisan queue:work redis --sleep=3 --tries=3
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/aiassisten-worker.log
```

Reload Supervisor after creating the config:

```bash
supervisorctl reread
supervisorctl update
```

## Horizon

For monitoring and balancing, install [Laravel Horizon](https://laravel.com/docs/horizon) and run:

```bash
php artisan horizon
```

Supervisor can manage Horizon as well:

```ini
[program:aiassisten-horizon]
command=php artisan horizon
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/aiassisten-horizon.log
```

With these workers running, `ProcessAiTask` and PPTX export jobs are processed in the background.
