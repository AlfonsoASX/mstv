import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../models/turno.dart';
import '../services/api_service.dart';
import '../services/auth_service.dart';
import '../widgets/boton_checada.dart';
import '../widgets/tarjeta_turno.dart';

class DashboardGuardia extends StatefulWidget {
  const DashboardGuardia({super.key});

  @override
  State<DashboardGuardia> createState() => _DashboardGuardiaState();
}

class _DashboardGuardiaState extends State<DashboardGuardia> {
  final _api = ApiService();
  Turno? _turno;

  @override
  void initState() {
    super.initState();
    _cargarTurno();
  }

  Future<void> _cargarTurno() async {
    final turno = await _api.obtenerProximoTurno();
    setState(() => _turno = turno);
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthService>();
    return Scaffold(
      appBar: AppBar(
        title: const Text('Dashboard de Guardia'),
        actions: [
          IconButton(
            onPressed: () {
              auth.logout();
              Navigator.pushReplacementNamed(context, '/login');
            },
            icon: const Icon(Icons.logout),
          ),
        ],
      ),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Bienvenido ${auth.usuario?.nombre ?? ''}', style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
            const SizedBox(height: 10),
            if (_turno != null)
              TarjetaTurno(turno: _turno!)
            else
              const Center(child: CircularProgressIndicator()),
            const SizedBox(height: 16),
            const Text('Acciones rápidas'),
            const SizedBox(height: 12),
            Row(
              children: [
                BotonChecada(
                  titulo: 'Llegada',
                  icono: Icons.pin_drop,
                  color: Colors.green,
                  onPressed: () => Navigator.pushNamed(context, '/checada', arguments: 'Llegada'),
                ),
                const SizedBox(width: 8),
                BotonChecada(
                  titulo: 'Entrada',
                  icono: Icons.login,
                  color: Colors.blue,
                  onPressed: () => Navigator.pushNamed(context, '/checada', arguments: 'Entrada'),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                BotonChecada(
                  titulo: 'Salida',
                  icono: Icons.logout,
                  color: Colors.orange,
                  onPressed: () => Navigator.pushNamed(context, '/checada', arguments: 'Salida'),
                ),
                const SizedBox(width: 8),
                BotonChecada(
                  titulo: 'Turno extra',
                  icono: Icons.more_time,
                  color: Colors.purple,
                  onPressed: () => Navigator.pushNamed(context, '/checada', arguments: 'Extra'),
                ),
              ],
            ),
            const SizedBox(height: 16),
            Wrap(
              spacing: 8,
              children: [
                ActionChip(
                  label: const Text('Bitácora'),
                  onPressed: () => Navigator.pushNamed(context, '/bitacora'),
                ),
                ActionChip(
                  label: const Text('Incidencias'),
                  onPressed: () => Navigator.pushNamed(context, '/incidencia'),
                ),
                ActionChip(
                  label: const Text('Chat'),
                  onPressed: () => Navigator.pushNamed(context, '/chat'),
                ),
                ActionChip(
                  label: const Text('Capacitación'),
                  onPressed: () => Navigator.pushNamed(context, '/capacitacion'),
                ),
                ActionChip(
                  label: const Text('Perfil'),
                  onPressed: () => Navigator.pushNamed(context, '/perfil'),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
