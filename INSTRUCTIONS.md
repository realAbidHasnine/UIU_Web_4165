# SkillMatch Backend Implementation Guide (Spring Boot 3.x + MySQL)

This step-by-step instruction guide is tailored specifically to your existing **SkillMatch** frontend codebase. Every endpoint, request payload, query parameter, and JSON response field in this document was verified against the active `fetch()` calls across your HTML pages.

---

## 1. Prerequisites

Ensure the following runtimes and developer tools are installed before initializing the project:

| Tool | Recommended Version | Verification Command | Purpose |
|---|---|---|---|
| **Java Development Kit (JDK)** | OpenJDK 17 or Eclipse Temurin 17 | `java -version` | Core Java runtime (Spring Boot 3 baseline) |
| **Build Tool** | Apache Maven 3.9+ (or use Maven Wrapper) | `mvn -version` | Dependency management & compilation |
| **Relational Database** | MySQL Community Server 8.0+ | `mysql --version` | Persistent data storage |
| **Database GUI Client** | MySQL Workbench, DBeaver, or phpMyAdmin | N/A | Visual schema inspection & query execution |
| **API Testing Tool** | Postman, Insomnia, or Thunder Client | N/A | Endpoint verification prior to frontend integration |
| **Browser** | Chrome / Edge / Firefox | N/A | DevTools Network Tab inspection |

---

## 2. Project Scaffolding & Setup

### A. Spring Initializr Configuration
Visit [start.spring.io](https://start.spring.io) and configure the project with these parameters:

- **Project**: Maven
- **Language**: Java
- **Spring Boot**: 3.3.x (latest stable 3.x release)
- **Project Metadata**:
  - **Group**: `com.skillmatch`
  - **Artifact**: `backend` (or `skillmatch-api`)
  - **Name**: `skillmatch-api`
  - **Package name**: `com.skillmatch`
  - **Packaging**: Jar
  - **Java**: 17
- **Dependencies**:
  1. `Spring Web` (`spring-boot-starter-web`) — REST controllers & Jackson JSON mapping
  2. `Spring Data JPA` (`spring-boot-starter-data-jpa`) — Hibernate ORM & JPA Repositories
  3. `MySQL Driver` (`mysql-connector-j`) — JDBC driver for MySQL
  4. `Validation` (`spring-boot-starter-validation`) — Request payload validation (`@NotNull`, `@NotBlank`)
  5. `Lombok` (Optional, recommended for brevity if preferred)

Extract the downloaded ZIP directly alongside or inside your project repository (e.g. `c:\Users\USER\Desktop\Web 262\UIU_Web_4165\backend`).

---

### B. Database Initialization
Create the dedicated MySQL database before starting Spring Boot:

```sql
CREATE DATABASE IF NOT EXISTS skillmatch_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

---

### C. Configuration (`application.properties`)
Open `src/main/resources/application.properties` and add the exact settings below:

```properties
# Server Port (matches frontend API_BASE: http://localhost:8080/api)
server.port=8080

# MySQL Database Connection
spring.datasource.url=jdbc:mysql://localhost:3306/skillmatch_db?useSSL=false&serverTimezone=UTC&allowPublicKeyRetrieval=true
spring.datasource.username=root
spring.datasource.password=your_mysql_password
spring.datasource.driver-class-name=com.mysql.cj.jdbc.Driver

# JPA / Hibernate Configuration
spring.jpa.hibernate.ddl-auto=update
spring.jpa.show-sql=true
spring.jpa.properties.hibernate.format_sql=true
spring.jpa.properties.hibernate.dialect=org.hibernate.dialect.MySQLDialect

# Multipart / File Upload Settings (for deliverables)
spring.servlet.multipart.max-file-size=25MB
spring.servlet.multipart.max-request-size=30MB

# JSON Serialization: do not fail on unknown frontend properties
spring.jackson.deserialization.fail-on-unknown-properties=false
```

---

## 3. CORS Setup

Because your frontend files are served via `file:///` protocols or local dev ports (e.g. `http://localhost:5500`, `http://127.0.0.1:5500`), the browser will enforce Cross-Origin Resource Sharing restrictions.

Create a configuration class `com.skillmatch.config.CorsConfig` implementing `WebMvcConfigurer`:
- Apply cross-origin access across all path patterns: `/**`.
- Set allowed origins to allow `*` (or explicit patterns like `http://localhost:*`, `http://127.0.0.1:*`, `null` for local file access).
- Allow all standard HTTP methods: `GET`, `POST`, `PUT`, `DELETE`, `OPTIONS`.
- Allow all headers: `Content-Type`, `Accept`, `Authorization`, `X-Requested-With`.
- Set `maxAge(3600)` to cache preflight OPTIONS requests for 1 hour.

*Why this works:* This prevents browser console errors like `CORS policy: No 'Access-Control-Allow-Origin' header is present on the requested resource` when your HTML pages send `fetch()` calls.

---

## 4. Recommended Package Structure

Structure your backend using a clean, layered architecture:

```
com.skillmatch
├── SkillMatchApplication.java
├── config
│   └── CorsConfig.java
├── controller
│   ├── AuthController.java
│   ├── JobController.java
│   ├── ChatController.java
│   ├── FreelancerController.java
│   ├── SkillController.java
│   ├── ClientController.java
│   └── AdminController.java
├── dto
│   ├── request/          <-- Java Records for @RequestBody
│   │   ├── LoginRequest.java
│   │   ├── JobCreateRequest.java
│   │   ├── ProposalSubmitRequest.java
│   │   ├── ChatMessageRequest.java
│   │   ├── ProfileUpdateRequest.java
│   │   ├── PortfolioCreateRequest.java
│   │   ├── SkillTestSubmitRequest.java
│   │   └── UserStatusUpdateRequest.java
│   └── response/         <-- Java Records returned by Controllers
│       ├── AuthResponse.java
│       ├── JobResponse.java
│       ├── ProposalResponse.java
│       ├── ChatThreadResponse.java
│       ├── ChatMessageResponse.java
│       ├── FreelancerProfileResponse.java
│       ├── PortfolioResponse.java
│       ├── SkillCategoryResponse.java
│       ├── SkillQuestionResponse.java
│       ├── SkillTestResultResponse.java
│       ├── ClientDashboardResponse.java
│       ├── ClientProjectResponse.java
│       ├── AdminMetricsResponse.java
│       └── AdminUserResponse.java
├── entity
│   ├── User.java
│   ├── Job.java
│   ├── Proposal.java
│   ├── ChatThread.java
│   ├── ChatMessage.java
│   ├── PortfolioItem.java
│   ├── SkillCategory.java
│   ├── SkillQuestion.java
│   ├── TestResult.java
│   └── Deliverable.java
├── repository
│   ├── UserRepository.java
│   ├── JobRepository.java
│   ├── ProposalRepository.java
│   ├── ChatThreadRepository.java
│   ├── ChatMessageRepository.java
│   ├── PortfolioRepository.java
│   ├── SkillCategoryRepository.java
│   ├── SkillQuestionRepository.java
│   ├── TestResultRepository.java
│   └── DeliverableRepository.java
└── service
    ├── AuthService.java
    ├── JobService.java
    ├── ChatService.java
    ├── FreelancerService.java
    ├── SkillService.java
    ├── ClientService.java
    └── AdminService.java
```

---

## 5. Entity Model (Derived from Frontend JSON Contracts)

Entities must represent the normalized relational schema in MySQL. DTOs will map between these entities and the frontend JSON shapes.

### 1. `User` (Parent Account Entity)
- `id` (Long, PK, Auto Increment)
- `email` (String, Unique, Not Null)
- `password` (String, Not Null)
- `role` (Enum/String: `FREELANCER`, `CLIENT`, `ADMIN`)
- `name` (String)
- `title` (String, e.g. "Senior Frontend Specialist")
- `company` (String, for Clients)
- `hourlyRate` (Double/Integer)
- `bio` (Text)
- `status` (String: `Active`, `Suspended`, `Flagged`)
- `score` (Integer, overall test benchmark, default 90+)
- `rating` (Double, default 4.9)
- `earnings` (Double, default 0.0)
- `completedJobs` (Integer, default 0)
- `location` (String, default "Remote")
- `joinedDate` (LocalDate/String, e.g. "Jun 12, 2026")
- Relationships:
  - `@OneToMany` with `Job` (posted jobs if client)
  - `@OneToMany` with `Proposal` (submitted bids if freelancer)
  - `@OneToMany` with `PortfolioItem`

### 2. `Job` (Marketplace Project Postings)
- `id` (Long, PK, Auto Increment)
- `title` (String, Not Null)
- `category` (String: `web`, `mobile`, `uiux`, `cloud`, `writing`, `data`)
- `budget` (Double / String, e.g. 4500)
- `budgetType` (String: `fixed`, `hourly`)
- `budgetDisplay` (String: "$4,500 Fixed")
- `duration` (String: "1-3-months")
- `durationDisplay` (String: "1-3 Months")
- `level` (String: "Expert", "Intermediate")
- `description` (Text)
- `company` (String, snapshot or derived from client User)
- `location` (String, default "Remote")
- `status` (String: `Open`, `In Progress`, `Completed`, `Awaiting Approval`)
- `postedDate` (LocalDateTime / String)
- `dueDate` (String, optional)
- Relationships:
  - `@ManyToOne` with `User` (`client_id`)
  - `@ManyToOne` with `User` (`hired_freelancer_id`, nullable)
  - `@ElementCollection` for `skills` (List of Strings, e.g. `["React", "TypeScript"]`)
  - `@ElementCollection` for `responsibilities` (List of Strings)
  - `@OneToMany` with `Proposal`

### 3. `Proposal` (Freelancer Bid on a Job)
- `id` (Long, PK, Auto Increment)
- `proposedRate` (Double, Not Null)
- `estimatedDays` (Integer, Not Null)
- `coverLetter` (Text)
- `status` (String: `Submitted`, `Active`, `Archived`, `Accepted`)
- `submittedAt` (LocalDateTime)
- Relationships:
  - `@ManyToOne` with `Job` (`job_id`)
  - `@ManyToOne` with `User` (`freelancer_id`)

### 4. `ChatThread` & `ChatMessage` (Messaging System)
- **`ChatThread`**:
  - `id` (Long, PK, Auto Increment)
  - `lastMessage` (String)
  - `lastMessageTime` (LocalDateTime / String)
  - Relationships:
    - `@ManyToOne` with `User` (`client_id`)
    - `@ManyToOne` with `User` (`freelancer_id`)
    - `@OneToMany` with `ChatMessage`
- **`ChatMessage`**:
  - `id` (Long, PK, Auto Increment)
  - `text` (Text, Not Null)
  - `timestamp` (LocalDateTime)
  - `isRead` (Boolean, default false)
  - Relationships:
    - `@ManyToOne` with `ChatThread` (`thread_id`)
    - `@ManyToOne` with `User` (`sender_id`)

### 5. `PortfolioItem` (Freelancer Showcase)
- `id` (Long, PK, Auto Increment)
- `title` (String)
- `category` (String)
- `url` (String)
- `description` (Text)
- Relationships:
  - `@ManyToOne` with `User` (`freelancer_id`)

### 6. `SkillCategory` & `SkillQuestion` (Assessment Engine)
- **`SkillCategory`**:
  - `id` (String / Long, e.g. `web`, `uiux`, `mobile`, `cloud`)
  - `name` (String, e.g. "Web Development")
  - `icon` (String, e.g. "💻")
  - `difficulty` (String, e.g. "Intermediate - Expert")
  - `questionCount` (Integer)
- **`SkillQuestion`**:
  - `id` (Long, PK, Auto Increment)
  - `category` (String / FK to `SkillCategory`)
  - `question` (Text)
  - `correctOptionIndex` (Integer, 0-indexed)
  - `@ElementCollection` for `options` (List of 4 Strings)

### 7. `TestResult` (Grades & Verified Certifications)
- `id` (Long, PK, Auto Increment)
- `category` (String)
- `score` (Integer, percentage 0-100)
- `passed` (Boolean, score >= 70)
- `correctCount` (Integer)
- `totalCount` (Integer)
- `verifiedBadge` (String, e.g. "Verified Pro")
- `submittedAt` (LocalDateTime)
- Relationships:
  - `@ManyToOne` with `User` (`freelancer_id`)

### 8. `Deliverable` (Project Submission Files & Notes)
- `id` (Long, PK, Auto Increment)
- `notes` (Text)
- `filePaths` (String / List of uploaded filenames)
- `submittedAt` (LocalDateTime)
- Relationships:
  - `@ManyToOne` with `Job` (`job_id`)
  - `@ManyToOne` with `User` (`freelancer_id`)

---

## 6. Suggested Build Order & Step-by-Step Roadmap

Follow this order to ensure foundational entities and services are ready before dependent features are implemented:

```
[Phase 1: Foundation]
  1. CorsConfig & GlobalExceptionHandler
  2. User entity & UserRepository
  3. AuthController (POST /api/auth/login)
           │
           ▼
[Phase 2: Core Marketplace]
  4. Job & Proposal entities & Repositories
  5. JobController (GET /api/jobs, POST /api/jobs, POST /api/jobs/{id}/proposals)
  6. ClientController (GET /api/clients/me/dashboard, GET /api/clients/me/projects)
           │
           ▼
[Phase 3: Freelancer Profiles & Showcase]
  7. FreelancerController (GET /api/freelancers, GET /api/freelancers/{id}, me, portfolio)
  8. Proposal withdrawal (DELETE /api/proposals/{id})
  9. Deliverables upload (POST /api/projects/{id}/deliverables)
           │
           ▼
[Phase 4: Skill Assessment Engine]
  10. SkillCategory & SkillQuestion entities & seed data
  11. SkillController (categories, test delivery, submit & grading, latest result)
           │
           ▼
[Phase 5: Real-Time Chat & Collaboration]
  12. ChatThread & ChatMessage entities
  13. ChatController (threads, message history, live send)
           │
           ▼
[Phase 6: Platform Administration]
  14. AdminController (GET /api/admin/metrics, GET /api/admin/users, POST status toggle)
```

*Reasoning:* 
- Authentication provides the mock/session identity for testing `me` endpoints.
- Jobs and Proposals form the central relational hub between Clients and Freelancers.
- Standalone features (Skills and Chat) can be developed independently once Users exist.
- Admin metrics aggregate data from Users and Jobs, so building it last ensures there is real data to calculate.

---

## 7. Complete Endpoint-by-Endpoint Checklist

All request and response shapes below reflect the exact JSON fields consumed and produced by your frontend JavaScript.

### Phase 1: Authentication (`AuthController`)

- [ ] **`POST /api/auth/login`**
  - **Invoking Pages**: `guest/login.html`, `guest/admin_login.html`
  - **Request Body (JSON)**:
    ```json
    {
      "email": "sarah.jenkins@example.com",
      "password": "password123",
      "role": "FREELANCER"
    }
    ```
    *(Note: `role` will be `"FREELANCER"`, `"CLIENT"`, or `"ADMIN"`)*
  - **Response (HTTP 200 OK)**:
    ```json
    {
      "id": "f-101",
      "name": "Sarah Jenkins",
      "email": "sarah.jenkins@example.com",
      "role": "FREELANCER",
      "title": "Senior Frontend & React Specialist",
      "company": "Apex Capital Partners",
      "hourlyRate": 65,
      "token": "session-token-abc-123"
    }
    ```

---

### Phase 2: Jobs & Project Management (`JobController` & `ClientController`)

- [ ] **`GET /api/jobs`**
  - **Invoking Pages**: `freelancer/job_listing.html`, `guest/public_job_listing.html`
  - **Query Parameters (Optional)**:
    - `category`: comma-separated string (e.g. `web,mobile`)
    - `budget`: string (e.g. `1000-3000`, `3000-5000`, `5000-plus`, or `fixed`)
    - `duration`: string (e.g. `1-3-months`, `3-6-months`)
  - **Response (HTTP 200 OK - Array of Jobs)**:
    ```json
    [
      {
        "id": "job-101",
        "title": "Full-Stack SaaS Dashboard & Analytics",
        "category": "web",
        "budget": 4500,
        "budgetType": "fixed",
        "budgetDisplay": "$4,500 Fixed",
        "duration": "1-3-months",
        "durationDisplay": "1-3 Months",
        "level": "Expert",
        "desc": "Looking for an engineer to architect our React analytics interface.",
        "skills": ["React", "TypeScript", "Spring Boot", "REST API"],
        "company": "Apex Global Tech",
        "location": "Remote",
        "posted": "2 hours ago",
        "proposalsCount": 4,
        "suggestedRate": 3000,
        "responsibilities": [
          "Deliver clean, documented code",
          "Participate in sprint check-ins"
        ]
      }
    ]
    ```

- [ ] **`POST /api/jobs`**
  - **Invoking Page**: `client/post-project-review.html`
  - **Request Body (JSON)**:
    ```json
    {
      "title": "E-commerce Redesign and Migration",
      "category": "web",
      "budgetType": "fixed",
      "budget": "4500",
      "budgetDisplay": "$4,500 Fixed",
      "duration": "1-3-months",
      "durationDisplay": "1-3 Months",
      "level": "Expert",
      "skills": ["React", "TypeScript", "UI Design"],
      "desc": "Complete redesign and headless cloud migration with performance benchmarking."
    }
    ```
  - **Response (HTTP 201 Created)**:
    ```json
    {
      "id": "job-105",
      "title": "E-commerce Redesign and Migration",
      "status": "Open",
      "posted": "Just now"
    }
    ```

- [ ] **`POST /api/jobs/{id}/proposals`**
  - **Invoking Page**: `freelancer/job_listing.html`
  - **Path Parameter**: `{id}` (e.g. `job-101`)
  - **Request Body (JSON)**:
    ```json
    {
      "jobId": "job-101",
      "jobTitle": "Full-Stack SaaS Dashboard & Analytics",
      "proposedRate": 3200,
      "estimatedDays": 14,
      "coverLetter": "I have extensive experience building Spring Boot and React dashboards."
    }
    ```
  - **Response (HTTP 201 Created)**:
    ```json
    {
      "id": "prop-501",
      "status": "Submitted",
      "message": "Proposal submitted successfully"
    }
    ```

- [ ] **`GET /api/clients/me/dashboard`**
  - **Invoking Page**: `client/client-dashboard.html`
  - **Response (HTTP 200 OK)**:
    ```json
    {
      "clientName": "Abida Hasan",
      "activeProjects": 4,
      "proposalsReceived": 12,
      "freelancersHired": 8,
      "totalSpent": "$12,450"
    }
    ```

- [ ] **`GET /api/clients/me/projects`**
  - **Invoking Pages**: `client/client-dashboard.html`, `client/my-projects.html`
  - **Query Parameter (Optional)**: `status` (e.g. `all`, `open`, `in progress`, `completed`)
  - **Response (HTTP 200 OK - Array of Projects)**:
    ```json
    [
      {
        "id": "proj-201",
        "title": "Healthcare Patient Portal Frontend",
        "status": "In Progress",
        "budget": "$4,500",
        "budgetType": "Fixed",
        "proposalsCount": 8,
        "freelancer": "Sabbir Hossain",
        "postedDate": "3 days ago",
        "dueDate": "Due in 18 days"
      }
    ]
    ```

---

### Phase 3: Freelancer Profiles, Proposals & Deliverables (`FreelancerController`)

- [ ] **`GET /api/freelancers`**
  - **Invoking Pages**: `guest/browse_freelancer.html`, `client/browse-freelancers.html`, `index.html`, `guest/landing.html`
  - **Response (HTTP 200 OK - Array of Verified Freelancers)**:
    ```json
    [
      {
        "id": "fl-1",
        "name": "Abid Hasnine",
        "title": "Full Stack Engineer & Cloud Architect",
        "score": 96,
        "verifiedScore": 96,
        "skills": ["Spring Boot", "React", "Docker", "PostgreSQL"],
        "rating": 5.0,
        "completedProjects": 48,
        "completedJobs": 48,
        "hourlyRate": 75,
        "category": "web-development",
        "verifiedBadge": "Verified Pro"
      }
    ]
    ```

- [ ] **`GET /api/freelancers/{id}`**
  - **Invoking Pages**: `guest/freelancer_public_profile.html`, `client/freelancer-profile.html`
  - **Path Parameter**: `{id}` (e.g. `fl-1` or `f-101`)
  - **Response (HTTP 200 OK)**:
    ```json
    {
      "id": "fl-1",
      "name": "Sarah Jenkins",
      "title": "Senior Frontend & React Specialist",
      "hourlyRate": 65,
      "rating": 4.95,
      "score": 94,
      "completedProjects": 34,
      "location": "Remote",
      "skills": ["React", "TypeScript", "TailwindCSS", "Spring Boot", "REST APIs"]
    }
    ```

- [ ] **`GET /api/freelancers/me`**
  - **Invoking Pages**: `freelancer/profile.html`, `freelancer/index.html`
  - **Response (HTTP 200 OK)**:
    ```json
    {
      "id": "f-101",
      "name": "Sarah Jenkins",
      "email": "sarah.jenkins@example.com",
      "title": "Senior Frontend & React Specialist",
      "hourlyRate": 65,
      "bio": "Over 6 years of experience building modern web apps.",
      "earnings": 28450,
      "completedJobs": 34,
      "verifiedScore": 94
    }
    ```

- [ ] **`PUT /api/freelancers/me`**
  - **Invoking Page**: `freelancer/profile.html`
  - **Request Body (JSON)**:
    ```json
    {
      "name": "Sarah Jenkins",
      "email": "sarah.jenkins@example.com",
      "title": "Principal Frontend Architect",
      "hourlyRate": 75,
      "bio": "Updated biography details."
    }
    ```
  - **Response (HTTP 200 OK)**:
    ```json
    {
      "status": "success",
      "message": "Profile updated successfully"
    }
    ```

- [ ] **`GET /api/freelancers/me/proposals`**
  - **Invoking Pages**: `freelancer/my_proposals.html`, `freelancer/index.html`
  - **Query Parameter (Optional)**: `status` (e.g. `all`, `active`, `submitted`, `archived`)
  - **Response (HTTP 200 OK - Array of Proposals)**:
    ```json
    [
      {
        "id": "prop-101",
        "jobId": "job-101",
        "jobTitle": "SaaS Platform Frontend Architecture",
        "clientName": "Apex Capital Partners",
        "status": "Active",
        "coverLetter": "Proposed full modular component build.",
        "proposedRate": 3400,
        "estimatedDays": 14
      }
    ]
    ```

- [ ] **`DELETE /api/proposals/{id}`** *(Discovered in `freelancer/my_proposals.html`)*
  - **Path Parameter**: `{id}` (e.g. `prop-101`)
  - **Response (HTTP 200 OK or 204 No Content)**:
    ```json
    {
      "status": "success",
      "message": "Proposal withdrawn successfully"
    }
    ```

- [ ] **`GET /api/freelancers/me/portfolio`**
  - **Invoking Page**: `freelancer/portfolio_link.html`
  - **Response (HTTP 200 OK - Array of Portfolio Items)**:
    ```json
    [
      {
        "id": "port-1",
        "title": "Fintech Mobile Banking Dashboard",
        "category": "React / TypeScript",
        "url": "https://github.com/example/banking-ui",
        "desc": "Real-time ledger visualizations and crypto exchange engine."
      }
    ]
    ```

- [ ] **`POST /api/freelancers/me/portfolio`** *(Discovered in `freelancer/portfolio_link.html`)*
  - **Request Body (JSON)**:
    ```json
    {
      "title": "Healthcare Telemedicine Portal",
      "category": "Spring Boot / React",
      "url": "https://github.com/example/telehealth",
      "desc": "HIPAA-compliant appointment booking and video calling application."
    }
    ```
  - **Response (HTTP 201 Created)**:
    ```json
    {
      "id": "port-2",
      "title": "Healthcare Telemedicine Portal",
      "category": "Spring Boot / React",
      "url": "https://github.com/example/telehealth",
      "desc": "HIPAA-compliant appointment booking and video calling application."
    }
    ```

- [ ] **`POST /api/projects/{id}/deliverables`** *(Discovered in `freelancer/upload_comp_work.html`)*
  - **Path Parameter**: `{id}` (e.g. `proj-1`)
  - **Request Body**: `multipart/form-data` with:
    - `notes` (text parameter)
    - `files` (uploaded binary files array, optional in local tests)
  - **Response (HTTP 201 Created)**:
    ```json
    {
      "id": "deliv-1",
      "status": "Submitted",
      "message": "Deliverable package submitted successfully for client review"
    }
    ```

---

### Phase 4: Skill Assessment Engine (`SkillController`)

- [ ] **`GET /api/skills/categories`**
  - **Invoking Page**: `freelancer/select_skill.html`
  - **Response (HTTP 200 OK - Array of Categories)**:
    ```json
    [
      {
        "id": "web",
        "name": "Web Development",
        "icon": "💻",
        "questionCount": 5,
        "difficulty": "Intermediate - Expert"
      },
      {
        "id": "uiux",
        "name": "UI / UX Design",
        "icon": "🎨",
        "questionCount": 5,
        "difficulty": "All Levels"
      }
    ]
    ```

- [ ] **`GET /api/skills/tests/{cat}`**
  - **Invoking Page**: `freelancer/skill_test.html`
  - **Path Parameter**: `{cat}` (e.g. `web`, `uiux`, `mobile`)
  - **Response (HTTP 200 OK - Question Bank Array)**:
    ```json
    [
      {
        "id": 1,
        "question": "What is the primary difference between useEffect and useLayoutEffect in React?",
        "options": [
          "useEffect fires before DOM mutations, useLayoutEffect fires asynchronously",
          "useLayoutEffect runs synchronously immediately after DOM mutations, while useEffect runs after paint",
          "There is no functional difference; one is an alias for the other",
          "useLayoutEffect is only supported in Server-Side Rendering environments"
        ],
        "correct": 1
      }
    ]
    ```

- [ ] **`POST /api/skills/tests/{cat}/submit`**
  - **Invoking Page**: `freelancer/skill_test.html`
  - **Path Parameter**: `{cat}` (e.g. `web`)
  - **Request Body (JSON)**:
    ```json
    {
      "category": "web",
      "answers": {
        "1": 1,
        "2": 2,
        "3": 0
      },
      "completedAt": "2026-09-14T14:30:00.000Z"
    }
    ```
  - **Response (HTTP 200 OK)**:
    ```json
    {
      "category": "web",
      "score": 88,
      "passed": true,
      "correctCount": 4,
      "totalCount": 5,
      "verifiedBadge": "Verified Pro",
      "submittedAt": "2026-09-14T14:30:05.000Z"
    }
    ```

- [ ] **`GET /api/skills/tests/results/latest`**
  - **Invoking Page**: `freelancer/result_suggestion.html`
  - **Response (HTTP 200 OK)**:
    ```json
    {
      "category": "web",
      "score": 88,
      "passed": true,
      "correctCount": 4,
      "totalCount": 5,
      "verifiedBadge": "Verified Pro",
      "submittedAt": "2026-09-14T14:30:05.000Z"
    }
    ```

---

### Phase 5: Messaging & Collaboration (`ChatController`)

- [ ] **`GET /api/chat/threads`**
  - **Invoking Pages**: `freelancer/freelancer_chat.html`, `client/client-chat.html`
  - **Response (HTTP 200 OK - Array of Threads)**:
    ```json
    [
      {
        "id": "thread-1",
        "name": "Abida Hasan",
        "role": "Client",
        "time": "10:42 AM",
        "status": "online",
        "unread": true,
        "lastMessage": "Mainly regarding the timeline charts..."
      }
    ]
    ```

- [ ] **`GET /api/chat/threads/{id}/messages`**
  - **Invoking Pages**: `client/client-chat.html`, `freelancer/freelancer_chat.html`
  - **Path Parameter**: `{id}` (e.g. `thread-1`)
  - **Response (HTTP 200 OK - Array of Messages)**:
    ```json
    [
      {
        "id": 1,
        "text": "Hi there! I have reviewed the brief for the dashboard redesign.",
        "time": "10:30 AM",
        "isMe": false
      },
      {
        "id": 2,
        "text": "Great, thanks for taking a look. What specific aspects need clarification?",
        "time": "10:35 AM",
        "isMe": true
      }
    ]
    ```

- [ ] **`POST /api/chat/threads/{id}/messages`**
  - **Invoking Pages**: `client/client-chat.html`, `freelancer/freelancer_chat.html`
  - **Path Parameter**: `{id}` (e.g. `thread-1`)
  - **Request Body (JSON)**:
    ```json
    {
      "text": "Sounds great! I will upload the updated wireframes shortly."
    }
    ```
  - **Response (HTTP 201 Created)**:
    ```json
    {
      "id": 3,
      "text": "Sounds great! I will upload the updated wireframes shortly.",
      "time": "10:45 AM",
      "isMe": true
    }
    ```

---

### Phase 6: Administration & Moderation (`AdminController`)

- [ ] **`GET /api/admin/metrics`**
  - **Invoking Page**: `Admin/html/admin-dashboard.html`
  - **Response (HTTP 200 OK)**:
    ```json
    {
      "totalUsers": 24592,
      "activeFreelancers": 8341,
      "activeClients": 3105,
      "projectsPosted": 1204,
      "platformRevenue": 142500
    }
    ```

- [ ] **`GET /api/admin/users`**
  - **Invoking Page**: `Admin/html/user-management.html`
  - **Response (HTTP 200 OK - Array of Users)**:
    ```json
    [
      {
        "id": "u-1",
        "name": "Nabila Islam",
        "email": "nabilaj@example.com",
        "role": "Freelancer",
        "status": "Active",
        "joined": "Jun 12, 2026"
      },
      {
        "id": "u-2",
        "name": "Apex Capital",
        "email": "david@apexcapital.com",
        "role": "Client",
        "status": "Active",
        "joined": "May 02, 2026"
      }
    ]
    ```

- [ ] **`POST /api/admin/users/{id}/status`**
  - **Invoking Page**: `Admin/html/user-management.html`
  - **Path Parameter**: `{id}` (e.g. `u-1`)
  - **Request Body (JSON)**:
    ```json
    {
      "status": "Suspended"
    }
    ```
  - **Response (HTTP 200 OK)**:
    ```json
    {
      "id": "u-1",
      "status": "Suspended",
      "message": "User status updated successfully"
    }
    ```

---

## 8. DTO & Error-Handling Conventions

### A. Java Records as DTOs
Always use Java 17 `record` definitions for request payloads and response bodies. Records provide immutability, compact syntax, and automatic equals/hashCode/toString methods:

- **Entity-to-DTO rule**: Controllers must **never** return an `@Entity` class directly. Direct entity returns lead to circular references with `@OneToMany` relationships and leak database structures.
- **Null Safety**: When mapping an entity to a response record, use fallback values for null fields (e.g. `user.getBio() != null ? user.getBio() : ""`).

### B. Global Exception Handling (`@RestControllerAdvice`)
Create a central advice class `com.skillmatch.exception.GlobalExceptionHandler`:
- Catch `MethodArgumentNotValidException` to return HTTP 400 Bad Request with field validation errors.
- Catch custom `ResourceNotFoundException` (or `NoSuchElementException`) to return HTTP 404 with `{ "error": "Resource not found" }`.
- Catch generic `Exception` to return HTTP 500 with `{ "error": "Internal server error" }`.
- Ensure all error bodies return JSON so the frontend `res.json()` parser does not throw unexpected HTML token errors.

---

## 9. Manual Testing Workflow

Follow this strict verification cycle for each endpoint:

```
1. Write Service & Controller Method
   ↓
2. Send Request in Postman (http://localhost:8080/api/...)
   ↓
3. Verify HTTP Status (200 OK, 201 Created, 204 No Content)
   ↓
4. Verify JSON Response Body field names match the contract exactly
   ↓
5. Open HTML page in browser & open DevTools (F12) -> Network tab
   ↓
6. Trigger UI action (e.g. click button, submit form, switch tab)
   ↓
7. Confirm status code 200/201 in Network tab with zero console errors
```

---

## 10. Running & Sanity-Checking the Server

### Starting the Server
From the root of your Maven backend folder:
```bash
./mvnw spring-boot:run
```
*(On Windows PowerShell, use `.\mvnw spring-boot:run` or `mvn spring-boot:run`)*

### Quick Sanity Check
Open a terminal or browser and query any public endpoint:
```bash
curl -i http://localhost:8080/api/jobs
```
**Expected Output**:
```http
HTTP/1.1 200 OK
Content-Type: application/json
[ ... JSON Array ... ]
```

---

## 11. Milestones & Demo Checklist

| Milestone | Feature Area | Verifiable Demo Criterion |
|---|---|---|
| **M1: Core Auth** | Login & Sessions | Enter credentials on `login.html`; verify role redirect to client/freelancer portal. |
| **M2: Jobs Feed** | Public & Freelancer Jobs | Open `job_listing.html`; verify real jobs load from MySQL and filters work. |
| **M3: Proposals** | Proposal Submission & Withdrawal | Submit a bid on `job_listing.html`; view on `my_proposals.html` and withdraw it. |
| **M4: Profile & Showcase** | Profile & Portfolio | Edit profile on `profile.html` and add a new link on `portfolio_link.html`; reload to verify persistence. |
| **M5: Assessment** | Skill Test & Certificate | Take a 5-question test on `skill_test.html`; submit and verify score on `result_suggestion.html`. |
| **M6: Messaging** | Client & Freelancer Chat | Send a message on `client-chat.html`; see it appear on `freelancer_chat.html`. |
| **M7: Admin Moderation** | Metrics & Moderation | Open `user-management.html`; click "Suspend" on a user and verify the status badge turns red. |

---

## 12. Common Pitfalls & Inconsistencies Discovered in Frontend Code

During our automated scan of your HTML files, the following field-name differences and endpoint patterns were identified. Pay close attention to these when designing your DTOs:

1. **`completedProjects` vs `completedJobs`**:
   - `guest/browse_freelancer.html`, `guest/freelancer_public_profile.html`, and `index.html` read `fl.completedProjects`.
   - `freelancer/index.html` reads `user.completedJobs`.
   - *Fix in backend*: In your `FreelancerProfileResponse` record, include **both** fields or map both to the same underlying entity counter (`int completedProjects, int completedJobs`).
2. **`score` vs `verifiedScore`**:
   - `guest/browse_freelancer.html` and `freelancer_public_profile.html` read `fl.score`.
   - `freelancer/index.html` reads `user.verifiedScore`.
   - *Fix in backend*: Provide both `score` and `verifiedScore` in the response DTO.
3. **`budget` and `budgetDisplay`**:
   - In `freelancer/job_listing.html`, the card uses `job.budgetDisplay || '$' + job.budget`. When posting a project from `post-project-review.html`, it sends `budget: "4500"` and `budgetDisplay: "$4,500 Fixed"`.
   - *Fix in backend*: Store both or auto-generate `budgetDisplay = "$" + budget + " Fixed"` if null.
4. **Proposal Status Case Sensitivity**:
   - `freelancer/my_proposals.html` query params use lowercase (`status=submitted`, `status=active`, `status=archived`), while the UI badge renders TitleCase (`Submitted`, `Active`, `Archived`).
   - *Fix in backend*: In `ProposalRepository` / `ProposalService`, use `.equalsIgnoreCase()` or a JPA query with `LOWER(p.status) = LOWER(:status)`.
5. **Endpoints called in frontend that were missing from initial contract**:
   - `DELETE /api/proposals/{id}` in `freelancer/my_proposals.html` (Withdraw proposal).
   - `POST /api/freelancers/me/portfolio` in `freelancer/portfolio_link.html` (Add project showcase).
   - `POST /api/projects/{id}/deliverables` in `freelancer/upload_comp_work.html` (Submit completed milestone files).
   - *Fix in backend*: All three are now included in the checklist above.
6. **Chat Thread Message Summary**:
   - `freelancer/freelancer_chat.html` checks `thread.lastMessage || thread.messages[last].text`, whereas `client/client-chat.html` strictly expects `thread.lastMessage`.
   - *Fix in backend*: Always populate the `lastMessage` property in `ChatThreadResponse`.
