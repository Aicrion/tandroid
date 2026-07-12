# Deployment: Shared Hosting and VPS

## Shared Hosting (No CLI)

This is the primary scenario the framework was designed for: upload
files via FTP/File Manager, no SSH, no CLI commands.

### Checklist

1. **Upload.** Upload the entire project (including `vendor/` —
   built in advance on your own machine with
   `composer install --no-dev --optimize-autoloader`) to the host.
   Most shared hosts can't run Composer, so `vendor/` must already be
   prepared.
2. **Set the Document Root to `public/`** (or, if that's not
   possible, move the contents of `public/` to the domain root and
   adjust the `require` paths in `webhook.php` accordingly).
3. **Environment variables.** If your hosting panel doesn't support
   setting env variables, you can temporarily put the real token
   value directly in `config/aicrion.yaml` (only after confirming
   this file is outside public reach — the `config/` folder must
   never live inside `public/`).
4. **Make `var/` writable.** Both SQLite (`var/data.sqlite`) and the
   filesystem cache (`var/cache/`) need write access:

   ```bash
   chmod -R 775 var/
   ```

5. **Register the webhook** (once, from your own browser, no server
   CLI required):

   ```
   https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://your-domain.com/webhook.php
   ```

6. **Test it.** Send `/start` to the bot. The very first request
   both discovers plugins and builds the database schema (automatic
   migration) — no separate install step is needed.

### Why SQLite Is the Default Choice

Most shared hosts restrict provisioning a separate MySQL service or
Redis access. SQLite is just a plain file in `var/` — no separate
process, no network port, no permissions beyond writing a file. If
your host does offer MySQL, you can still switch to it per
[Configuration](02-configuration.md).

## VPS / Docker

On environments where you have full control, it's recommended to
use:

- **Real Redis** for shared cache/back-stack across multiple
  workers.
- **Polling mode with a process supervisor** (systemd/supervisord) if
  you'd rather not have a public HTTPS domain, or **Webhook behind
  Nginx/Caddy with TLS** for better performance under high load.
- **`opcache` enabled** for PHP-FPM, since `EntityManagerFactory`
  reads Doctrine metadata from attributes on every boot; opcache
  removes the cost of parsing PHP files (not the metadata reflection
  itself — for that, consider a dedicated Doctrine metadata cache).

### Sample systemd Unit for Polling Mode

```ini
[Unit]
Description=Aicrion Tandroid Bot (polling)
After=network.target

[Service]
ExecStart=/usr/bin/php /var/www/my-bot/bin/poll.php
Restart=always
RestartSec=3
User=www-data
WorkingDirectory=/var/www/my-bot

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable --now aicrion-bot.service
```

### Sample Nginx Config for Webhook Mode

```nginx
server {
    listen 443 ssl http2;
    server_name your-domain.com;
    root /var/www/my-bot/public;

    location /webhook.php {
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root/webhook.php;
        include fastcgi_params;
    }
}
```

## Security Checklist Before Going to Production

- [ ] Never commit the bot token in code; always use `%env(...)%`.
- [ ] Set a `secret_token` when calling `setWebhook`, and verify the
      `X-Telegram-Bot-Api-Secret-Token` header matches it inside
      `webhook.php`, so nobody can send fake Updates directly to your
      `webhook.php`.
- [ ] Keep the `config/` and `var/` folders outside `public/` so
      they're not downloadable through a browser.
- [ ] For production, wrap `EntityManagerFactory` with a metadata
      caching layer (see the performance note in
      [Database and Doctrine](07-database-and-doctrine.md)).
