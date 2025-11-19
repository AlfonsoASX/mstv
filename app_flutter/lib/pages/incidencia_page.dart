import 'package:flutter/material.dart';

import '../models/incidencia.dart';
import '../services/api_service.dart';
import '../widgets/card_incidencia.dart';

class IncidenciaPage extends StatefulWidget {
  const IncidenciaPage({super.key});

  @override
  State<IncidenciaPage> createState() => _IncidenciaPageState();
}

class _IncidenciaPageState extends State<IncidenciaPage> {
  final _api = ApiService();
  late Future<List<Incidencia>> _future;
  final _formKey = GlobalKey<FormState>();
  final _descripcionController = TextEditingController();
  String _tipo = 'Seguridad';
  String _prioridad = 'Media';

  @override
  void initState() {
    super.initState();
    _future = _api.obtenerIncidencias();
  }

  @override
  void dispose() {
    _descripcionController.dispose();
    super.dispose();
  }

  void _enviar() {
    if (!_formKey.currentState!.validate()) return;
    final nueva = Incidencia(
      tipo: _tipo,
      prioridad: _prioridad,
      descripcion: _descripcionController.text,
      estado: 'Pendiente',
      fecha: DateTime.now(),
    );
    setState(() {
      _future = Future<List<Incidencia>>.value([nueva]);
    });
    ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Incidencia enviada')));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Incidencias')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Form(
              key: _formKey,
              child: Column(
                children: [
                  DropdownButtonFormField<String>(
                    value: _tipo,
                    items: const [
                      DropdownMenuItem(value: 'Seguridad', child: Text('Seguridad')),
                      DropdownMenuItem(value: 'Operación', child: Text('Operación')),
                      DropdownMenuItem(value: 'Cliente', child: Text('Cliente')),
                    ],
                    onChanged: (v) => setState(() => _tipo = v ?? _tipo),
                    decoration: const InputDecoration(labelText: 'Tipo'),
                  ),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<String>(
                    value: _prioridad,
                    items: const [
                      DropdownMenuItem(value: 'Alta', child: Text('Alta')),
                      DropdownMenuItem(value: 'Media', child: Text('Media')),
                      DropdownMenuItem(value: 'Baja', child: Text('Baja')),
                    ],
                    onChanged: (v) => setState(() => _prioridad = v ?? _prioridad),
                    decoration: const InputDecoration(labelText: 'Prioridad'),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _descripcionController,
                    decoration: const InputDecoration(labelText: 'Descripción'),
                    maxLines: 3,
                    validator: (v) => (v == null || v.isEmpty) ? 'Captura una descripción' : null,
                  ),
                  const SizedBox(height: 12),
                  ElevatedButton.icon(
                    onPressed: _enviar,
                    icon: const Icon(Icons.report),
                    label: const Text('Enviar incidencia'),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),
            FutureBuilder<List<Incidencia>>(
              future: _future,
              builder: (context, snapshot) {
                final data = snapshot.data ?? [];
                if (data.isEmpty) {
                  return const Text('Sin incidencias registradas');
                }
                return Column(
                  children: data.map((e) => CardIncidencia(incidencia: e)).toList(),
                );
              },
            ),
          ],
        ),
      ),
    );
  }
}
