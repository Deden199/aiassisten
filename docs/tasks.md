# Task Endpoints

The application queues AI tasks such as summaries, mindmaps, and slides for a project.

## Mindmap

`/projects/{project}/tasks/mindmap`

Accepts **GET** and **POST** requests.

- **GET**: Displays a page describing the endpoint.
- **POST**: Queues a mindmap generation task for the project. Include the CSRF token when submitting the form.
