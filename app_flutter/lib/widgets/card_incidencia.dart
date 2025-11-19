import 'package:flutter/material.dart';

import '../models/incidencia.dart';
import '../utils/helpers.dart';

class CardIncidencia extends StatelessWidget {
  final Incidencia incidencia;

  const CardIncidencia({super.key, required this.incidencia});

  Color _colorEstado() {
    switch (incidencia.estado.toLowerCase()) {
      case 'pendiente':
        return Colors.orange;
      case 'atendido':
        return Colors.blue;
      default:
        return Colors.green;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      child: ListTile(
        title: Text('${incidencia.tipo} (${incidencia.prioridad})'),
        subtitle: Text(incidencia.descripcion),
        trailing: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text(formateaFechaHora(incidencia.fecha)),
            const SizedBox(height: 4),
            Chip(
              label: Text(incidencia.estado),
              backgroundColor: _colorEstado().withOpacity(0.1),
            ),
          ],
        ),
      ),
    );
  }
}
