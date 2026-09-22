# Premium Gym

Multi-tenant gym-management SaaS: a Laravel API, a React/TypeScript admin
dashboard, and a Flutter member app, all sharing the same backend.

| Component | Path | Description |
|---|---|---|
| **API + admin** | [`backend/`](backend/) | Laravel 12 + Sanctum |
| **Admin dashboard** | [`frontend/`](frontend/) | React + TypeScript + Vite |
| **Member app** | [`mobile/`](mobile/) | Flutter — Android / iOS |

## جرّبه مباشرة (Try it live)

**لوحة الأدمن (Web demo)**
الرابط: https://ahmedmyportofilo.netlify.app/demos/premiumgym/admin/login
حساب الموظف/الأدمن: `ahmed.ehab@premiumgym.com` / `password`
ملاحظة: أول طلب بعد فترة خمول قد يأخذ 30-60 ثانية (استضافة مجانية على Render).

**تطبيق العضو (Android APK)**
حساب العضو التجريبي: `member.demo@premiumgym.com` / `MemberDemo123!`

---

**Admin dashboard (Web demo)**
URL: https://ahmedmyportofilo.netlify.app/demos/premiumgym/admin/login
Staff/admin account: `ahmed.ehab@premiumgym.com` / `password`
Note: the first request after idle may take 30-60s to wake up (free-tier Render hosting).

**Member app (Android APK)**
Demo member account: `member.demo@premiumgym.com` / `MemberDemo123!`

## Quick start (local)

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --seed
php artisan serve
```

```bash
cd frontend
npm install
npm run dev
```

```bash
cd mobile
flutter pub get
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1
```

## Docs

- [`DEPLOYMENT.md`](DEPLOYMENT.md) — production deployment guide
- [`OPERATIONS.md`](OPERATIONS.md) — day-2 operations (logs, backups, rollback)
- [`RELEASE_CHECKLIST.md`](RELEASE_CHECKLIST.md) — release checklist
