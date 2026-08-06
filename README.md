# PHP MySQL CRM Portal - Client Page Updated

## Client page changes
- Removed add client form from `modules/clients.php`
- Client list now uses the full page width
- Added top-right `Add Client` button on clients page
- Added new page: `modules/client_create.php`
- New client form fields:
  - Client First Name
  - Client Last Name
  - Company Name
  - Email ID
  - Mobile Number
  - Location
  - LinkedIn ID
  - Photo
  - Card Photo

## Important DB update
Run the updated SQL file again or manually execute the new `ALTER TABLE clients ...` statements from `sql/crm_portal.sql`.

## Setup
1. Put folder in `htdocs/crm-portal`
2. Import `sql/crm_portal.sql`
3. Check `BASE_URL` in `config.php`
4. Open `http://localhost/crm-portal/auth/login.php`
