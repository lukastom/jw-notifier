# JW Notifier

JW Notifier is a lightweight WordPress plugin that sends daily Telegram reminders:

- **Daily Text Notification**
- **Evening Bible Reading Reminder**
- Zero external libraries
- Simple PHP code
- Designed to run inside any existing WordPress installation  
  (I was too lazy to spin up another VPS just for two cron jobs.)

## Features

✓ Scrapes the official JW Daily Text (WOL)  
✓ Sends messages via Telegram Bot API  
✓ Evening reminder (“Read the Bible.”)  
✓ Two independent cron endpoints  
✓ Minimal and self-contained codebase  
✓ Works even on cheap shared hosting

## Installation

1. Upload the folder `jw-notifier/` into:

```
wp-content/plugins/
```

(via FTP, e.g., FileZilla)

2. Activate the plugin in **WordPress Admin → Plugins**.

3. Ensure the plugin contains these files:

```
jw-notifier.php
jw-daily-text-notifier.php
jw-bible-reading-notifier.php
jw-daily-cron.php
jw-bible-cron.php
```

## Creating a Telegram Bot

1. Install **Telegram** on your phone.
2. Search for **BotFather** → choose the verified account → tap **Start**.
3. Send the command:
   ```
   /newbot
   ```
4. When asked for a name, send:
   ```
   JW notification
   ```
5. When asked for a username, send:
   ```
   JW_notification_bot
   ```
6. You will receive a message with your **Bot Token** → save it.
7. Open your bot in Telegram:  
   Search for `@JW_notification_bot` → tap **Start**.
8. Send the bot any message (e.g., “Hello”).
9. In your browser, open:

   ```
   https://api.telegram.org/botYOUR_BOT_TOKEN/getUpdates
   ```

10. If `"result":[]`, send another message and refresh.
11. You will eventually see something like:

    ```json
    "chat": { "id": 123456789 }
    ```

    → This number is your **chat_id**.

## Configure the Plugin

Open:

```
jw-daily-text-notifier.php
```

and fill in your bot token and chat ID:

```php
return 'YOUR_TELEGRAM_BOT_TOKEN';
return [ YOUR_CHAT_ID ];
```

Both daily-text and bible-reading notifications reuse these values.

## Cron Setup (Server Side)

Create two cron jobs pointing to:

```
/www/wp-content/plugins/jw-notifier/jw-daily-cron.php
/www/wp-content/plugins/jw-notifier/jw-bible-cron.php
```

### Suggested schedule

**Daily Text:**  
→ Every morning (example: 06:00)

**Bible Reading Reminder:**  
→ Every evening (example: 21:00)

Recommended limits:

- **Time limit:** 60 seconds  
- **Memory limit:** 64 MB  

## Manual Testing (Optional)

Daily text:

```
https://your-domain.com/?jw_daily_notify=1
```

Bible reminder:

```
https://your-domain.com/?jw_bible_notify=1
```

---

## Notes

- Uses WordPress’s internal HTTP API (`wp_remote_get` / `wp_remote_post`).
- Does not require any external libraries.
- Works on shared hosting without shell access.
- Fully contained inside the plugin folder.
