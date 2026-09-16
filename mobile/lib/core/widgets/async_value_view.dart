import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../network/api_exception.dart';
import 'empty_view.dart';
import 'error_view.dart';
import 'loading_view.dart';

/// Renders every state a Riverpod AsyncValue can be in with the app's
/// standard loading/error/empty treatment, so individual screens only
/// ever write the "happy path" builder (Phase 25 §11/§12).
class AsyncValueView<T> extends StatelessWidget {
  const AsyncValueView({
    super.key,
    required this.value,
    required this.data,
    this.onRetry,
    this.isEmpty,
    this.emptyMessage = 'Nothing here yet.',
    this.emptyIcon = Icons.inbox_outlined,
  });

  final AsyncValue<T> value;
  final Widget Function(T data) data;
  final VoidCallback? onRetry;
  final bool Function(T data)? isEmpty;
  final String emptyMessage;
  final IconData emptyIcon;

  @override
  Widget build(BuildContext context) {
    return value.when(
      loading: () => const LoadingView(),
      error: (error, _) => ErrorView(message: _messageFor(error), onRetry: onRetry),
      data: (value) {
        if (isEmpty != null && isEmpty!(value)) {
          return EmptyView(message: emptyMessage, icon: emptyIcon);
        }
        return data(value);
      },
    );
  }

  String _messageFor(Object error) {
    if (error is ApiException) return error.message;
    return 'Something went wrong. Please try again.';
  }
}
