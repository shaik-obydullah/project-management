# ProjectFlow

ProjectFlow is a full-featured project management application built with [Laravel](https://laravel.com) and [Vue.js](https://vuejs.org). It provides an intuitive platform for managing organizations, projects, issues, sprints, and team collaboration.

## Features

- **Organizations** - Create and manage organizations with team members and role-based access control
- **Projects** - Organize work into projects with custom keys, colors, and member permissions
- **Kanban Boards** - Drag-and-drop boards with customizable columns for visual workflow management
- **Issue Tracking** - Create, assign, and track issues with types, priorities, estimates, due dates, and subtasks
- **Sprints** - Plan and execute sprints with start/complete functionality and issue assignment
- **Labels** - Categorize issues with customizable labels
- **Comments & Attachments** - Collaborate on issues with comments and file attachments
- **Notifications** - Stay updated with in-app notifications
- **Search** - Full-text search across issues and projects
- **Dashboard** - Overview of assigned issues, recent projects, and activity statistics
- **Activity Logging** - Track changes with Spatie Activity Log

## Tech Stack

### Backend
- [Laravel 13](https://laravel.com) - PHP web framework
- [Laravel Sanctum](https://laravel.com/docs/sanctum) - API authentication
- [Laravel Scout](https://laravel.com/docs/scout) - Full-text search
- [Spatie Activity Log](https://spatie.be/docs/laravel-activitylog) - Activity tracking
- PostgreSQL - Primary database
- Redis - Caching, sessions, and queues

### Frontend
- [Vue 3](https://vuejs.org) - Progressive JavaScript framework
- [Vue Router](https://router.vuejs.org) - Client-side routing
- [Pinia](https://pinia.vuejs.org) - State management
- [Tailwind CSS 4](https://tailwindcss.com) - Utility-first CSS framework
- [Vite](https://vitejs.dev) - Build tool and dev server
- [Vue Draggable Plus](https://vue-draggable-plus.pages.dev) - Drag and drop functionality
- [ApexCharts](https://apexcharts.com) - Data visualization

## Requirements

- PHP 8.3+
- Composer
- Node.js & npm
- Docker & Docker Compose (for containerized setup)

## Installation

### Using Docker (Recommended)

1. Clone the repository:
```bash
git clone <repository-url>
cd project-management
```

2. Start the Docker containers:
```bash
docker-compose up -d
```

3. Install dependencies and set up the application:
```bash
docker-compose exec php-fpm composer install
docker-compose exec php-fpm cp .env.example .env
docker-compose exec php-fpm php artisan key:generate
docker-compose exec php-fpm php artisan migrate
docker-compose exec node npm install
```

4. Access the application:
- Web: [http://localhost:8082](http://localhost:8082)
- Vite Dev Server: [http://localhost:5173](http://localhost:5173)

### Local Development

1. Clone the repository and install dependencies:
```bash
composer install
npm install
```

2. Set up environment:
```bash
cp .env.example .env
php artisan key:generate
```

3. Configure your database in `.env` (SQLite is configured by default)

4. Run migrations:
```bash
php artisan migrate
```

5. Start the development servers:
```bash
# Terminal 1 - Laravel server
php artisan serve

# Terminal 2 - Vite dev server
npm run dev
```

Or use the combined dev command:
```bash
composer dev
```

## API

The application provides a RESTful API under `/api/v1` with endpoints for:
- Authentication (register, login, logout)
- Organizations and members
- Projects and members
- Boards, columns, and issues
- Sprints
- Labels, comments, and attachments
- Search, notifications, and dashboard

All protected routes require authentication via Laravel Sanctum.

## Testing

Run the test suite:
```bash
php artisan test
```

Or with composer:
```bash
composer test
```

## License

The ProjectFlow application is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
