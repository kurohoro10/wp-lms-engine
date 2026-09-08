# Feuer Nursing Review

A comprehensive WordPress plugin built to power the Feuer Nursing Review learning platform. It provides course management, lesson delivery, NCLEX and Next Generation NCLEX (NGN) assessments, student progress tracking, performance analytics, and instructor reporting.

## Features

### Course Management

* Custom Course post type
* Lesson management and course hierarchy
* Module and lesson organization
* Content drip functionality
* Student course access management
* Support for Gutenberg-based content editing

### Quiz Engine

* Multiple Choice questions
* Select All That Apply (SATA)
* Numeric calculation questions
* Ordered response questions
* Hotspot questions
* Practice and simulated exam modes
* Instant feedback and rationale display
* Configurable time limits and passing scores

### Next Generation NCLEX (NGN)

* Matrix and grid questions
* Drop-down Cloze questions
* Drag-and-drop matching
* Highlight passages and tables
* Bow-Tie questions
* Dyad and Triad items
* Unfolding case studies
* Support for the 6-step NCSBN Clinical Judgment Measurement Model (NCJMM)

### Scoring

The plugin supports multiple scoring methods depending on the question type:

* **0/1 Scoring** — Full credit for correct answers
* **Plus/Minus Scoring** — Correct selections add points while incorrect selections deduct points, with scores floored at zero
* **Rationale Scoring** — Linked responses must meet the required criteria to receive points

### Student Progress

* Lesson completion tracking
* Course progress monitoring
* Quiz attempt history
* Time spent tracking
* Question-level response logging
* Performance tracking by NCLEX category
* Access and drip availability tracking

### Analytics

#### Student Dashboard

Students can view their learning progress and identify weak areas based on quiz and question performance.

#### Instructor Dashboard

Instructors can review aggregate student performance, including:

* Question error rates
* Student quiz performance
* Weak NCLEX categories
* Course and lesson progress

## Content Structure

The plugin uses the following custom post types:

| Post Type      | Description                     |
| -------------- | ------------------------------- |
| `fnr_course`   | Main review course container    |
| `fnr_lesson`   | Individual course lesson        |
| `fnr_quiz`     | Quiz configuration and settings |
| `fnr_question` | Reusable question bank item     |

### Taxonomies

* `fnr_subject`
* `fnr_nclex_category`

Example subjects include:

* Fundamentals
* Pharmacology
* Medical-Surgical Nursing

Example NCLEX categories include:

* Management of Care
* Safety and Infection Control
* Pharmacological Therapies

## Database Tables

The plugin uses dedicated database tables for high-frequency student activity and analytics.

```text
{prefix}fnr_user_progress
{prefix}fnr_quiz_attempts
{prefix}fnr_question_logs
```

Tables are created and updated using WordPress `dbDelta()` during plugin activation.

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
* Track their learning progress

## REST API

The plugin uses custom WordPress REST API endpoints for asynchronous functionality.

Example namespace:

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
* LearnDash compatibility
* LifterLMS compatibility
* Vimeo video embeds
* YouTube video embeds

## Requirements

* WordPress 6.x or later
* PHP 8.1 or later
* MySQL 8.0+ or MariaDB equivalent
* Modern browser with JavaScript enabled

## Installation

1. Download or clone the plugin repository.
2. Place the plugin inside the WordPress plugins directory:

```text
wp-content/plugins/feuer-nursing-review/
```

3. Activate the plugin from **WordPress Admin → Plugins**.
4. Plugin activation will register the required roles, capabilities, custom post types, and database tables.
5. Configure the plugin settings from the Feuer Nursing Review admin menu.

## Development

### Suggested Structure

```text
feuer-nursing-review/
├── feuer-nursing-review.php
├── readme.md
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

Frontend and admin interfaces should follow WordPress accessibility best practices.

This includes:

* Keyboard-accessible interactive components
* Visible focus states
* Proper form labels
* Semantic HTML
* ARIA attributes only when necessary
* Accessible error and validation messages
* Screen reader announcements for dynamic quiz feedback
* Support for `prefers-reduced-motion`

## Security

All plugin development should follow WordPress security best practices.

This includes:

* Sanitizing input
* Escaping output
* Verifying nonces for administrative and user actions
* Checking user capabilities before protected actions
* Validating REST API permissions
* Using prepared database queries where appropriate
* Preventing unauthorized access to course and student data

## Development Status

This project is currently under active development.

### Planned Development Phases

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
* [ ] NGN scoring engine
* [ ] Unfolding case studies
* [ ] Student analytics
* [ ] Instructor analytics
* [ ] WooCommerce integration
* [ ] LMS integration

## License

Private / Proprietary

This plugin is developed specifically for Feuer Nursing Review. Redistribution, modification, or use outside the intended project requires permission from the project owner.
