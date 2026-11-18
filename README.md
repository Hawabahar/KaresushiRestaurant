# Karesushi Docker setup

From this project directory, run:

```powershell
docker compose up -d --build
```

Open http://localhost:8080/user/ (user area), http://localhost:8080/admin/ (admin area), or http://localhost:8080/kitchen/ (kitchen page). The first run creates the `karesushi_db` database and imports `karesushi_db.sql`. The database is retained in the `db_data` Docker volume when containers are stopped.

Application files are grouped by role: `user/`, `admin/`, and `kitchen/`. The shared database connection is `config.php`; shared images are in `images/` and `uploaded_img/`.

Categories are stored in MariaDB. Admins can add, rename, upload an image for, or delete empty categories at http://localhost:8080/admin/categories.php. Users browse them at http://localhost:8080/user/category.php; all products use the single `user/menu.php` page. The admin manual order page also reads the same categories.

In HeidiSQL, create a **MariaDB or MySQL (TCP/IP)** session with:

| Setting | Value |
| --- | --- |
| Hostname / IP | `127.0.0.1` |
| User | `karesushi` |
| Password | `karesushi_local` |
| Port | `3307` |
| Database | `karesushi_db` |

HeidiSQL is the database client; the website runs in the `web` container. Port `3307` avoids a conflict if XAMPP's MariaDB is still using `3306`. These passwords are for local development only. To stop the containers, run `docker compose down`. Avoid `docker compose down -v` unless you want to delete the database volume. SQL initialization runs only when the volume is empty.
