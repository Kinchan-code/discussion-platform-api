# Community-Powered Protocol & Discussion Platform API

A robust Laravel REST API for a community-driven platform where users can share wellness protocols, create discussion threads, and engage through comments, reviews, and voting.

## 🚀 Features

-   **Protocols**: Structured wellness instructions with tags, ratings, and author attribution
-   **Threads & Comments**: Discussion topics with multi-level nested replies
-   **Reviews & Voting**: Star ratings, feedback, and upvote/downvote system (one vote per user)
-   **User Profiles**: Statistics and activity tracking
-   **Search**: Full-text, faceted search with Typesense Cloud & Laravel Scout
-   **Authentication**: Laravel Sanctum token-based authentication
-   **Clean Architecture**: Service layer, DTOs, and standardized API responses

## 🏗️ Tech Stack

-   **Framework**: Laravel 12.x (PHP 8.2+)
-   **Database**: MySQL 8.0+
-   **Search**: Typesense Cloud via Laravel Scout
-   **Auth**: Laravel Sanctum
-   **Testing**: PHPUnit

## ⚡ Quick Start

1. **Clone & Install**

    ```bash
    git clone <repository-url>
    cd discussion-platform-api
    composer install
    npm install
    ```

2. **Environment Setup**

    ```bash
    cp .env.example .env
    php artisan key:generate
    # Edit .env for DB and Typesense credentials
    ```

3. **Database & Seed**

    ```bash
    php artisan migrate
    php artisan db:seed
    ```

4. **(Optional) Search Index**

    ```bash
    php artisan scout:import "App\\Models\\Protocol"
    php artisan scout:import "App\\Models\\Thread"
    ```

5. **Run Server**
    ```bash
    php artisan serve
    # API at http://localhost:8000
    ```

## 📚 API Overview

**Authentication**

-   `POST /api/register` — Register new user
-   `POST /api/login` — Login user
-   `POST /api/logout` — Logout user (auth required)
-   `GET /api/me` — Get current user (auth required)

**Profile**

-   `GET /api/profile` — Get current user profile (auth required)
-   `PUT /api/profile` — Update profile (auth required)
-   `GET /api/profile/statistics` — User activity stats (auth required)
-   `GET /api/profile/replies` — User’s replies (auth required)
-   `GET /api/profile/comments` — User’s comments (auth required)
-   `GET /api/profile/reviews` — User’s reviews (auth required)

**Protocols**

-   `GET /api/protocols` — List protocols (filter/sort supported)
-   `GET /api/protocols/featured` — Featured protocols
-   `GET /api/protocols/filters` — Protocol filters
-   `GET /api/protocols/{id}` — Get protocol
-   `GET /api/protocols/{id}/stats` — Protocol stats
-   `POST /api/protocols` — Create protocol (auth required)
-   `PUT /api/protocols/{id}` — Update protocol (auth required)
-   `DELETE /api/protocols/{id}` — Delete protocol (auth required)

**Threads**

-   `GET /api/threads` — List threads (filter/sort supported)
-   `GET /api/threads/trending` — Trending threads
-   `GET /api/threads/{id}` — Get thread
-   `GET /api/threads/{id}/stats` — Thread stats
-   `GET /api/protocols/{protocol}/threads` — Threads by protocol
-   `POST /api/threads` — Create thread (auth required)
-   `PUT /api/threads/{id}` — Update thread (auth required)
-   `DELETE /api/threads/{id}` — Delete thread (auth required)

**Comments**

-   `GET /api/threads/{thread}/comments` — Get thread comments
-   `GET /api/comments/{comment}/replies` — Get comment replies
-   `GET /api/replies/{reply}/nested` — Get nested replies
-   `POST /api/threads/{thread}/comments` — Create comment (auth required)
-   `POST /api/comments/{comment}/reply` — Reply to comment (auth required)
-   `POST /api/replies/{reply}/reply` — Reply to reply (auth required)
-   `PUT /api/comments/{comment}` — Update comment (auth required)
-   `DELETE /api/comments/{comment}` — Delete comment (auth required)

**Reviews**

-   `GET /api/protocols/{protocol}/reviews` — Get protocol reviews
-   `POST /api/protocols/{protocol}/reviews` — Create review (auth required)
-   `DELETE /api/reviews/{id}` — Delete review (auth required)

**Voting**

-   `POST /api/threads/{thread}/vote` — Vote on thread (auth required)
-   `POST /api/comments/{comment}/vote` — Vote on comment (auth required)
-   `POST /api/reviews/{review}/vote` — Vote on review (auth required)

**Tags & Analytics**

-   `GET /api/tags/popular` — Popular tags
-   `GET /api/stats/dashboard` — Platform statistics

---

All endpoints return standardized JSON. Most write actions require authentication (Bearer token).

## 🧪 Testing

```bash
php artisan test
```

**For questions or feedback, feel free to reach out.**
