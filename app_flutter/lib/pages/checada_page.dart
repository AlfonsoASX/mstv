import 'package:flutter/material.dart';

import '../services/facial_service.dart';
import '../services/location_service.dart';
import '../utils/constants.dart';

class ChecadaPage extends StatefulWidget {
  const ChecadaPage({super.key});

  @override
  State<ChecadaPage> createState() => _ChecadaPageState();
}

class _ChecadaPageState extends State<ChecadaPage> {
  final _comentarioController = TextEditingController();
  final _facialService = FacialService();
  final _locationService = LocationService();
  String _estado = 'Pendiente';
  bool _procesando = false;

  @override
  void dispose() {
    _comentarioController.dispose();
    super.dispose();
  }

  Future<void> _ejecutarChecada(String tipo) async {
    setState(() {
      _procesando = true;
      _estado = 'Validando geocerca...';
    });

    final dentroGeocerca = _locationService.isInsideCircle(
      const GeoPoint(19.43, -99.13),
      const GeoPoint(19.43, -99.13),
      AppConstants.geofenceRadioMetros,
    );

    if (!dentroGeocerca) {
      setState(() {
        _estado = 'Rechazada por geocerca';
        _procesando = false;
      });
      return;
    }

    setState(() => _estado = 'Verificando rostro...');
    final rostroOk = await _facialService.verificarSelfie();
    setState(() {
      _estado = rostroOk ? 'Aprobada ($tipo)' : 'Rechazada por rostro';
      _procesando = false;
    });
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Resultado: $_estado')));
  }

  @override
  Widget build(BuildContext context) {
    final tipo = (ModalRoute.of(context)?.settings.arguments ?? 'Checada') as String;
    return Scaffold(
      appBar: AppBar(title: Text('Checada: $tipo')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Text('Estado: $_estado'),
            const SizedBox(height: 10),
            TextField(
              controller: _comentarioController,
              maxLines: 2,
              decoration: const InputDecoration(
                labelText: 'Comentario rápido',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 16),
            ElevatedButton.icon(
              onPressed: _procesando ? null : () => _ejecutarChecada(tipo),
              icon: const Icon(Icons.fingerprint),
              label: Text('Confirmar $tipo'),
            ),
          ],
        ),
      ),
    );
  }
}
