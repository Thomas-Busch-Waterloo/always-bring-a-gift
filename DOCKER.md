# Docker Deployment Guide

Always Bring a Gift (ABAG) can be deployed as a Docker container for easy self-hosting.

## Quick Start

### Using Docker Compose (Recommended)

1. Create a `docker-compose.yml` file (or use the provided example):

```bash
cp docker-compose.yml docker-compose.prod.yml
```

2. Edit `docker-compose.prod.yml` and update the environment variables:
   - `APP_URL`: Your application URL
   - `PUID`: User ID to run the application as (default: 1000)
   - `PGID`: Group ID to run the application as (default: 1000)
   - `TZ`: The timezone for the host/application (default: UTC)

3. Start the container:

```bash
docker-compose -f docker-compose.prod.yml up -d
```

4. Access the application at http://localhost:8000

5. Log in with the admin credentials shown in the log output. **This info will only be shown on first start**!

### Using Docker Run

```bash
docker run -d \
  --name abag \
  -p 8000:8000 \
  -e APP_URL=http://localhost:8000 \
  -e PUID=99 \
  -e PGID=100 \
  -e TZ=America/Los_Angeles \
  -v abag_storage:/app/storage \
  ghcr.io/indemnity83/always-bring-a-gift:latest
```

## Environment Variables

### User/Group IDs (Unraid Compatibility)

For systems like Unraid where you need to match host user/group IDs:

- `PUID`: User ID to run the application as (default: 1000)
- `PGID`: Group ID to run the application as (default: 1000)

**Example for Unraid:**
```yaml
environment:
  - PUID=99
  - PGID=100
```
### Admin User (First Run)

On the first run, the system will automatically create an admin user:
- **Email**: `admin@example.com`
- **Password**: Random password displayed in the container logs

⚠️ **Important**:
- The random password will **only be shown once** in the logs on first startup
- View the password with: `docker logs abag | grep Password`
- Change the admin password immediately after first login!

### Database

By default, the container uses SQLite stored in a Docker volume. For PostgreSQL or MySQL:

```yaml
environment:
  - DB_CONNECTION=pgsql
  - DB_HOST=postgres
  - DB_PORT=5432
  - DB_DATABASE=abag
  - DB_USERNAME=abag
  - DB_PASSWORD=secret
```

### Optional: Authentik SSO

```yaml
environment:
  - AUTHENTIK_CLIENT_ID=your-client-id
  - AUTHENTIK_CLIENT_SECRET=your-client-secret
  - AUTHENTIK_REDIRECT_URI=http://localhost:8080/auth/authentik/callback
  - AUTHENTIK_BASE_URL=https://authentik.example.com
```

## Data Persistence

The container uses a single volume for all persistent data:

- `abag_storage`: Entire storage directory (mounted at `/app/storage`)
  - `storage/database.sqlite` - SQLite database
  - `storage/app` - User-uploaded files (gift images, etc.)
  - `storage/framework` - Cache, sessions, compiled views (rebuilt on start, harmless to persist)
  - `storage/logs` - Empty (logs go to stdout/stderr)

**Logging:**
- Application logs are sent to stdout/stderr (not files)
- View logs with `docker logs abag`
- `storage/logs` directory exists but remains empty

This simplified approach:
- Single volume to manage
- Database and uploads together
- Easy backups (just backup the volume)
- Framework cache persists across restarts (minor performance benefit)

### Backing Up Data

To backup all your data, backup the `abag_storage` volume:

```bash
# Create a backup
docker run --rm -v abag_storage:/source -v $(pwd):/backup alpine tar czf /backup/abag-backup-$(date +%Y%m%d).tar.gz -C /source .

# Restore from backup
docker run --rm -v abag_storage:/target -v $(pwd):/backup alpine sh -c "cd /target && tar xzf /backup/abag-backup-YYYYMMDD.tar.gz"
```

## Updating

1. Pull the latest image:

```bash
docker pull ghcr.io/indemnity83/always-bring-a-gift:latest
```

2. Recreate the container:

```bash
docker-compose -f docker-compose.prod.yml up -d
```

The entrypoint scripts will automatically run migrations on container start.

**How the entrypoint works:**
1. `docker-entrypoint.sh` runs as root to create directories and set permissions (using PUID/PGID) and handle all Laravel operations (migrations, seeding, caching, etc.) it then uses su-exec to actually start the built in laravel web server as $PUID:$PGID
3. No system users are created - everything uses numeric UIDs/GIDs directly

## Building from Source

The Dockerfile uses a multi-stage build to minimize the final image size:
- Stage 1: Installs PHP dependencies with Composer (discarded after build)
- Stage 2: Builds frontend assets with Node.js (discarded after build)
- Stage 3: Final runtime image with only necessary files

```bash
docker build -t always-bring-a-gift:local .
```

The final image contains:
- PHP runtime only (no Node.js, no Composer, no build tools)
- Compiled frontend assets
- PHP vendor dependencies
- Application code

This results in a much smaller and more secure production image.

## Troubleshooting

### View logs

Application logs are sent to stdout/stderr and can be viewed with:

```bash
# Follow logs in real-time
docker logs -f abag

# View last 100 lines
docker logs --tail 100 abag

# View logs since 1 hour ago
docker logs --since 1h abag
```

### Access container shell

```bash
docker exec -it abag sh
```

### Reset admin password

```bash
docker exec -it abag php artisan tinker
# Then run: User::where('email', 'admin@example.com')->first()->update(['password' => Hash::make('newpassword')])
```

## Security Recommendations

1. **Change default passwords**: Always change the default admin password on first login
2. **Use HTTPS**: Run behind a reverse proxy (nginx, Traefik, Caddy) with SSL/TLS
3. **Regular backups**: Backup the Docker volume regularly
4. **Keep updated**: Pull the latest image regularly for security updates

## Production Deployment

For production, use a reverse proxy with SSL:

```yaml
services:
  app:
    # ... your config
    environment:
      - APP_URL=https://gifts.example.com
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.abag.rule=Host(`gifts.example.com`)"
      - "traefik.http.routers.abag.tls.certresolver=letsencrypt"
```
