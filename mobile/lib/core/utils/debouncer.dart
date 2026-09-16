import 'dart:async';
import 'package:flutter/foundation.dart';

/// Delays firing a search request until typing pauses (Phase 29 §9
/// "avoid unnecessary network requests") — shared by every search box in
/// the app instead of each screen rolling its own Timer.
class Debouncer {
  Debouncer({this.duration = const Duration(milliseconds: 400)});

  final Duration duration;
  Timer? _timer;

  void run(VoidCallback action) {
    _timer?.cancel();
    _timer = Timer(duration, action);
  }

  void dispose() => _timer?.cancel();
}
