import 'package:flutter/material.dart';

import '../models/checada.dart';
import '../utils/helpers.dart';

class TarjetaBitacora extends StatelessWidget {
  final Checada checada;

  const TarjetaBitacora({super.key, required this.checada});

  Color _colorEstado() {
    switch (checada.estado.toLowerCase()) {
      case 'aprobada':
        return Colors.green;
      case 'rechazada':
        return Colors.red;
      default:
        return Colors.orange;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      child: ListTile(
        title: Text(checada.tipo),
        subtitle: Text(formateaFechaHora(checada.fecha)),
        trailing: Chip(
          backgroundColor: _colorEstado().withOpacity(0.1),
          label: Text(
            checada.estado,
            style: TextStyle(color: _colorEstado()),
          ),
        ),
      ),
    );
  }
}
