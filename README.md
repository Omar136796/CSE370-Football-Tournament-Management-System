# Football Tournament Management System

A web application for running football tournaments, built with **PHP** and
**MySQL**. Admins manage teams, players, tournaments, matches, match events,
and standings. Fans follow their teams, check fixtures, standings, and
statistics and discuss matches in threaded reviews.

Team project for CSE370 (Database Systems) at BRAC University, Spring 2025.

![Tournament standings](screenshots/user/tournament_standings.png)

## My contribution

This was a two-person project. I built the **admin side**: the admin
dashboard and the pages for managing users, teams, players, tournaments,
matches, match events, and standings, along with the database queries and
frontend for those pages (standings tables, team and player pages, and the
edit/delete/manage controls).

My teammate built the user side: login, logout, registration, profile
updates, following teams, match reviews and ratings, and the comment threads.

## Features

### Admin

| Feature | Details |
|---------|---------|
| Manage users | Add, edit, and delete user and admin accounts |
| Manage teams | Add, edit, and delete teams, and upload team logos |
| Manage players | Add players to teams and edit their details |
| Manage tournaments | Create league, knockout, and group-stage tournaments, and assign teams to them |
| Schedule matches | Schedule matches and record results |
| Update standings | Update the standings table for league and group-stage tournaments |
| Record match events | Goals, assists, yellow and red cards, and substitutions, with the minute they happened |

### User

| Feature | Details |
|---------|---------|
| Teams and players | Browse teams, squads, and player profiles with individual statistics |
| Follow teams | Follow and unfollow teams, and see followed teams on the dashboard |
| Tournaments | View tournament schedules, standings, and results |
| Match details | View the score and a timeline of match events |
| Statistics | Top scorers, top assist providers, and most yellow and red cards, overall or per tournament |
| Reviews | Rate matches from 1 to 5 stars, write reviews, and reply to other users' reviews in threads |

## Database design

The database has 10 tables:

| Table | Purpose |
|-------|---------|
| `users` | Accounts, with a `user_type` of admin or user and bcrypt-hashed passwords |
| `teams` | Team name, logo, founding year, home stadium, and coach |
| `players` | Player details, linked to a team |
| `tournaments` | Tournament name, dates, type (league, knockout, or group stage), and status |
| `tournament_teams` | Many-to-many link between tournaments and teams, with an optional group |
| `matches` | Home and away teams, date, venue, score, status, round, and group |
| `match_events` | Goals, assists, cards, and substitutions in a match, with the minute and a related player |
| `standings` | Played, won, drawn, lost, goals for and against, goal difference, and points per team per tournament |
| `user_follows` | Many-to-many link between users and the teams they follow |
| `match_reviews` | Match reviews and ratings, with a self-referencing `parent_review_id` for threaded replies |

Foreign keys use `ON DELETE CASCADE` where it makes sense, so deleting a
tournament also removes its matches, events, and standings.

### ER diagram

![ER diagram](docs/er_diagram.png)

### Schema diagram

![Schema diagram](docs/schema_diagram.png)

## Tech stack

- **Backend:** PHP with PDO, using prepared statements for database access
- **Database:** MySQL (MariaDB, via XAMPP)
- **Frontend:** HTML and CSS
- **Security:** passwords hashed with `password_hash()` (bcrypt), admin pages restricted with an `isAdmin()` check, and logo uploads restricted to image file types

## How to run

1. Install [XAMPP](https://www.apachefriends.org/) and start **Apache** and **MySQL**.
2. Copy the `football_tournament` folder into XAMPP's `htdocs` folder
   (`C:\xampp\htdocs\` on Windows, `/Applications/XAMPP/htdocs/` on macOS).
3. Open phpMyAdmin at `http://localhost/phpmyadmin` and import
   `football_tournament/database.sql`. It creates the `football_tournament`
   database, all the tables, and two sample accounts.
4. Make sure the `football_tournament/uploads` folder is writable, so team
   logos can be uploaded.
5. Open `http://localhost/football_tournament/` and log in with a sample account:

| Role | Username | Password |
|------|----------|----------|
| Admin | `admin` | `admin123` |
| User | `user1` | `user123` |

The database connection settings are in `football_tournament/config.php`
and default to XAMPP's `root` user with no password.

## Screenshots

### Admin view

| Admin dashboard | Manage teams |
|-----------------|--------------|
| ![Admin dashboard](screenshots/admin/dashboard.png) | ![Manage teams](screenshots/admin/manage_teams.png) |

| Manage players | Manage tournaments |
|----------------|--------------------|
| ![Manage players](screenshots/admin/manage_players.png) | ![Manage tournaments](screenshots/admin/manage_tournaments.png) |

| Manage matches | Update standings |
|----------------|------------------|
| ![Manage matches](screenshots/admin/manage_matches.png) | ![Update standings](screenshots/admin/update_standings.png) |

| Edit team | Edit player |
|-----------|-------------|
| ![Edit team](screenshots/admin/edit_team.png) | ![Edit player](screenshots/admin/edit_player.png) |

| Manage users | Admin login |
|--------------|-------------|
| ![Manage users](screenshots/admin/manage_users.png) | ![Admin login](screenshots/admin/login.png) |

### User view

| User dashboard | All teams |
|----------------|-----------|
| ![User dashboard](screenshots/user/dashboard.png) | ![All teams](screenshots/user/all_teams.png) |

| Followed team details | Player profile and stats |
|-----------------------|--------------------------|
| ![Followed team details](screenshots/user/followed_team_details.png) | ![Player profile](screenshots/user/player_profile.png) |

| Match events | Match reviews (threaded) |
|--------------|--------------------------|
| ![Match events](screenshots/user/match_events.png) | ![Match reviews](screenshots/user/match_reviews.png) |

| Season statistics | User profile |
|-------------------|--------------|
| ![Season statistics](screenshots/user/season_statistics.png) | ![User profile](screenshots/user/user_profile.png) |

## Project structure

```
football_tournament/
├── admin/          # Admin pages: dashboard, CRUD for users, teams, players,
│                   # tournaments, and matches, plus match events and standings
├── css/style.css
├── includes/       # Shared header and footer
├── uploads/        # Team logos uploaded through the admin panel
├── config.php      # Database connection and helper functions
├── database.sql    # Database schema and sample accounts
└── *.php           # User pages: home, teams, players, tournaments, matches,
                    # statistics, profile, login, and registration
docs/               # ER diagram, schema diagram, project report, and feature list
screenshots/        # Admin and user view screenshots
```

## Documents

- [Project report](docs/project_report.pdf)
- [ER diagram (PDF)](docs/er_diagram.pdf) · [Schema diagram (PDF)](docs/schema_diagram.pdf)
- [Feature list](docs/features.pdf) · [Original setup notes](docs/setup_notes.pdf)
