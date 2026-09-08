=== Feuer Nursing Review ===
Contributors: your-wporg-username
Tags: nclex, nursing, education, quiz, learning-management-system
Requires at least: 6.4
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A WordPress-based NCLEX learning platform for managing courses, lessons, quizzes, NGN assessments, student progress, and performance analytics.

== Description ==

Feuer Nursing Review is a WordPress plugin designed to provide a complete learning and assessment platform for NCLEX and Next Generation NCLEX (NGN) preparation.

The plugin provides structured course content, interactive assessments, student progress tracking, question management, scoring, rationales, and performance analytics.

The system is designed specifically for WordPress and uses custom post types, taxonomies, database tables, REST API endpoints, and modern frontend components.

= Core Features =

* Course and lesson management
* Reusable NCLEX question bank
* Standard NCLEX quiz engine
* Next Generation NCLEX (NGN) question types
* Interactive unfolding case studies
* Student progress tracking
* Quiz attempt and question-level analytics
* Instructor performance reports
* Content drip and course access controls
* REST API-powered progress tracking
* Gutenberg and frontend component support
* WooCommerce integration support
* LMS integration hooks

= Content Structure =

The plugin uses custom post types to organize learning content:

* `fnr_course` - Course container
* `fnr_lesson` - Individual course lesson
* `fnr_quiz` - Quiz configuration and settings
* `fnr_question` - Reusable question bank items

Custom taxonomies include:

* `fnr_subject`
* `fnr_nclex_category`

= Assessment Engine =

The assessment engine supports multiple question formats, including:

* Multiple Choice
* Select All That Apply (SATA)
* Numeric Calculation
* Ordered Response
* Hotspot
* Matrix / Grid
* Drop-Down / Cloze
* Drag-and-Drop Matching
* Highlight Passages
* Bow-Tie
* Dyad and Triad items

The plugin also supports different assessment modes, including practice and simulated exam experiences.

= NGN Support =

The plugin is designed to support Next Generation NCLEX assessments and case studies based on the Clinical Judgment Measurement Model.

NGN functionality includes:

* Multi-step unfolding case studies
* Clinical information tabs
* Nurses' Notes
* Vital Signs
* Laboratory Results
* History and Physical information
* Sequential case study progression
* NGN-specific scoring rules

= Student Progress =

Student activity can be tracked using dedicated database tables for high-frequency learning and assessment data.

Tracked information may include:

* Lesson completion
* Course progress
* Quiz attempts
* Quiz scores
* Time spent
* Question responses
* Points earned
* NCLEX category performance

= Analytics =

Student and instructor analytics provide insight into learning performance.

Student analytics can identify weaker NCLEX Client Need areas, while instructor reports can identify questions and topics with higher error rates.

== Installation ==

1. Upload the `feuer-nursing-review` directory to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Configure the plugin settings from the WordPress admin area.
4. Create courses, lessons, quizzes, and questions as required.
5. Assign appropriate roles and course access to students.

== Frequently Asked Questions ==

= What version of WordPress is required? =

The plugin requires WordPress 6.4 or later.

= What version of PHP is required? =

PHP 8.1 or later is recommended.

= Does the plugin support NGN questions? =

Yes. The plugin architecture is designed to support multiple Next Generation NCLEX question types and scoring methods.

= Does the plugin track student progress? =

Yes. Student lesson completion, quiz attempts, scores, and question-level performance can be tracked.

= Does the plugin require WooCommerce? =

No. WooCommerce is an optional integration intended for selling course access.

= Does the plugin require a separate LMS? =

No. The plugin is designed to provide its own course and assessment functionality while also allowing integrations with existing LMS platforms.

== Screenshots ==

1. Course management interface.
2. Quiz builder.
3. Student course dashboard.
4. Interactive quiz interface.
5. NGN case study interface.
6. Student performance analytics.
7. Instructor analytics dashboard.

== Changelog ==

= 0.1.0 =
* Initial plugin architecture.
* Added custom post type structure.
* Added custom taxonomy structure.
* Added initial database architecture.
* Added initial role and capability structure.
* Added foundational course and assessment architecture.

== Upgrade Notice ==

= 0.1.0 =
Initial development release.
