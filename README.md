# MonacoTool

A Monte Carlo forecasting tool for agile software teams. Given a team's historical iteration output, it simulates thousands of possible futures to estimate the probability of hitting a target output within a target number of iterations.

---

## Tech stack

- **Backend:** Symfony 7.4, Doctrine ORM 3, PHP 8.2+
- **Frontend:** Tailwind CSS v4 (via `symfonycasts/tailwind-bundle`), Vanilla JS, Hotwire Turbo (via `symfony/ux-turbo`), Asset Mapper / importmap (no Webpack/Vite)
- **Database:** Any Doctrine-supported RDBMS (development default: MySQL 8)
- **Mail:** Symfony Mailer (Mailpit recommended for local development)
- **QA:** PHPUnit 11, PHPStan 2 (level 6), PHP-CS-Fixer 3

---

## Concepts

| Term | Meaning |
|---|---|
| **Team** | A group of users with a shared backlog and iteration history |
| **Iteration** | A time-boxed sprint with a measured output (story points, tickets, etc.) |
| **Velocity** | Average output across all iterations |
| **Forecast** | A Monte Carlo simulation that returns the probability of reaching a target output within a target number of iterations |
| **Membership** | The link between a user and a team. Carries a role (`Admin` / `User`) and a status (`Pending` / `Active` / `Rejected`) |

---

## Getting started

### Prerequisites

- PHP 8.2+ with `ctype` and `iconv` extensions
- Composer
- A running database (MySQL 8 by default; any Doctrine-supported RDBMS works)
- An SMTP server for outbound email (Mailpit recommended for local development)
- The Symfony CLI is recommended for the dev server

### Install

```bash
composer install
```

### Environment

Copy the committed defaults and add the secrets your local install needs:

```bash
cp .env .env.local
```

Then edit `.env.local` and set every variable in the table below. The committed `.env` only ships non-secret defaults (`APP_SECRET`, `DATABASE_URL`, `NUMBER_OF_SIMULATIONS`, `DEFAULT_URI`); the mailer and invite vars **must** be added to `.env.local` or the app will fail to boot.

| Variable | Required? | Where set | Purpose |
|---|---|---|---|
| `APP_SECRET` | Yes | `.env` (override in `.env.local` for prod) | Symfony session/CSRF secret |
| `DATABASE_URL` | Yes | `.env` (override in `.env.local`) | Doctrine DSN |
| `NUMBER_OF_SIMULATIONS` | Yes | `.env` (default `2500`) | How many Monte Carlo runs `BasicForecaster` performs per forecast |
| `DEFAULT_URI` | Yes | `.env` (default `http://127.0.0.1`) | Base URI used when generating absolute URLs from the CLI (e.g. for emails sent in console contexts) |
| `MAILER_DSN` | Yes | `.env.local` | Symfony Mailer DSN. Local example: `smtp://127.0.0.1:1025` (Mailpit) |
| `MAILER_FROM` | Yes | `.env.local` | `From:` address on outbound mail. Example: `noreply@monacotool.local` |
| `INVITE_TOKEN_EXPIRY_DAYS` | Yes | `.env.local` | Lifetime of team invitation links, in days. Recommended: `7` |

> **Heads up:** `.env.local` is gitignored. None of the variables marked "Yes / `.env.local`" have committed defaults — if you skip them the container will fail to compile.

### Database

Run the migrations:

```bash
php bin/console doctrine:migrations:migrate
```

### Mailer

The app sends transactional emails for:

- Account verification (`verification.html.twig`)
- Password reset (`password-reset.html.twig`)
- Team invitations to new users (`invite-new-user.html.twig`)
- Team invitations to existing users (`invite-existing-user.html.twig`)

For local development, [Mailpit](https://mailpit.axllent.org/) catches outbound mail at `127.0.0.1:1025` (SMTP) with a UI on `127.0.0.1:8025`.

### Create the super admin

There is one super admin per installation. The super admin can see every team and bypasses the per-user team-creation cap.

```bash
php bin/console app:create-super-admin
```

The command refuses to run a second time once a super admin exists. Email, name, and password are validated interactively; password length must be 8–72 characters.

### Run the dev server

```bash
php bin/console tailwind:build --watch &
symfony server:start
```

---

## Roles & permissions

Two layers of authorisation are in play: a global Symfony role on `User`, and a per-team role+status on `Membership`.

| Role / Status | Can do |
|---|---|
| `ROLE_SUPER_ADMIN` | View, edit, and delete **any** team. Bypasses the per-user limit on admin teams. |
| `Membership(Admin, Active)` | View team, add/edit/delete iterations, request and delete forecasts, **invite members**, delete the team |
| `Membership(User, Active)` | View team and its iterations/forecasts (read-only) |
| `Membership(*, Pending)` | **No team access.** Can act on the invitation only via the email link |
| `Membership(*, Rejected)` | **No team access.** A new invitation re-uses the rejected row |

A user (other than the super admin) is capped at being admin of **5 teams** at a time (`max_admin_teams` in `services.yaml`). The cap counts only `Active` admin memberships — pending or rejected admin invites do not count.

---

## Account & invitation flows

### Self-registration

1. `POST /register` creates an unverified user.
2. `POST /resend-verification` re-sends the verification mail.
3. `GET /verify-email?token=…` flips `isVerified` to true and redirects to login with a success flash.

### Forgot / reset password

1. `POST /forgot-password` sends a reset link (always returns 200, regardless of whether the email exists, to avoid enumeration).
2. `POST /reset-password` consumes the token and sets a new password.

### Team invitations

1. An admin opens the team page and clicks **+ Invite**.
2. `POST /team/{id}/invite` (admin-only, rate-limited 20/hour/IP) takes a `name` and `email`.
3. Backend behaviour depends on whether the email is already a `User`:
   - **New user:** a `User` row is created with an unhashed sentinel password (cannot authenticate) and `isVerified = false`. They get an email with a setup link.
   - **Existing user:** they get an email with separate **Accept** and **Decline** links.
4. The membership row is `Pending` until the invitee acts on it. Tokens are stored as SHA-256 hashes; the plain token only ever exists in the email URL. Default expiry is `INVITE_TOKEN_EXPIRY_DAYS` days (recommended: 7).
5. Accept/decline routes are CSRF-protected and require the logged-in user to match the invitee. Re-inviting a previously rejected user re-uses the same membership row to avoid violating the `(user, team)` unique constraint.

---

## API reference

All non-public routes require an authenticated session. Mutating endpoints expect `application/json` bodies and CSRF tokens supplied by the page-side `apiFetch()` helper.

### Public

| Method | Path | Description |
|---|---|---|
| `GET` | `/` | Landing page |
| `GET`/`POST` | `/login` | Form login (CSRF-enabled) |
| `POST` | `/logout` | Logout |
| `POST` | `/register` | Create a new account |
| `POST` | `/resend-verification` | Resend the email verification link |
| `GET` | `/verify-email` | Verify an email address from a link |
| `POST` | `/forgot-password` | Request a password reset email |
| `POST` | `/reset-password` | Reset password using a token |
| `GET` | `/invite/{token}` | New-user account setup page |
| `POST` | `/invite/{token}` | Submit account setup (auto-logs in on success) |
| `GET`/`POST` | `/invite/{token}/accept` | Accept an invitation (existing user) |
| `GET`/`POST` | `/invite/{token}/reject` | Decline an invitation |

### Authenticated

| Method | Path | Description |
|---|---|---|
| `GET` | `/team` | List teams the current user belongs to |
| `POST` | `/team` | Create a team (caller becomes Admin) |
| `GET` | `/team/{id}` | Show a team |
| `DELETE` | `/team/{id}` | Delete a team (admin / super admin) |
| `POST` | `/team/{id}/invite` | Invite a user to the team (admin) |
| `POST` | `/team/{id}/forecast` | Run a new forecast |
| `POST` | `/team/{id}/iteration` | Add an iteration |
| `PATCH` | `/iteration/{id}` | Update an iteration |
| `DELETE` | `/iteration/{id}` | Delete an iteration |
| `POST` | `/forecast/{id}/sensitivity` | Compute the forecast's sensitivity table on demand |
| `DELETE` | `/forecast/{id}` | Delete a forecast |

---

## Rate limits

Configured in `config/packages/rate_limiter.yaml`. All limits use the fixed-window policy and are keyed on the client IP:

| Action | Limit |
|---|---|
| Register | 5 / hour |
| Resend verification | 3 / hour |
| Forgot password | 5 / hour |
| Reset password | 10 / hour |
| Invite | 20 / hour |

The default cache pool for the limiter is filesystem-based. **For multi-instance production deployments, switch to a shared adapter (e.g. Redis)** — otherwise each app server enforces its own bucket.

---

## Forecasting

`BasicForecaster` runs `NUMBER_OF_SIMULATIONS` Monte Carlo runs per forecast. Each run draws from a normal distribution fitted to the team's historical mean and standard deviation, then sums those draws across the target number of iterations. The forecast result is the fraction of simulations that reached or exceeded the target output (a probability in `[0, 1]`), rendered alongside a label:

| Probability | Label |
|---|---|
| ≥ 0.7 | Likely |
| 0.4 – 0.69 | Possible |
| < 0.4 | Unlikely |

The detail view also shows a sensitivity table that recomputes the probability across nearby iteration counts.

---

## Theming

The UI ships with three themes — **Light**, **Dark**, and **Forest** — switchable from the header. The chosen theme is persisted in `localStorage`.

---

## Development workflow

### Run the test suite

```bash
php bin/phpunit
```

PHPUnit 11 is configured via `phpunit.dist.xml`. Test bootstrap is `tests/bootstrap.php`. Functional tests use Symfony's `WebTestCase` against the test environment, which uses an in-memory cache adapter and a low-cost password hasher for speed.

### Static analysis

```bash
composer phpstan
```

Runs PHPStan at level 6 against `src/`, with the `phpstan-doctrine` and `phpstan-symfony` extensions wired in (`phpstan.neon`).

### Code style

```bash
vendor/bin/php-cs-fixer fix
```

Configured via `.php-cs-fixer.dist.php`.

### Asset Mapper / importmap

This project uses Symfony's Asset Mapper — there is no Webpack or Vite. Runtime JavaScript modules are declared in `importmap.php` and served from `assets/`.

To add a new package:

```bash
php bin/console importmap:require <package>
```

To download all packages after a fresh clone or after manually editing `importmap.php`:

```bash
php bin/console importmap:install
```

---

## Production notes

- Set a long, random `APP_SECRET` in `.env.local` (do **not** ship the development one).
- Switch the rate-limiter cache pool to a shared adapter (Redis, Memcached) when running more than one instance — see `config/packages/rate_limiter.yaml`.
- `MAILER_DSN` should point at a real transport (Mailgun, Postmark, SES…). The `MAILER_FROM` address must be authorised on that transport.
- `DEFAULT_URI` is read by Symfony when generating absolute URLs from CLI contexts (cron, queues). Set it to your public origin.
- Build assets for production with `php bin/console tailwind:build` followed by `php bin/console asset-map:compile`.
- Re-inviting a previously rejected or pending user re-uses the existing membership row rather than inserting a duplicate.
