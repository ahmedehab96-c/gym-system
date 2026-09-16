import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';

class AppAvatar extends StatelessWidget {
  const AppAvatar({super.key, this.imageUrl, required this.name, this.radius = 24});

  final String? imageUrl;
  final String name;
  final double radius;

  String get _initials {
    final parts = name.trim().split(RegExp(r'\s+')).where((p) => p.isNotEmpty).toList();
    if (parts.isEmpty) return '?';
    if (parts.length == 1) return parts.first.substring(0, 1).toUpperCase();
    return (parts.first.substring(0, 1) + parts.last.substring(0, 1)).toUpperCase();
  }

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final url = imageUrl;

    if (url == null || url.isEmpty) {
      return CircleAvatar(
        radius: radius,
        backgroundColor: scheme.primaryContainer,
        child: Text(_initials, style: TextStyle(color: scheme.onPrimaryContainer, fontWeight: FontWeight.w700)),
      );
    }

    return ClipOval(
      child: CachedNetworkImage(
        imageUrl: url,
        width: radius * 2,
        height: radius * 2,
        fit: BoxFit.cover,
        placeholder: (_, _) => CircleAvatar(radius: radius, backgroundColor: scheme.surfaceContainerHighest),
        errorWidget: (_, _, _) => CircleAvatar(
          radius: radius,
          backgroundColor: scheme.primaryContainer,
          child: Text(_initials, style: TextStyle(color: scheme.onPrimaryContainer, fontWeight: FontWeight.w700)),
        ),
      ),
    );
  }
}
