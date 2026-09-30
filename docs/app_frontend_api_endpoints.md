# SCLTrack — App Frontend API Endpoints

**Audience:** App (mobile / SPA) frontend developer
**Backend:** Laravel 12 project `scltrack`
**Status:** implemented, tested and running

---

## 1. What this flow does

The app has a school-first entry. The user does not log in first — they pick a school, then pick
*who* they are, then log in.

```
App Screen 1  ->  Select School
                   school names come from  central_school_db . schools
                        |
                        |  backend checks: does this school have a `database_name`?
                        v
App Screen 2  ->  Choose Login            (two buttons)
                        |
        +---------------+---------------+
        v                               v
App Screen 3a -> Parent Login      App Screen 3b -> Driver / Transport Admin Login
                   username+password        username+password
        |                               |
        v                               v
App Screen 4a -> Parent Profile     App Screen 4b -> Driver / Admin Profile
```

### Where the data lives

| Data | Database | Table |
|---|---|---|
| School list | `central_school_db` (`DB_DATABASE`) | `schools` |
| Parent accounts | `school_001_db` (`TENANT_DB_DATABASE`) | `parents` |
| Driver / Transport Admin accounts | `school_001_db` (`TENANT_DB_DATABASE`) | `admin_and_drivers` |

Each school row in `central_school_db.schools` has a `database_name` column (for example
`school_001_db`). When a school is chosen, the backend points the `tenant` database connection at
that database name and then looks up the login. **The app never sends a database name** — it only
sends `school_id`.

> `school_002_db` is listed in `schools` but the database has not been created on this MySQL
> server yet. That school returns `can_login: false`, and any login attempt for it returns a
> clean `422` instead of a server error.

---

## 2. Common rules for every endpoint

### API versioning

Every endpoint lives under **`/api/v1`**. The `v1` is the first path segment after `/api`, so a
future breaking change can be shipped as `/api/v2` while old apps keep working on `/api/v1`.

| | |
|---|---|
| Versioned (use these) | `/api/v1/...` |
| Legacy (still working, do not use) | `/api/parents`, `/api/students` |

The two legacy paths existed before versioning was added. They were left in place so nothing
already built breaks — **build the app against `/api/v1` only.**

### Request conventions

| Item | Value |
|---|---|
| Base URL (local) | `http://127.0.0.1:8000` |
| Base URL (server) | `http://<your-server-ip>:8000` |
| All paths below start with | `/api/v1` |
| Request header (always) | `Accept: application/json` |
| Request header (after login) | `Authorization: Bearer <token>` |
| Request / response format | `application/json` |
| Login token lifetime | 30 days |

**The `Accept: application/json` header is required.** Without it Laravel will try to redirect to
an HTML page instead of returning JSON.

### Success response shape

```json
{
  "message": "Human readable message.",
  "data": { }
}
```

Login endpoints add one extra key, `token`, next to `data`.

### Error response shape

Validation and business errors return **HTTP 422** with this body:

```json
{
  "message": "Short summary.",
  "errors": {
    "field_name": ["First error message.", "Second error message."]
  }
}
```

| HTTP code | When it happens |
|---|---|
| `200` | Success |
| `201` | Resource created (not used in this flow) |
| `422` | Missing/invalid input, wrong credentials, expired token, school not ready |
| `500` | Server error (should not happen in this flow) |

---

## 3. Endpoint summary

| # | Method | URL | Auth | Purpose |
|---|---|---|---|---|
| 1 | `GET` | `/api/v1/schools` | No | List schools for the first screen |
| 2 | `GET` | `/api/v1/schools/{schoolId}` | No | Check one school before continuing |
| 3 | `POST` | `/api/v1/parent/login` | No | Parent login |
| 4 | `GET` | `/api/v1/parent/me` | Yes | Logged-in parent details |
| 5 | `POST` | `/api/v1/parent/logout` | Yes | Parent logout |
| 6 | `POST` | `/api/v1/driver/login` | No | Driver / Transport Admin login |
| 7 | `GET` | `/api/v1/driver/me` | Yes | Logged-in driver / admin details |
| 8 | `POST` | `/api/v1/driver/logout` | Yes | Driver / Transport Admin logout |

A **parent token cannot be used** on driver endpoints, and a **driver token cannot be used** on
parent endpoints. The backend rejects the wrong type with `422`.

### Also available under `/api/v1`

These already existed before versioning and were re-registered under `/api/v1` too. Same
controllers, same payloads, same `search` and `per_page` query parameters.

| Method | URL | Purpose |
|---|---|---|
| `GET` | `/api/v1/parents` | List parents (`?search=`, `?per_page=5\|10\|25\|50`) |
| `POST` | `/api/v1/parents` | Create a parent |
| `GET` | `/api/v1/parents/{id}` | One parent |
| `PUT`/`PATCH` | `/api/v1/parents/{id}` | Update a parent |
| `DELETE` | `/api/v1/parents/{id}` | Delete a parent |
| `GET` | `/api/v1/students` | List students (`?search=`, `?per_page=`) |
| `POST` | `/api/v1/students` | Create a student |
| `GET` | `/api/v1/students/{id}` | One student (includes `parent` + `classSection`) |
| `PUT`/`PATCH` | `/api/v1/students/{id}` | Update a student |
| `DELETE` | `/api/v1/students/{id}` | Delete a student |

> These are **not** scoped to the selected school — they always read the database configured in
> `.env` as `TENANT_DB_DATABASE`. They are listed here only for completeness; the school-selection
> flow in this document does not use them.

---

## 4. Endpoint details

### 1. `GET /api/v1/schools` — school selection list

Call this as soon as App Screen 1 opens. No request body.

**Response `200`**

```json
{
  "message": "School list loaded successfully.",
  "data": [
    {
      "id": 1,
      "school_name": "ims school kanyakumari",
      "school_code": "2550",
      "has_database": true,
      "database_exists": true,
      "can_login": true
    },
    {
      "id": 2,
      "school_name": "kps school nagercoil",
      "school_code": "3550",
      "has_database": true,
      "database_exists": false,
      "can_login": false
    }
  ]
}
```

**Field meaning**

| Field | Type | Meaning |
|---|---|---|
| `id` | number | Send this back as `school_id` on every later call |
| `school_name` | string | Show this in the dropdown |
| `school_code` | string or null | Show next to the name if present |
| `has_database` | boolean | The school row has a `database_name` filled |
| `database_exists` | boolean | That MySQL database really exists on the server |
| `can_login` | boolean | `status = active` **and** `database_exists` |

**Frontend rule:** if `can_login` is `false`, show the school as disabled/greyed out and show the
message *"Login is not available for this school yet."* — do not let the user continue.

Only `status = 'active'` schools are returned. Inactive schools are never sent to the app.

---

### 2. `GET /api/v1/schools/{schoolId}` — single school check

Use this when you want to re-validate a stored `school_id` (for example on app resume after the
user changed school).

**Path parameter**

| Name | Type | Required | Example |
|---|---|---|---|
| `schoolId` | number | Yes | `1` |

**Response `200`**

```json
{
  "message": "School details loaded successfully.",
  "data": {
    "id": 1,
    "school_name": "ims school kanyakumari",
    "school_code": "2550",
    "has_database": true,
    "database_exists": true,
    "can_login": true
  }
}
```

**Response `422`**

```json
{
  "message": "Selected school was not found.",
  "errors": { "school_id": ["Selected school was not found."] }
}
```

---

### 3. `POST /api/v1/parent/login` — parent login

**Request body**

| Field | Type | Required | Notes |
|---|---|---|---|
| `school_id` | number | Yes | From the school list |
| `username` | string | Yes | Max 255 chars |
| `password` | string | Yes | Plain text, sent over the request |

**Example request**

```json
{
  "school_id": 1,
  "username": "apitest_parent",
  "password": "Parent@123"
}
```

**Response `200`**

```json
{
  "message": "Parent login successful.",
  "token": "eyJpdiI6IjBkTTNFVko3VDhaYmV4Tk9DcEVadWc9PSIsInZhbHVlIjoibUx4ZUdyZUw5RFVLWEtj...",
  "data": {
    "id": 37,
    "full_name": "API Test Parent",
    "username": "apitest_parent",
    "phone": "9000000001",
    "email": null,
    "address": null,
    "emergency_contact_name": null,
    "emergency_contact_phone": null,
    "is_active": true,
    "school_id": 1,
    "school_name": "ims school kanyakumari"
  }
}
```

> `token` is a long encrypted string. Store it in secure storage (Keychain / Keystore) and send it
> back as `Authorization: Bearer <token>`. It is valid for 30 days.
>
> `password` is **never** present in any response.

**Response `422` — wrong username or password**

```json
{
  "message": "Invalid username or password.",
  "errors": { "username": ["Invalid username or password."] }
}
```

**Response `422` — school cannot accept logins**

```json
{
  "message": "Parent login is not available for this school yet.",
  "errors": { "school_id": ["Parent login is not available for this school yet."] }
}
```

**Response `422` — missing fields**

```json
{
  "message": "The school id field is required. (and 2 more errors)",
  "errors": {
    "school_id": ["The school id field is required."],
    "username": ["The username field is required."],
    "password": ["The password field is required."]
  }
}
```

**Also blocked:** a parent whose `is_active` is `0` can never log in (the same
`"Invalid username or password."` message is returned, so the app cannot tell whether a username
exists).

---

### 4. `GET /api/v1/parent/me` — logged-in parent details

**Headers**

```
Accept: application/json
Authorization: Bearer <token from login>
```

**Response `200`**

```json
{
  "message": "Parent profile loaded successfully.",
  "data": {
    "id": 37,
    "full_name": "API Test Parent",
    "username": "apitest_parent",
    "phone": "9000000001",
    "email": null,
    "address": null,
    "emergency_contact_name": null,
    "emergency_contact_phone": null,
    "is_active": true,
    "school_id": 1,
    "school_name": "ims school kanyakumari"
  }
}
```

**Response `422` — token problems**

```json
{ "message": "Login token is missing.", "errors": { "token": ["Login token is missing."] } }
```

```json
{ "message": "Login token is invalid.", "errors": { "token": ["Login token is invalid."] } }
```

```json
{ "message": "Login token is not valid for this account type.", "errors": { "token": ["Login token is not valid for this account type."] } }
```

```json
{ "message": "Login token has expired. Please login again.", "errors": { "token": ["Login token has expired. Please login again."] } }
```

**Frontend rule:** on any `token` error, clear the saved token and send the user back to
**Screen 1 (Select School)**.

---

### 5. `POST /api/v1/parent/logout` — parent logout

Send the `Authorization` header. No request body.

**Response `200`**

```json
{ "message": "Parent logged out successfully." }
```

The token is not stored server-side, so the **app must delete its own copy of the token**.

---

### 6. `POST /api/v1/driver/login` — driver / transport admin login

Identical request body to the parent login. The only difference is the endpoint and the returned
profile.

**Request body**

| Field | Type | Required | Notes |
|---|---|---|---|
| `school_id` | number | Yes | From the school list |
| `username` | string | Yes | From `admin_and_drivers.username` |
| `password` | string | Yes | Plain text, sent over the request |

**Example request**

```json
{
  "school_id": 1,
  "username": "sam",
  "password": "123456"
}
```

**Response `200`**

```json
{
  "message": "Driver / transport admin login successful.",
  "token": "eyJpdiI6IlhSdFNVM2srQWpyOGxJQUZoNjY3MFE9PSIsInZhbHVlIjoiSnB3VWRhbitNLzRQT1...",
  "data": {
    "id": 17,
    "full_name": "sam",
    "username": "sam",
    "user_role": "super admin",
    "phone": null,
    "email_id": null,
    "license_number": null,
    "license_expiry_date": null,
    "address": null,
    "is_active": true,
    "school_id": 1,
    "school_name": "ims school kanyakumari"
  }
}
```

**`user_role` possible values** (free text stored in the database, treat it as a display string):

`super admin`, `transport admin`, `data entry staff`, `cab drivers`, `cab assistant`

**`license_expiry_date` format:** `d-m-Y` string (for example `31-12-2026`) or `null`.

**Response `422` — wrong username or password**

```json
{ "message": "Invalid username or password.", "errors": { "username": ["Invalid username or password."] } }
```

Same shape as the parent login, with these possible `422` messages:

- `Invalid username or password.`
- `Driver login is not available for this school yet.`
- `The school id field is required.` / `The username field is required.` / `The password field is required.`

---

### 7. `GET /api/v1/driver/me` — logged-in driver / admin details

**Headers**

```
Accept: application/json
Authorization: Bearer <token from driver login>
```

**Response `200`**

```json
{
  "message": "Driver / admin profile loaded successfully.",
  "data": {
    "id": 17,
    "full_name": "sam",
    "username": "sam",
    "user_role": "super admin",
    "phone": null,
    "email_id": null,
    "license_number": null,
    "license_expiry_date": null,
    "address": null,
    "is_active": true,
    "school_id": 1,
    "school_name": "ims school kanyakumari"
  }
}
```

Token errors are the same as listed under endpoint 4.

---

### 8. `POST /api/v1/driver/logout` — driver / transport admin logout

**Response `200`**

```json
{ "message": "Driver / transport admin logged out successfully." }
```

---

## 5. Screen-by-screen integration guide

### Screen 1 — Select School

```
GET /api/v1/schools
```

- Render a dropdown or list from `data[]`.
- Show `school_name` (+ `school_code` if present).
- If `can_login === false`, disable that row and show
  *"Login is not available for this school yet."*
- On tap, save `school_id` locally and go to Screen 2.

### Screen 2 — Choose Login (two buttons)

There is **no API call for this screen** — it is a static two-button page.

| Button | Goes to |
|---|---|
| **Parent Login** | Screen 3a |
| **Driver / Transport Admin Login** | Screen 3b |

Optional extra check before showing it: `GET /api/v1/schools/{schoolId}` to confirm `can_login`
is still `true`.

### Screen 3a — Parent Login

Show two inputs (`username`, `password`) and a Login button.

```
POST /api/v1/parent/login
{ "school_id": <saved school_id>, "username": "...", "password": "..." }
```

- `200` → store `token`, store `data`, go to Screen 4a.
- `422` → show `errors.username[0]` (or `errors.school_id[0]`) in red under the form.

### Screen 3b — Driver / Transport Admin Login

Identical form and identical request body, but call `POST /api/v1/driver/login`.

- `200` → store `token`, store `data`, go to Screen 4b.
- `422` → show the error the same way.

### Screen 4a — Parent Profile

Show the fields from the `data` object returned by login (or re-fetch with `GET /api/v1/parent/me`).
Recommended rows: School, Parent ID, Full Name, Username, Phone, Email, Address, Emergency
Contact Name, Emergency Contact Phone, Status.

Show a **Logout** button → `POST /api/v1/parent/logout`, clear the token, return to Screen 1.

### Screen 4b — Driver / Transport Admin Profile

Show: School, User ID, Full Name, Username, User Role, Phone, Email, License Number, License
Expiry Date, Address, Status.

Show a **Logout** button → `POST /api/v1/driver/logout`, clear the token, return to Screen 1.

---

## 6. Copy-paste test commands

```bash
BASE=http://127.0.0.1:8000
V1=$BASE/api/v1
JSON='Accept: application/json'
```

**School list**
```bash
curl -s -H "$JSON" $V1/schools
```

**One school**
```bash
curl -s -H "$JSON" $V1/schools/1
```

**Parent login**
```bash
curl -s -X POST -H "$JSON" -H "Content-Type: application/json" \
  -d '{"school_id":1,"username":"apitest_parent","password":"Parent@123"}' \
  $V1/parent/login
```

**Parent profile** (paste the token from the login response)
```bash
curl -s -H "$JSON" -H "Authorization: Bearer <TOKEN>" $V1/parent/me
```

**Driver login**
```bash
curl -s -X POST -H "$JSON" -H "Content-Type: application/json" \
  -d '{"school_id":1,"username":"sam","password":"123456"}' \
  $V1/driver/login
```

**Driver profile**
```bash
curl -s -H "$JSON" -H "Authorization: Bearer <TOKEN>" $V1/driver/me
```

**Logout**
```bash
curl -s -X POST -H "$JSON" -H "Authorization: Bearer <TOKEN>" $V1/parent/logout
```

---

## 7. Matching web (Blade) pages

The same flow also exists as server-rendered pages, reusing the project's existing design
(`login-card`, `module-card`, `top-navbar` classes). Use them to test the flow in a browser.

| Step | URL | Form posts to |
|---|---|---|
| Select school | `GET /select-school` | `POST /select-school` |
| Choose login (2 buttons) | `GET /login-choice` | — |
| Parent login | `GET /parent/login` | `POST /parent/login` |
| Parent profile | `GET /parent/dashboard` | `POST /parent/logout` |
| Driver login | `GET /driver/login` | `POST /driver/login` |
| Driver profile | `GET /driver/dashboard` | `POST /driver/logout` |

These use normal Laravel session cookies instead of tokens. **The original admin login at
`/login` and all existing CRUD screens are untouched and still work exactly as before.**

---

## 8. Backend implementation map (for reference)

| File | Purpose |
|---|---|
| `app/Models/School.php` | Reads `central_school_db.schools` |
| `app/Models/Tenant/ParentModel.php` | Reads the tenant `parents` table (now with `username` + `password`) |
| `app/Models/Tenant/AdminAndDriver.php` | Reads the tenant `admin_and_drivers` table (already had `username` + `password`) |
| `app/Services/TenantDatabaseService.php` | Points the `tenant` connection at the chosen school's `database_name` |
| `app/Services/SchoolService.php` | School list / school lookup / `can_login` check |
| `app/Services/LoginTokenService.php` | Creates and validates the login token |
| `app/Services/ParentAuthService.php` | Parent username + password check |
| `app/Services/DriverAuthService.php` | Driver / admin username + password check |
| `app/Http/Controllers/Api/SchoolController.php` | Endpoints 1 and 2 |
| `app/Http/Controllers/Api/ParentAuthController.php` | Endpoints 3, 4, 5 |
| `app/Http/Controllers/Api/DriverAuthController.php` | Endpoints 6, 7, 8 |
| `routes/api.php` | Registers every endpoint inside a `Route::prefix('v1')` group |
| `app/Http/Controllers/SchoolSelectionController.php` | Web: Screen 1 and Screen 2 |
| `app/Http/Controllers/ParentPortalController.php` | Web: Parent screens |
| `app/Http/Controllers/DriverPortalController.php` | Web: Driver screens |
| `database/migrations/tenant/2026_09_29_000001_add_username_and_password_to_parents_table.php` | Adds `username` + `password` to `parents` |

### Migration command

```bash
php artisan migrate --database=tenant --path=database/migrations/tenant
```

The migration only **adds** two nullable columns (`username`, `password`) plus a unique index on
`username`. It never edits or deletes existing rows — all existing parents keep `username = NULL`
and `password = NULL` until an admin sets them. Running the command twice is safe.

---

## 9. Known gaps / notes for the frontend team

1. **Existing parents have no username/password yet.** The columns are `NULL` until a school admin
   fills them in from the existing Parents screen. Those parents cannot log in yet.
2. **`school_002_db` does not exist** on this MySQL server. The school appears in the list with
   `can_login: false`.
3. **Tokens are stateless.** Logout only tells the app to delete its own copy. A token that is
   stolen stays valid until it expires (30 days) or the account is set to inactive.
4. **CORS** — for a browser-based SPA on a different domain, add the frontend origin to the
   Laravel CORS config before testing.
5. **No rate limiting** on the login endpoints yet. Worth adding before going live.
