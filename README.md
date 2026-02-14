# Event Management API

REST API for event listing, ticket management, bookings, and payments. Built with Laravel, Sanctum authentication, and role-based access (Admin, Organizer, Customer).

---

## Requirements

- **PHP** 8.2+
- **Composer** 2.x
- **Database**: PostgreSQL
- Optional: Redis for cache/queue (see `.env.example`)

---

## Setup

### 1. Clone and install

```bash
git clone <repository-url>
cd event-management-backend
composer install
```

### 2. Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` if needed:

- **DB**: Default is SQLite. For MySQL/PostgreSQL set `DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
- **APP_URL**: Set to your base URL (e.g. `http://event-management-backend.test`) for links in emails/notifications.

### 3. Database

```bash
php artisan migrate
php artisan db:seed
```

### 4. Run the application

```bash
php artisan serve
```

API base URL: **http://event-management-backend.test/api/v1**

---

## Seeded Data

After `php artisan db:seed`, the database contains:

| Entity         | Count | Notes                                                              |
| -------------- | ----- | ------------------------------------------------------------------ |
| **Admins**     | 2     | Full access; can manage any event/ticket.                          |
| **Organizers** | 3     | Can create events and tickets; own events only for update/delete.  |
| **Customers**  | 10    | Can create bookings, pay, cancel; view only own bookings/payments. |
| **Events**     | 5     | Spread across organizers (e.g. 2, 2, 1).                           |
| **Tickets**    | 15    | 3 per event (e.g. VIP, Standard, Economy).                         |
| **Bookings**   | 20    | Mixed statuses: Pending, Confirmed, Cancelled; some with payments. |

Seeded users use the factory default password: **`password`**. Use these to log in via `/api/v1/login` (email + password) and call protected endpoints with the returned Bearer token.

---

## Postman Collection

A Postman collection with all API endpoints is included: **`postman/Event-Management-API.postman_collection.json`**.

1. Open Postman → **Import** → select the JSON file (or drag it in).
2. Set the collection variable **`base_url`** (e.g. `http://event-management-backend.test/api/v1`).
3. **Login** (or **Register**) and copy the `token` from the response into the collection variable **`token`**, or use the **Login** request’s “Tests” script to set it automatically.
4. All **Auth-required** requests use `Authorization: Bearer {{token}}`.

### API overview (from collection)

| Method | Endpoint                     | Auth                  | Description                            |
| ------ | ---------------------------- | --------------------- | -------------------------------------- |
| POST   | `/register`                  | No                    | Register (customer/organizer)          |
| POST   | `/login`                     | No                    | Login; returns token                   |
| GET    | `/events`                    | No                    | List events (paginated, search/filter) |
| GET    | `/events/{id}`               | No                    | Event details with tickets             |
| POST   | `/logout`                    | Yes                   | Revoke current token                   |
| GET    | `/me`                        | Yes                   | Current user                           |
| POST   | `/events`                    | Yes (Organizer/Admin) | Create event                           |
| PUT    | `/events/{id}`               | Yes (Owner/Admin)     | Update event                           |
| DELETE | `/events/{id}`               | Yes (Owner/Admin)     | Delete event                           |
| POST   | `/events/{event_id}/tickets` | Yes (Organizer/Admin) | Create ticket                          |
| PUT    | `/tickets/{id}`              | Yes (Ticket owner)    | Update ticket                          |
| DELETE | `/tickets/{id}`              | Yes (Ticket owner)    | Delete ticket                          |
| POST   | `/tickets/{id}/bookings`     | Yes (Customer)        | Create booking                         |
| GET    | `/bookings`                  | Yes (Customer)        | List own bookings                      |
| PUT    | `/bookings/{id}/cancel`      | Yes (Customer)        | Cancel own booking                     |
| POST   | `/bookings/{id}/payment`     | Yes (Customer)        | Process payment                        |
| GET    | `/payments/{id}`             | Yes                   | Payment details (own only)             |

---

## Testing

```bash
php artisan test
```

Optional coverage (requires Xdebug or PCOV):

```bash
php artisan test --coverage
```

---

## Roles and Permissions

- **Admin**: All event/ticket create, update, delete; cannot create bookings as customer.
- **Organizer**: Create events and tickets; update/delete only own events and their tickets.
- **Customer**: Create bookings, list/cancel own bookings, process payment for own booking, view own payments.

---

## License

Proprietary / as per project agreement.
