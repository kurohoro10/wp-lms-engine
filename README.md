# Feuer Nursing Review

A WordPress-based learning and assessment platform built for Feuer Nursing Review. The plugin is designed to provide course management, lesson delivery, NCLEX and Next Generation NCLEX (NGN)-style assessments, student progress tracking, performance analytics, and instructor reporting.

## Features

### Course Management

* Custom Course post type
* Lesson management and course hierarchy
* Module and lesson organization
* Content drip functionality
* Student course access management
* Gutenberg-based content editing

### Quiz Engine

The quiz engine is designed to support multiple assessment formats:

* Multiple Choice
* Select All That Apply (SATA)
* Numeric Calculation
* Ordered Response
* Hotspot
* Practice and simulated exam modes
* Instant feedback and rationale display
* Configurable time limits and passing scores

### Next Generation NCLEX (NGN)

The plugin provides functionality for NGN-style practice activities, including interactive question formats, clinical judgment exercises, and unfolding case studies.

Planned NGN functionality includes:

* Matrix and grid questions
* Drop-down Cloze questions
* Drag-and-drop matching
* Highlight passages and tables
* Bow-Tie questions
* Dyad and Triad items
* Unfolding case studies
* Clinical information tabs
* Sequential case study progression
* NGN-style scoring methods
* Case studies aligned with the Clinical Judgment Measurement Model

> Feuer Nursing Review is an independent learning platform and is not affiliated with, endorsed by, or sponsored by the NCSBN.

### Scoring

The assessment engine is designed to support multiple scoring approaches depending on the question type:

* **0/1 Scoring** — Awards full credit for a correct response.
* **Plus/Minus Scoring** — Correct selections add points while incorrect selections deduct points, with the resulting score limited to a minimum of zero.
* **Rationale-Based Scoring** — Applies scoring requirements to linked responses where applicable.

These scoring methods are intended for practice and assessment functionality within the Feuer Nursing Review platform and do not represent the official NCLEX scoring algorithm.

## Student Progress

The plugin is designed to track student learning and assessment activity, including:

* Lesson completion
* Course progress
* Quiz attempt history
* Time spent
* Question-level responses
* Points earned
* Performance by NCLEX category
* Course access and drip availability

## Analytics

### Student Dashboard

Students can view their learning progress and identify areas that may require additional study based on quiz and question performance.

### Instructor Dashboard

Instructors can review aggregate student performance, including:

* Question error rates
* Student quiz performance
* Weak NCLEX categories
* Course and lesson progress

## Content Structure

The plugin uses custom post types to organize learning content.

| Post Type      | Description                     |
| -------------- | ------------------------------- |
| `fnr_course`   | Main review course container    |
| `fnr_lesson`   | Individual course lesson        |
| `fnr_quiz`     | Quiz configuration and settings |
| `fnr_question` | Reusable question bank item     |

### Taxonomies

* `fnr_subject`
* `fnr_nclex_category`

Example subjects:

* Fundamentals
* Pharmacology
* Medical-Surgical Nursing

Example NCLEX categories:

* Management of Care
* Safety and Infection Control
* Pharmacological Therapies

## Database Architecture

The plugin uses dedicated database tables for high-frequency student activity and assessment analytics.

```text
{prefix}fnr_user_progress
{prefix}fnr_quiz_attempts
{prefix}fnr_question_logs
```

Tables are created and maintained using WordPress `dbDelta()` during plugin activation and database updates.

### User Progress

Tracks information such as:

* Lesson completion
* Course progress
* Module availability
* Access dates

### Quiz Attempts

Stores assessment session data such as:

* User ID
* Quiz ID
* Score
* Total points
* Time spent
* Attempt status

### Question Logs

Stores question-level assessment data such as:

* Attempt ID
* Question ID
* User response
* Correctness
* Points earned
* Rationale interaction data

## User Roles

### Instructor

The `fnr_instructor` role is intended for educators managing learning content and reviewing student performance.

Example capabilities include:

* Create and manage courses
* Create and manage questions
* Manage quizzes
* View student reports
* Access aggregate analytics

### Student

The `fnr_student` role is intended for users enrolled in Feuer Nursing Review courses.

Students can:

* Access assigned courses
* Complete lessons
* Take quizzes and assessments
* Review rationales
* Track learning progress

## REST API

The plugin uses custom WordPress REST API endpoints for asynchronous frontend functionality.

API namespace:

```text
/wp-json/fnr/v1/
```

Planned endpoints include functionality for:

* Marking lessons as complete
* Saving quiz progress
* Submitting quiz attempts
* Retrieving question data
* Recording question responses
* Retrieving student progress
* Loading analytics data

## Integrations

Planned integrations include:

* WooCommerce for course access and purchases
* Easy Digital Downloads
* LearnDash integration hooks
* LifterLMS integration hooks
* Vimeo video embeds
* YouTube video embeds

## Requirements

* WordPress 6.4 or later
* PHP 8.1 or later
* MySQL 8.0+ or compatible MariaDB version
* Modern browser with JavaScript enabled

## Installation

1. Download or clone the plugin repository.
2. Place the plugin inside the WordPress plugins directory:

```text
wp-content/plugins/feuer-nursing-review/
```

3. Activate the plugin from **WordPress Admin → Plugins**.
4. Plugin activation will register the required roles, capabilities, custom post types, and database tables.
5. Configure the plugin settings from the Feuer Nursing Review admin area.

## Development

### Suggested Project Structure

```text
feuer-nursing-review/
├── feuer-nursing-review.php
├── README.md
├── readme.txt
├── uninstall.php
├── includes/
│   ├── class-plugin.php
│   ├── class-activator.php
│   ├── class-deactivator.php
│   ├── class-post-types.php
│   ├── class-taxonomies.php
│   ├── class-database.php
│   ├── class-roles.php
│   └── class-rest-api.php
├── admin/
│   ├── class-admin.php
│   └── assets/
├── public/
│   ├── class-public.php
│   └── assets/
├── blocks/
├── templates/
└── languages/
```

## Accessibility

Frontend and administrative interfaces should follow WordPress accessibility best practices.

The plugin should provide:

* Keyboard-accessible interactive components
* Visible focus states
* Proper form labels
* Semantic HTML
* ARIA attributes only when necessary
* Accessible validation and error messages
* Screen reader announcements for dynamic quiz feedback
* Support for `prefers-reduced-motion`

Interactive quiz components should remain usable without relying exclusively on drag-and-drop or pointer interactions.

## Security

All plugin development should follow WordPress security best practices.

This includes:

* Sanitizing user input
* Escaping output
* Verifying nonces for administrative and user actions
* Checking user capabilities before protected operations
* Validating REST API permissions
* Using prepared database queries
* Protecting student and course data
* Preventing unauthorized access to assessment results
* Validating and authorizing all frontend state-changing requests

## Development Status

This project is currently under active development.

### Development Roadmap

* [ ] Plugin architecture and database setup
* [ ] Custom post types and taxonomies
* [ ] User roles and capabilities
* [ ] Course and lesson management
* [ ] Student dashboard
* [ ] Progress tracking
* [ ] Standard quiz engine
* [ ] Question bank
* [ ] Rationale engine
* [ ] NGN question components
* [ ] NGN-style scoring engine
* [ ] Unfolding case studies
* [ ] Student analytics
* [ ] Instructor analytics
* [ ] WooCommerce integration
* [ ] LMS integration

## License

Private / Proprietary

This plugin is developed specifically for Feuer Nursing Review. Redistribution, modification, or use outside the intended project requires permission from the project owner.
