import 'package:flutter/material.dart';

class BotonChecada extends StatelessWidget {
  final String titulo;
  final IconData icono;
  final Color color;
  final VoidCallback? onPressed;

  const BotonChecada({
    super.key,
    required this.titulo,
    required this.icono,
    required this.color,
    this.onPressed,
  });

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: ElevatedButton.icon(
        icon: Icon(icono),
        onPressed: onPressed,
        style: ElevatedButton.styleFrom(backgroundColor: color),
        label: Padding(
          padding: const EdgeInsets.symmetric(vertical: 12),
          child: Text(
            titulo,
            textAlign: TextAlign.center,
          ),
        ),
      ),
    );
  }
}
