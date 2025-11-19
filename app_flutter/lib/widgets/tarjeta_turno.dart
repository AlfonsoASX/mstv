import 'package:flutter/material.dart';

import '../models/turno.dart';
import '../utils/helpers.dart';

class TarjetaTurno extends StatelessWidget {
  final Turno turno;

  const TarjetaTurno({super.key, required this.turno});

  @override
  Widget build(BuildContext context) {
    return Card(
      child: ListTile(
        title: Text(turno.sitio),
        subtitle: Text('${formateaHoraCorta(turno.inicio)} - ${formateaHoraCorta(turno.fin)}'),
        trailing: turno.esExtra
            ? const Chip(label: Text('Extra'), backgroundColor: Colors.orangeAccent)
            : const Chip(label: Text('Ordinario')),
      ),
    );
  }
}
