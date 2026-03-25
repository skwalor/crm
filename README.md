# IntePros Federal Celios.AI CRM

A full-featured CRM (Customer Relationship Management) application built with PHP and vanilla JavaScript.

## Features

- **Dashboard** - Overview cards and quick stats
- **Contacts** - Manage contacts with interaction notes and related tasks
- **Proposals** - Track proposals through their lifecycle
- **Opportunities** - Pipeline management with notes and tasks
- **Kanban Board** - Visual task management with drag-and-drop
  - Filter by user, date range (day/week/month), proposal, contact, opportunity, or priority
  - Status columns: To Do, In Progress, Done
- **Events** - Event tracking with filters and sub-tabs
- **Jira Integration** - OAuth-based Jira board sync with kanban and list views
- **User Management** - Authentication, roles, and profile management
- **Admin Panel** - User administration

## Tech Stack

- **Backend:** PHP
- **Frontend:** HTML, CSS, vanilla JavaScript
- **Database:** MySQL (via PDO)
- **Authentication:** Session-based with CSRF protection
- **Integrations:** Atlassian Jira (OAuth 2.0)

## Project Structure

```
├── index.php                 # Main application (SPA-style)
├── login.php                 # Login page
├── register.php              # Registration page
├── profile.php               # User profile
├── admin.php                 # Admin panel
├── api.php                   # REST API endpoints
├── jira_api.php              # Jira API proxy
├── jira_oauth_callback.php   # Jira OAuth callback handler
├── db_connect.php            # Database connection
├── includes/
│   ├── functions.php         # Shared utility functions
│   ├── session_config.php    # Session configuration
│   ├── JiraAPI.php           # Jira API client class
│   ├── jira_config.php       # Jira OAuth credentials
│   └── env_loader.php        # Environment variable loader
├── js/
│   └── jira_module.js        # Jira board UI module
├── jira_module.js            # Jira module (root copy)
└── uploads/                  # File uploads directory
```

## Setup

1. Configure your database connection in `db_connect.php`
2. Set up Jira OAuth credentials in `includes/jira_config.php` (optional)
3. Ensure the `uploads/` directory is writable
4. Serve with Apache/Nginx with PHP support

## Security

- CSRF token protection on all mutating requests
- Session hardening with secure cookie settings
- XSS prevention via output escaping
- Parameterized database queries (PDO)
