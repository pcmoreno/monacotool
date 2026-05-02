# MonacoTool

A Monte Carlo forecasting tool for agile software teams. Given a team's historical iteration output, it simulates thousands of possible futures to estimate the probability of hitting a target within a set number of iterations.

## Tech stack

- **Backend:** Symfony 7.4, Doctrine ORM, PHP 8.2+
- **Frontend:** Tailwind CSS v4, Vanilla JS, Hotwire Turbo
- **Database:** Any Doctrine-supported RDBMS

## Concepts

| Term | Meaning |
|---|---|
| **Team** | A group of users with a shared backlog and iteration history |
| **Iteration** | A time-boxed sprint with a measured output (story points, tickets, etc.) |
| **Velocity** | Average output across all iterations |
| **Forecast** | A Monte Carlo simulation that returns the probability of reaching a target output within a target number of iterations |

## Getting started

### Prerequisites

- PHP 8.2+
- Composer
- A running database

### Install

```bash
composer install
```

Copy and configure your environment:

```bash
cp .env .env.local
# edit DATABASE_URL, APP_SECRET, MAILER_DSN, and MAILER_FROM in .env.local
```

Run migrations:

```bash
php bin/console doctrine:migrations:migrate
```

### Mailer

The app sends transactional emails for account verification and password reset. Set `MAILER_DSN` and `MAILER_FROM` in `.env.local`. During development, [Mailpit](https://mailpit.axllent.org/) works well as a local SMTP catcher.

### Create the super admin

There is one super admin per installation. The super admin can see all teams.

```bash
php bin/console app:create-super-admin
```

### Run the dev server

```bash
php bin/console tailwind:build --watch &
symfony server:start
```

## Roles

| Role | Access |
|---|---|
| `ROLE_SUPER_ADMIN` | All teams, all data, can delete any team |
| `TeamRole::Admin` | Team admin — can view, add/edit/delete iterations, request forecasts, and delete the team |
| `TeamRole::User` | Team member — view only |

## Theming

The UI ships with three themes — **Light**, **Dark**, and **Forest** — switchable at any time from the header. The chosen theme is persisted in `localStorage`.

## API endpoints

| Method | Path | Description |
|---|---|---|
| `POST` | `/register` | Create a new account |
| `POST` | `/resend-verification` | Resend the email verification link |
| `POST` | `/forgot-password` | Request a password reset email |
| `POST` | `/reset-password` | Reset password using a token |
| `POST` | `/team` | Create a team |
| `DELETE` | `/team/{id}` | Delete a team (admin/superadmin only) |
| `POST` | `/team/{id}/forecast` | Request a new forecast |
| `DELETE` | `/forecast/{id}` | Delete a forecast |
| `POST` | `/team/{id}/iteration` | Add an iteration |
| `PATCH` | `/iteration/{id}` | Update an iteration |
| `DELETE` | `/iteration/{id}` | Delete an iteration |

## Forecasting

The `BasicForecaster` runs N Monte Carlo simulations (configurable via `number_of_simulations` in `services.yaml`). Each simulation draws from a normal distribution fitted to the team's historical mean and standard deviation to project cumulative totals over the target number of iterations. The result is the fraction of simulations that reached or exceeded the target output — a probability between 0 and 1.
