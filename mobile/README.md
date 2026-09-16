# Premium Gym — Member Mobile App

Flutter client for gym **members** (not staff) — a separate app from the
React admin dashboard, talking to the same Laravel API under a dedicated
`member/` route namespace with its own authentication.

## Architecture

Feature-based, with a light clean-architecture split per feature:

```
lib/
  core/           # api client, secure storage, theming, routing, shared widgets — no feature imports this
  shared/models/  # typed models shared across features (Member, GymClass, Invoice, ...)
  features/<name>/
    data/          # repository — the only place that calls ApiClient for this feature
    presentation/
      providers/   # Riverpod state (FutureProvider for reads, StateNotifier for actions)
      screens/
```

Every repository returns `ApiResult<T>` (`ApiSuccess`/`ApiFailure`) rather
than throwing across layers; providers convert that into Riverpod's
`AsyncValue`, which `AsyncValueView` renders uniformly (loading / error /
empty / data) across every screen.

## Running

```sh
flutter pub get

# Android emulator (10.0.2.2 is the emulator's alias for the host machine):
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api/v1

# iOS simulator / physical device on the same network — use your machine's LAN IP:
flutter run --dart-define=API_BASE_URL=http://192.168.x.x:8000/api/v1
```

The backend must be running (`php artisan serve`) and at least one
`Member` row needs a password set — staff do this from the admin
dashboard's Members screen (or `POST /api/v1/members/{member}/set-password`).

## Testing

```sh
flutter analyze
flutter test
flutter build apk --release   # production build check
```

## Deliberately out of scope (see Phase 25 report)

- Trainer app, offline sync, AI features — explicitly excluded by Phase 25.
- QR check-in scanning — the backend has no QR-specific attendance
  endpoint yet to scan against; the repository pattern here is ready for
  it once one exists.
- Real push delivery — `features/notifications/data/device_token_repository.dart`
  is wired to the backend's device-token endpoint, but no push SDK
  (Firebase Messaging) is integrated, since that needs a real Firebase
  project's credentials this environment doesn't have.
