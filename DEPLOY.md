# دیپلوی خودکار rokhdad.top (GitHub Actions)

با هر `push` به شاخه‌ی `main`، گیت‌هاب‌اکشنز به سرور SSH می‌زند، کد را می‌کشد،
ایمیج‌های `rokhdad/frontend:latest` و `rokhdad/backend:latest` را دوباره می‌سازد و
کانتینرها را با کد جدید بالا می‌آورد. (فایل: `.github/workflows/deploy.yml`)

## تنظیم یک‌بارمصرف

### ۱) روی سرور یک کلید SSH مخصوص دیپلوی بساز

```bash
ssh-keygen -t ed25519 -f ~/deploy_key -N ""
cat ~/deploy_key.pub >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
cat ~/deploy_key      # کل این خروجی (کلید خصوصی) را کپی کن
```

### ۲) در گیت‌هاب، در مسیر زیر سکرت‌ها را اضافه کن

`Settings → Secrets and variables → Actions → New repository secret`

| نام سکرت | مقدار |
|---|---|
| `SSH_HOST` | `193.151.139.93` |
| `SSH_USER` | `root` |
| `SSH_KEY` | `-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAAAMwAAAAtzc2gtZW
QyNTUxOQAAACCVc382aMcXbONLIKnEv4PttIDi/3nlVdjaVjxjVXO0twAAAJiTLI7YkyyO
2AAAAAtzc2gtZWQyNTUxOQAAACCVc382aMcXbONLIKnEv4PttIDi/3nlVdjaVjxjVXO0tw
AAAEBoc8zTKTXwAzxqe9lc0394qHeCY1UlwmPuN4YYIgrPfJVzfzZoxxds40sgqcS/g+20
gOL/eeVV2NpWPGNVc7S3AAAAEHJvb3RAcmlja3NhbmNoZXoBAgMEBQ==
-----END OPENSSH PRIVATE KEY-----` |
| `SSH_PORT` |`22` |

### ۳) پیش‌نیازهای سرور

- `docker` و `docker compose` (هست).
- `git` (هست).
- ایمیج‌ها با تگ‌های `rokhdad/frontend:latest` و `rokhdad/backend:latest` ساخته می‌شوند —
  همان نام‌هایی که `/opt/rokhdad/deploy/docker-compose.yml` انتظار دارد، پس کامپوز دست‌نخورده می‌ماند.

## استفاده‌ی روزمره

فقط کد را push کن:

```bash
git add -A
git commit -m "توضیح تغییر"
git push origin main
```

سپس در تب **Actions** ریپو اجرای workflow را ببین. وقتی سبز شد، نسخه‌ی جدید روی rokhdad.top بالاست.

اجرای دستی هم ممکن است: تب Actions → Deploy to rokhdad.top → Run workflow.

## نکته‌ها

- ساخت ایمیج روی همین سرور انجام می‌شود؛ چون فضای دیسک ~۷۰٪ پر است، workflow در پایان
  `docker image prune -f` می‌زند تا ایمیج‌های بلااستفاده پاک شوند.
- نمایش عکس کاور رویدادها به rebuildِ `backend` بستگی دارد (تغییر `EventController`)، که در همین
  workflow انجام می‌شود.
- اگر بعداً worker/سرویس دیگری هم خواستی خودکار شود، یک خط `docker build` و نام سرویس در
  `docker compose up -d ...` اضافه می‌شود.

## دیپلوی دستی backend (تا وقتی .env تأیید شود)

workflow فعلاً فقط **frontend** را خودکار دیپلوی می‌کند. علت: در پوشه‌ی دیپلوی فایل `.env`
دیده نمی‌شود (هشدار `MARIADB_…/MONGO_…/REDIS_… not set`)، و recreate کردن backend بدون آن
ریسک بالاآمدن بدون رمز دیتابیس و خطای ۵۰۲ دارد.

بعد از اطمینان از وجود `.env` در `/opt/rokhdad/deploy`، دیپلوی backend به‌صورت دستی و امن:

```bash
cd /opt/rokhdad/src && git pull
docker build -t rokhdad/backend:latest ./backend
cd /opt/rokhdad/deploy
docker compose -f docker-compose.yml -f docker-compose.migration-override.yml up -d --no-deps --force-recreate backend
curl -ksI https://rokhdad.top/api/v1/events | head -n1   # باید 200 بدهد
```

