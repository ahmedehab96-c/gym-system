import 'package:flutter/material.dart';

/// One consistent search box used across every list screen that supports
/// server-side filtering (Phase 29 §6) — kept intentionally dumb (no
/// debouncing/state of its own) so each screen decides its own debounce
/// and provider wiring via [onChanged].
class SearchField extends StatelessWidget {
  const SearchField({required this.hintText, required this.onChanged, super.key});

  final String hintText;
  final ValueChanged<String> onChanged;

  @override
  Widget build(BuildContext context) {
    return TextField(
      onChanged: onChanged,
      textInputAction: TextInputAction.search,
      decoration: InputDecoration(
        hintText: hintText,
        prefixIcon: const Icon(Icons.search_rounded),
        isDense: true,
        filled: true,
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: BorderSide.none),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      ),
    );
  }
}
